<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class OrderStatesCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('order.states')

       // the short description shown while running "php bin/console list"
       ->setDescription('Order states')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Order states');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $orders = $this->getApplication()
        ->getStorage()
        ->get('api')
        ->getOrderHistory();

     if (!count($orders)) {
       $output->writeln("No orders found");
       return;
     }

     $balances = $this->getApplication()
        ->getStorage()
        ->get('api')
        ->getBalances();

     $data = $this->getApplication()
        ->getStorage()
        ->get('api')
        ->getMarketSummaries();

     $markets = [];
     foreach ($data as $market) {
       $markets[$market['MarketName']] = $market;
     }

     $rows = [];
     $rollingBalance = [];
     $sale = [];
     foreach ($orders as $order) {
       $market = $markets[$order['Exchange']];
       list($base, $counter) = explode('-', $order['Exchange']);

       // Rare circumstances, currencies rebrand and we won't be able to trace it
       if (!isset($balances[$counter])) {
         continue;
       }

       // If the balance is empty, all subsequent orders are meaningless.
       if (floatval($balances[$counter]['Balance']) <= 0) {
         continue;
       }

       if (!isset($rollingBalance[$counter])) {
         $rollingBalance[$counter] = 0;
       }

       if ($rollingBalance[$counter] == $balances[$counter]['Balance']) {
         continue;
       }

       $sale[$counter] = isset($sale[$counter]) ? $sale[$counter] : 0;

       switch ($order['OrderType']) {
         case 'LIMIT_BUY':

            if ($sale[$counter] > $order['Quantity']) {
              $sale[$counter] = bcsub($sale[$counter], $order['Quantity'], 9);
              continue;
            }
            $order['Quantity'] = bcsub($order['Quantity'], $sale[$counter], 9);

            $change = round(bcmul(bcdiv(
              number_format($market['Ask'], 9, '.', ''),
              number_format($order['Limit'], 9, '.', ''),
            9), 100, 2) - 100, 2);

            $tag = $change >= 0 ? 'info' : 'error';

            $rows[] = [
              $order['OrderUuid'],
              $counter,
              $order['Quantity'],
              number_format($order['Limit'], 9),
              number_format($market['Ask'],9),
              "<$tag>$change%</$tag>",
            ];

            $rollingBalance[$counter] = bcadd($rollingBalance[$counter], $order['Quantity'], 9);
            break;

         case 'LIMIT_SELL':
          $rollingBalance[$counter] = bcsub($rollingBalance[$counter], $order['Quantity'], 9);
          $sale[$counter] = bcadd($sale[$counter], $order['Quantity'], 9);
          break;
       }
     }
     $table = new Table($output);
     $table->setHeaders(['UUID', 'Currency', 'Quantity', 'Buy-in', 'Current Ask', 'Change']);
     $table->setRows($rows);
     $table->render();
   }
}
