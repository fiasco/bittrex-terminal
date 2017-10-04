<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Bittrex\Math\Math;

class MarketAnalyseCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('market.analyse')

       // the short description shown while running "php bin/console list"
       ->setDescription('Look for investment opportunities in the market')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Look for investment opportunities in the market');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $summaries = $this->getApplication()
          ->api()
          ->getMarketSummaries();

        $m = new Math;

        $markets = [];

        foreach ($summaries as $market) {

          list($base, $counter) = explode('-', $market['MarketName']);
          if ($base != 'BTC') {
            continue;
          }

          $output->writeln($market['MarketName'] . '...');
          $out = file_get_contents('https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName=' . $market['MarketName'] . '&tickInterval=day&_=' . time());
          $out = json_decode($out, TRUE);
          
          // Often the first day is bad data. So avoid it.
          $tick = array_shift($out['result']);
          $tick = array_shift($out['result']);

          $markets[$market['MarketName']] = [
            'MarketName' => $market['MarketName'],
            'High' => $tick['H'],
            'Low' => $tick['L'],
            'Last' => $tick['C'],
            'HighTime' => $tick['T'],
            'LowTime' => $tick['T'],
            'Average' => [$tick['C']],
          ];
          $mk = &$markets[$market['MarketName']];

          while ($tick = array_shift($out['result'])) {
            if ($m->gt($tick['H'], $mk['High'])) {
              $mk['High'] = $tick['H'];
              $mk['HighTime'] = $tick['T'];
            }
            if ($m->lt($tick['L'], $mk['Low'])) {
              $mk['Low'] = $tick['L'];
              $mk['LowTime'] = $tick['T'];
            }
            $mk['Last'] = $tick['C'];
            $mk['Average'][] = $tick['C'];
          }

          $mk['Average'] = $m->avg($mk['Average']);
        }

        foreach ($markets as &$market) {
          $market['Spread'] = $m->percent($m->sub($market['High'], $market['Low']), $market['Low']);
          $market['BounceFromTheBottom'] = $m->percent($market['Last'], $market['Low']) - 100;

          $market['High'] = $m->format($market['High']);
          $market['Low'] = $m->format($market['Low']);
          $market['Last'] = $m->format($market['Last']);

          $market['HighTime'] = date('Y-m-d', strtotime($market['HighTime']));
          $market['LowTime'] = date('Y-m-d', strtotime($market['LowTime']));
        }

        $markets = array_values($markets);
        usort($markets, function ($a, $b) {
          if ($a['BounceFromTheBottom'] == $b['BounceFromTheBottom']) {
            return 0;
          }
          return $a['BounceFromTheBottom'] > $b['BounceFromTheBottom'] ? 1 : -1;
        });

        $table = new Table($output);
        $table->setHeaders(array_keys($markets[0]));
        $table->setRows($markets);
        $table->render();
    }
}
