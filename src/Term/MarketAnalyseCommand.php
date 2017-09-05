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
       ->setHelp('Look for investment opportunities in the market')
       ->addArgument('base', InputArgument::OPTIONAL, 'The base market to show. E.g. BTC', 'BTC')
       ->addOption(
        'limit',
        'l',
         InputOption::VALUE_OPTIONAL,
         'Display the top $limit markets ordered by Volume. Default to 20',
         20
       );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $markets = $this->getApplication()
          ->api()
          ->getMarketSummaries();

        $markets = array_filter($markets, function ($market) use ($input) {
          list($base, $counter) = explode('-', $market['MarketName']);
          return $base == $input->getArgument('base');
        });

        $markets = array_filter($markets, function ($market) {
          return $market['BaseVolume'] > 200;
        });

        $math = new Math();

        $progress = new ProgressBar($output, count($markets));

        // start and displays the progress bar
        $progress->start();
        $output->writeln('');

        array_walk($markets, function (&$market) use ($math, $progress) {
          $json = file_get_contents('https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName=' . $market['MarketName'] . '&tickInterval=hour&_=' . time());
          $data = json_decode($json, TRUE);
          $sample = array_slice($data['result'], 0, 200);
          $total = 0;
          foreach ($sample as $point) {
            $total = $math->add($total, $point['C']);
          }
          $market['MA200'] = $math->div($total, count($sample));
          $market['MALastDiff'] = $math->sub($market['MA200'], $market['Last']);
          $market['MALastDiff'] = $math->percent($market['MALastDiff'], $market['MA200']);
          $progress->advance();
        });

        $progress->finish();

        $markets = array_filter($markets, function ($market) use ($math) {
          return $math->gt($market['MALastDiff'], 0);
        });

        usort($markets, function ($a, $b) use ($math) {
          return $a['MALastDiff'] > $b['MALastDiff'] ? -1 : 1;
        });

        // Sum up the volume in BTC.
        $volume = call_user_func_array([$math, 'add'],
          array_map(
            function ($market) use ($math, $input) {
              list($base, $counter) = explode('-', $market['MarketName']);
              if ($base != $input->getArgument('base')) {
                return 0;
              }
              return $math->float($market['BaseVolume']);
            },
          $markets
        ));

        $markets = array_slice($markets, 0, $input->getOption('limit'));

        // Formatter to highlight the higher value.
        $formatter = function ($a, $b) {
          if ($a > $b) {
            return "<comment>$a</comment>";
          }
          return $a;
        };

        $rows = [];
        foreach ($markets as $market) {
            list($base, $counter) = explode('-', $market['MarketName']);
            $percent = $base == $input->getArgument('base') ? $math->mul($math->div($market['BaseVolume'], $volume), 100) : 0;
            $rows[] = [
              $market['MarketName'],
              $math->format($market['BaseVolume'], 0) . ' ' . $base,
              $math->format($percent, 2) . '%',
              $formatter($market['OpenBuyOrders'], $market['OpenSellOrders']),
              $formatter($market['OpenSellOrders'], $market['OpenBuyOrders']),
              $math->format($math->div($market['OpenBuyOrders'], $market['OpenSellOrders']), 2),
              $math->format($math->div($market['Bid'], $market['Ask']), 6),
              $market['MALastDiff'],
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Market', 'Volume', 'Market Share', 'Buys', 'Sells', 'B2S Ratio', 'B2A Ratio', 'Under Valued']);
        $table->setRows($rows);
        $table->render();

        return $markets[0];
    }
}
