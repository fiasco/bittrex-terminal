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
       ->setName('position')

       // the short description shown while running "php bin/console list"
       ->setDescription('Order states')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Order states');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $orders = $this->getApplication()
        ->api()
        ->getOrderHistory();

     if (!count($orders)) {
       $output->writeln("No orders found");
       return;
     }

     $balances = $this->getApplication()
        ->api()
        ->getBalances();

     $data = $this->getApplication()
        ->api()
        ->getMarketSummaries();

     $markets = [];
     foreach ($data as $market) {
       $markets[$market['MarketName']] = $market;
     }

     $usd = $markets['USDT-BTC'];

     $rows = [];
     $rollingBalance = [];
     $sale = [];
     $usdCashOut = 0;
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

       // If this is the first time we've encountered an order of this currency
       // we need to start a rolling balance for it.
       if (!isset($rollingBalance[$counter])) {
         $rollingBalance[$counter] = 0;
       }

       if ($rollingBalance[$counter] == $balances[$counter]['Balance']) {
         continue;
       }

       // Ensure a sale tracker exists.
       $sale[$counter] = isset($sale[$counter]) ? $sale[$counter] : 0;

       switch ($order['OrderType']) {
         case 'LIMIT_BUY':
            // If more has been sold that what has been bought since, then
            // deduct the order quantity from the tracked sale and skip this
            // order so it isn't displayed.
            if ($sale[$counter] >= $order['Quantity']) {
              $sale[$counter] = bcsub($sale[$counter], $order['Quantity'], 9);
              continue;
            }

            // Subtract the what's been sold since this order from the total
            // quantity position.
            $order['Quantity'] = bcsub($order['Quantity'], $sale[$counter], 9);
            // Reset the sale tracker
            $sale[$counter] = 0;

            $change = round(bcmul(bcdiv(
              number_format($market['Bid'], 9, '.', ''),
              number_format($order['Limit'], 9, '.', ''),
            9), 100, 2) - 100, 2);

            $oldValue = bcmul($order['Quantity'], $order['Limit'], 9);
            $newValue = bcmul($order['Quantity'], $market['Bid'], 9);
            $gain = bcsub($newValue, $oldValue, 9);
            $usdGain = bcmul($gain, $usd['Bid'], 2);
            $usdCashOut = bcadd($usdCashOut, $usdGain, 2);
            $gain = $gain > 0 ? "+$gain" : $gain;

            $tag = $change >= 0 ? 'info' : 'error';

            $rows[] = [
              $order['OrderUuid'],
              $counter,
              $order['Quantity'],
              number_format($order['Limit'], 9),
              number_format($market['Bid'],9),
              "<$tag>$change%</$tag>",
              "<$tag>$gain</$tag>",
              "<$tag>\$$usdGain</$tag>"
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
     $table->setHeaders(['UUID', 'Currency', 'Quantity', 'Buy-in', 'Current Bid', 'Change', 'P&L (BTC)', 'P&L (USD)']);
     $table->setRows($rows);
     $table->render();

     $tag = $usdCashOut >= 0 ? 'info' : 'error';
     $output->writeln("Esitmated cash position: <$tag>\$$usdCashOut USD</$tag>");
   }
}
