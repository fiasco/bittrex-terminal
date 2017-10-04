<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Bittrex\Math\Math;
use Bittrex\FormattableFloat;
use Bittrex\TrendIndicator;

class OrderStatesCommand extends PollingCommand
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
       ->setHelp('Order states');
       parent::configure();
    }

    protected function sync($db, $input, $output)
    {
      if (!$db->count || (($db->count % 15) === 0)) {
        $db->balances = $this->getApplication()
          ->api()
          ->getBalances();

        $db->orders = $this->getApplication()
          ->api()
          ->getOrderHistory();
      }

      $data = $this->getApplication()
        ->api()
        ->getMarketSummaries();

      $markets = [];
      foreach ($data as $market) {
          $markets[$market['MarketName']] = $market;
      }

      $db->markets = $markets;
    }

    protected function process($db, $input, $output)
    {
      $math = new Math();
      $db->render['orders'] = [];
      $balances = $db->balances;
      $balance = 0;
      foreach ($db->orders as $order) {
        // Can't report on a market that doesn't exist anymore.
        if (!isset($db->markets[$order['Exchange']])) {
            continue;
        }

        $market = $db->markets[$order['Exchange']];

        $isBuy = $order['OrderType'] == 'LIMIT_BUY';

        // Extract the base and counter.
        list($base, $counter) = explode('-', $order['Exchange']);

        if ($base != 'BTC') {
          continue;
        }

        // If the order pertains to no holding balance, disguard.
        $currency = $isBuy ? $counter : $base;
        if (empty($db->balances[$currency])) {
          continue;
        }

        // If the balance is empty, all subsequent orders are meaningless.
        $wallet = &$balances[$currency];
        if (floatval($wallet['Balance']) <= 0) {
            continue;
        }

        $wallet['Balance'] = call_user_func([$math, $isBuy ? 'sub' : 'add'], $wallet['Balance'], $order['Quantity']);

        if (!$isBuy) {
          continue;
        }

        if (floatval($wallet['Balance']) <= 0) {
            $order['Quantity'] = preg_replace('/((.)?0+)$/', '', $math->add($order['Quantity'], $wallet['Balance']));
            if (floatval($order['Quantity']) <= 0) {
              continue;
            }
        }

        $trend = new FormattableFloat($math->percent($market['Last'], $order['PricePerUnit']) - 100);
        $trend->setHighlighting();
        //$trend->setIndicator();
        $trend->setPrecision(2);
        $trend->setSuffix('%');

        $pnl = $math->sub($market['Last'], $order['PricePerUnit']);

        $db->render['orders'][] = [
          'Exchange' => $order['Exchange'],
          'Quantity' => $order['Quantity'],
          'Entry' => (new FormattableFloat($order['PricePerUnit']))->getBtcString(),
          'Price' => (new FormattableFloat($order['Price']))->getBtcString(),
          'Last' => (new FormattableFloat($market['Last']))->getBtcString(),
          'P&L' => (new FormattableFloat($math->mul($pnl, $order['Quantity'])))
            ->getBtcString()
            ->setHighlighting(),
          'P&L USD' => (new FormattableFloat($math->mul($pnl, $order['Quantity'], $db->markets['USDT-BTC']['Last'])))
            ->setPrecision(2)
            ->setHighlighting(),
          '%' => $trend,
        ];

        $balance = $math->add($balance, $math->mul($pnl, $order['Quantity']));
      }

      $db->balance = $balance;
      $db->UsdBal = round($math->mul($balance, $db->markets['USDT-BTC']['Last']), 2);
    }

    protected function render($db, $input, $output)
    {
      $io = new SymfonyStyle($input, $output);
      $io->table(array_keys($db->render['orders'][0]), $db->render['orders']);
      $io->title(strtr('Estimated position: @BTC (@USD USD)', [
        '@BTC' => (new FormattableFloat($db->balance))->getBtcString(),
        '@USD' => $db->UsdBal,
      ]));
    }
}
