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
          try {
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
          }
          catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
          }

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
          $sale[$base] = isset($sale[$base]) ? $sale[$base] : 0;

          switch ($order['OrderType']) {
             case 'LIMIT_BUY':
                // If more has been sold that what has been bought since, then
                // deduct the order quantity from the tracked sale and skip this
                // order so it isn't displayed.
                if ($sale[$counter] >= $order['Quantity']) {
                    $sale[$counter] = $math->sub($sale[$counter], $order['Quantity']);
                    continue;
                }
                $sale[$base] = $math->add($sale[$base], $order['Price']);

                // Subtract the what's been sold since this order from the total
                // quantity position.
                $order['Quantity'] = $math->sub($order['Quantity'], $order['QuantityRemaining'], $sale[$counter]);

                if ($math->lt($order['Quantity'], 0)) {
                  continue;
                }
                // Reset the sale tracker
                $sale[$counter] = 0;

                $change = $math->format(
                  $math->percent($market['Last'], $order['PricePerUnit']) - 100,
                2);

                $gain = $math->sub(
                  $math->mul($order['Quantity'], $market['Last']),
                  $math->mul($order['Quantity'], $order['PricePerUnit'])
                );

                // This should be reported in BTC.
                $buyIn = $order['PricePerUnit'];
                $usdReport = [
                  'Spend' => $math->mul(
                    $math->mul($order['Quantity'], $order['PricePerUnit']),
                    $usd['Last']
                  ),
                  'Gain' => $math->mul($gain, $usd['Last']),
                ];

                // Unique condition
                if ($order['Exchange'] == 'USDT-BTC') {
                  // This will be in USD so needs to be converted into BTC.
                  $buyIn = $order['Quantity'];
                  $usdReport['Spend'] = $math->mul($order['PricePerUnit'], $order['Quantity']);
                  $usdReport['Gain'] = $gain;
                  $gain = 0.0;
                  $market['Last'] = $order['Quantity'];
                }

                $usdCashOut = $math->add($usdCashOut, $usdReport['Gain']);

                $tag = $change >= 0 ? 'fg=green' : 'fg=red';

                $time = strtotime($order['TimeStamp']);
                $since = time() - $time - (12 * 60 * 60);

                $tokens = [
                  '@tag' => $tag,
                  '@change' => $change,
                  '@gain' => $math->format($gain, 9),
                  '@gainUSD' => $math->format($usdReport['Gain'], 2),
                ];

                $rows[] = [
                  'Age' => $this->format_interval($since),
                  'Currency' => $counter,
                  'Quantity' => $order['Quantity'],
                  'Buy-in' => $math->format($buyIn),
                  $math->format($usdReport['Spend'],2),
                  $math->format($market['Last']),
                  strtr("<@tag>@change%</>", $tokens),
                  strtr("<@tag>@gain</>", $tokens),
                  strtr("<@tag>@gainUSD</>", $tokens),
                ];

                $rollingBalance[$counter] = $math->add($rollingBalance[$counter], $order['Quantity']);
                break;

             case 'LIMIT_SELL':
              $rollingBalance[$counter] = $math->sub($rollingBalance[$counter], $order['Quantity']);
              $sale[$counter] = $math->add($sale[$counter], $order['Quantity']);
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
