<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class CoinAnalyseCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('coin.analyse')

       // the short description shown while running "php bin/console list"
       ->setDescription('Shows a view of the market based on the given coin')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Shows a view of the market based on the given coin')
       ->addArgument('currency', InputArgument::REQUIRED, 'A currency, e.g. DGB');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $currency = $input->getArgument('currency');

     $markets = $this->getApplication()
        ->getStorage()
        ->get('api')
        ->getMarketSummaries();

     $rows = [];

     foreach ($markets as $market) {
       list($base, $counter) = explode('-', $market['MarketName']);

       unset($market['TimeStamp']);

       $market['Volume'] = (int) round($market['Volume']);

       $market['Buy'] = $market['OpenBuyOrders'];
       $market['Sell'] = $market['OpenSellOrders'];

       unset($market['OpenBuyOrders'], $market['OpenSellOrders']);

       foreach ($market as $key => &$value) {
         if (is_float($value)) {
           $value = number_format($value, 9);
         }
         elseif (is_int($value)) {
           $value = number_format($value);
         }
       }

        if (in_array($currency, [$base, $counter])) {
          $rows[] = $market;
        }
     }

     $table = new Table($output);
     $table->setHeaders(array_keys($rows[0]));
     $table->setRows($rows);
     $table->render();
   }
}
