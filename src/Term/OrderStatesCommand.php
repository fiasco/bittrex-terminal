<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Bittrex\Math\Math;

class OrderStatesCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('position')

       // the short description shown while running "php bin/console list"
       ->setDescription('Order states')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Order states')
       ->addOption(
        'poll',
        'p',
         InputOption::VALUE_NONE,
         'Poll Bittrex for position'
       );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        do {
          list($rows, $usdCashOut) = $this->getOrderStates();

          // Remove the last render.
          if (isset($lines)) {

            // Move the cursor to the beginning of the line
            $output->write("\x0D");

            // Erase the line
            $output->write("\x1B[2K");
            $output->write(str_repeat("\x1B[1A\x1B[2K", $lines));
          }
          $lines = count($rows) + 5;

          $table = new Table($output);
          $table->setHeaders(['Age', 'Currency', 'Quantity', 'Buy-in', 'Buy-in (USD)', 'Last', 'Change', 'P&L (BTC)', 'P&L (USD)']);
          $table->setRows($rows);
          $table->render();

          $tag = $usdCashOut >= 0 ? 'info' : 'error';
          $usdCashOut = number_format($usdCashOut, 2);
          $output->writeln("Esitmated cash position: <$tag>\$$usdCashOut USD</$tag>");

          if ($input->getOption('poll')) {
            sleep(15);
          }
        }
        while ($input->getOption('poll'));
    }

    protected function getOrderStates()
    {
      $math = new Math();
      $orders = $this->getApplication()
      ->api()
      ->getOrderHistory();

      if (!count($orders)) {
          throw new \Exception("No orders found.");
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

      $usdbtc = $markets['USDT-BTC'];

      $rows = [];
      $rollingBalance = [];
      $sale = [];
      $usdCashOut = 0;
      foreach ($orders as $order) {
          // Can't report on a market that doesn't exist anymore.
          if (!isset($markets[$order['Exchange']])) {
              continue;
          }

          $usd = $usdbtc;

          $market = $markets[$order['Exchange']];
          list($base, $counter) = explode('-', $order['Exchange']);

          if ($base == 'USDT') {
            $usd = $markets[$order['Exchange']];
          }

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
                    $sale[$counter] = $math->sub($sale[$counter], $order['Quantity']);
                    continue;
                }

                // Subtract the what's been sold since this order from the total
                // quantity position.
                $order['Quantity'] = $math->sub($order['Quantity'], $order['QuantityRemaining'], $sale[$counter]);
                // Reset the sale tracker
                $sale[$counter] = 0;

                $change = round(
                  $math->mul($math->div($market['Last'], $order['PricePerUnit']), 100) - 100,
                2);

                $oldValue = $math->mul($order['Quantity'], $order['PricePerUnit']);
                $newValue = $math->mul($order['Quantity'], $market['Last']);
                $gain = $math->sub($newValue, $oldValue);
                $usdGain = $math->mul($gain, $usd['Last']);
                $usdCashOut = $math->add($usdCashOut, $usdGain);
                $usdGain = number_format($usdGain, 2);
                $usdSpend = $math->format($math->mul($oldValue, $usd['Last']), 2);

                $gain = $gain > 0 ? "+$gain" : $gain;

                $tag = $change >= 0 ? 'info' : 'error';

                $time = strtotime($order['TimeStamp']);
                $since = time() - $time - (12 * 60 * 60);

                $rows[] = [
                  $this->format_interval($since),
                  $counter,
                  $order['Quantity'],
                  number_format($order['PricePerUnit'], 9),
                  "\$$usdSpend",
                  number_format($market['Last'], 9),
                  "<$tag>$change%</$tag>",
                  "<$tag>$gain</$tag>",
                  "<$tag>\$$usdGain</$tag>",
                ];

                $rollingBalance[$counter] = bcadd($rollingBalance[$counter], $order['Quantity'], 9);
                break;

             case 'LIMIT_SELL':
              $rollingBalance[$counter] = bcsub($rollingBalance[$counter], $order['Quantity'], 9);
              $sale[$counter] = bcadd($sale[$counter], $order['Quantity'], 9);
              break;
           }
      }

      return [$rows, $usdCashOut];
    }

    protected function format_interval($interval, $granularity = 2)
    {
        $units = array(
      '1 year|@count years' => 31536000,
      '1 month|@count months' => 2592000,
      '1 week|@count weeks' => 604800,
      '1 day|@count days' => 86400,
      '1 hour|@count hours' => 3600,
      '1 min|@count min' => 60,
      '1 sec|@count sec' => 1,
    );
        $output = '';
        foreach ($units as $key => $value) {
            $key = explode('|', $key);
            if ($interval >= $value) {
                $time = floor($interval / $value);
                $output .= ($output ? ' ' : '').strtr(($time == 1 ? $key[0] : $key[1]), ['@count' => $time]);
                $interval %= $value;
                --$granularity;
            }

            if ($granularity == 0) {
                break;
            }
        }

        return $output;
    }
}
