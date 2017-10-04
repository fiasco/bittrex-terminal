<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Bittrex\Math\Math;
use Bittrex\Ticker;
use Bittrex\TrendIndicator;
use Bittrex\FormattableFloat;

class OrderPendingCommand extends Command
{
    protected $orderStates = [];

    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('pending')

       // the short description shown while running "php bin/console list"
       ->setDescription('Orders pending')

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
        $iteration = 1;
        $orders = $this->getApplication()
        ->api()
        ->getOpenOrders();

        if (!count($orders)) {
            throw new \Exception("No orders found.");
            return;
        }

        $this->updateOrders($orders);

        do {

          // Refresh orders only every 15 iterations.
          if (($iteration % 3) === 0) {
            $orders = $this->getApplication()
            ->api()
            ->getOpenOrders();

            $this->updateOrders($orders);
          }
          $iteration++;

          $tables = $this->buildOrdersTables($this->getMarkets());

          $this->clear($output);

          foreach ($tables as $base => $table) {
            $table->render();
          }

          if ($input->getOption('poll')) {
            sleep(3);
          }
        }
        while ($input->getOption('poll'));
    }

    protected function clear($output)
    {
      $height = exec('tput lines');
      // Move the cursor to the beginning of the line
      $output->write("\x0D");
      // Erase the line
      $output->write("\x1B[2K");
      $output->write(str_repeat("\x1B[1A\x1B[2K", $height));
    }

    protected function updateOrders($orders)
    {
      $ids = array_map(function ($o) {
        return $o['OrderUuid'];
      }, $orders);

      // Orders that have been filled or cancelled should be removed.
      $removed = array_diff(array_keys($this->orderStates), $ids);
      foreach ($removed as $uuid) {
        unset($this->orderStates[$uuid]);
      }

      foreach ($orders as $order) {
        $uuid = $order['OrderUuid'];

        if (!isset($this->orderStates[$uuid])) {
          $this->orderStates[$uuid] = $order;
          $this->orderStates[$uuid]['Distance'] = new Ticker();
          $this->orderStates[$uuid]['Distance']->setPeriodLength(900);

          $this->orderStates[$uuid]['Last'] = new Ticker();
          $this->orderStates[$uuid]['Last']->setPeriodLength(900);
        }
      }
      return $this;
    }

    protected function getMarkets()
    {
      $data = $this->getApplication()
      ->api()
      ->getMarketSummaries();

      $markets = [];
      foreach ($data as $market) {
          $markets[$market['MarketName']] = $market;
      }
      return $markets;
    }

    protected function buildOrdersTables($markets)
    {
      $math = new Math();

      $rows = [];
      foreach ($this->orderStates as &$order) {
          $market = $markets[$order['Exchange']];

          $order['Last']->update($market['Last']);

          $order['Distance']->update(
            $math->percent($market['Last'], $order['Limit']) - 100
          );

          $trend = (new TrendIndicator($order['Distance']->change()))->setHighlighting();

          $order['Filled'] = $math->percent(
            $math->sub($order['Quantity'], $order['QuantityRemaining']),
            $order['Quantity']
          );

          list($base, $counter) = explode('-', $order['Exchange']);
          $precision = $base == 'USDT' ? 4 : 8;

          // Work out what the USD amount is.
          if ($base == 'USDT') {
            $UsdBuyIn = new FormattableFloat($math->mul($order['Quantity'], $order['Limit']));
          }
          else {
            $exchange = $markets['USDT-' . $base];
            $UsdBuyIn = new FormattableFloat($math->mul($order['Quantity'], $order['Limit'], $exchange['Last']));
          }

          $UsdBuyIn->setPrefix('$')
                   ->setSuffix(' USD')
                   ->setPrecision(2);

          $row = [
            'OrderUuid' => $order['OrderUuid'],
            'OrderType' => $order['OrderType'],
            'Currency'  => $counter,
            'Limit'     => new FormattableFloat($order['Limit']),
            'Quantity'  => $order['Quantity'],
            'Price'     => $UsdBuyIn,
            'Filled'    => $order['Filled'] . '%',
            'Distance'  => (new FormattableFloat($order['Distance']->last()))
                             ->setPrecision(2)
                             ->setSuffix('%') . ' ' . $trend,
            'Last'      => new FormattableFloat($order['Last']->last()),
          ];

          switch ($base) {
            case 'BTC':
              $row['Limit'] = $row['Limit']->getBtcString();
              $row['Last'] = $row['Last']->getBtcString();
              break;
            default:
              $row['Limit']->setPrecision($precision);
              $row['Last']->setPrecision($precision);
              break;
          }

          $rows[] = $row;
      }

      $tables = [];
      $table = new Table(new ConsoleOutput());
      $table->setHeaders(array_keys($rows[0]));
      $table->setRows($rows);

      $tables[] = $table;

      return $tables;
    }

    // protected function format_interval($interval, $granularity = 2)
    // {
    //     $units = array(
    //   '1 year|@count years' => 31536000,
    //   '1 month|@count months' => 2592000,
    //   '1 week|@count weeks' => 604800,
    //   '1 day|@count days' => 86400,
    //   '1 hour|@count hours' => 3600,
    //   '1 min|@count min' => 60,
    //   '1 sec|@count sec' => 1,
    // );
    //     $output = '';
    //     foreach ($units as $key => $value) {
    //         $key = explode('|', $key);
    //         if ($interval >= $value) {
    //             $time = floor($interval / $value);
    //             $output .= ($output ? ' ' : '').strtr(($time == 1 ? $key[0] : $key[1]), ['@count' => $time]);
    //             $interval %= $value;
    //             --$granularity;
    //         }
    //
    //         if ($granularity == 0) {
    //             break;
    //         }
    //     }
    //
    //     return $output;
    // }
}
