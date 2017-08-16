<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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

        $math = new Math();

        usort($markets, function ($a, $b) use ($math) {
          return ($math->div($a['OpenBuyOrders'], $a['OpenSellOrders']) > $math->div($b['OpenBuyOrders'], $b['OpenSellOrders'])) ? 1 : -1;
          list($aa, ) = explode('-', $a['MarketName']);
          list($bb, ) = explode('-', $b['MarketName']);
          $order = [$aa, $bb];
          sort($order);

          // Sort alphabetically if the bases are not the same.
          if ($aa != $bb) {
            return $order[0] == $aa ? -1 : 1;
          }
          if ($math->eq($a['BaseVolume'], $b['BaseVolume'])) {
            return 0;
          }
          // Sort by base volume.
          return $math->gt($a['BaseVolume'], $b['BaseVolume']) ? -1 : 1;
        });

        // Sum up the volume in BTC.
        $volume = call_user_func_array([$math, 'add'],
          array_map(
            function ($market) use ($math) {
              list($base, $counter) = explode('-', $market['MarketName']);
              if ($base != 'BTC') {
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
            $percent = $base == 'BTC' ? $math->mul($math->div($market['BaseVolume'], $volume), 100) : 0;
            $rows[] = [
              $market['MarketName'],
              $math->format($market['BaseVolume'], 0) . ' ' . $base,
              $math->format($percent, 2) . '%',
              $formatter($market['OpenBuyOrders'], $market['OpenSellOrders']),
              $formatter($market['OpenSellOrders'], $market['OpenBuyOrders']),
              $math->format($math->div($market['OpenBuyOrders'], $market['OpenSellOrders']), 2),
              $math->format($math->div($market['Bid'], $market['Ask']), 6)
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Market', 'Volume', 'Market Share', 'Buys', 'Sells', 'B2S Ratio', 'B2A Ratio']);
        $table->setRows($rows);
        $table->render();

        return $markets[0];
    }
}
