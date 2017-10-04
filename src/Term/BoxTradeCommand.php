<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Bittrex\Order;
use Bittrex\Math\Math;
use Bittrex\FormattableFloat;

class BoxTradeCommand extends Command
{
    const SATOSHI = 0.00000001;
    const DUST = 0.00050000;
    const MOVEMENT = 0.1;
    protected $market;

    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('box')

       // the short description shown while running "php bin/console list"
       ->setDescription('Buy and sell on a fluctuating market.')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Buy and sell on a fluctuating market.')
       ->addArgument('market', InputArgument::REQUIRED, 'The market to buy and sell on');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        $this->market = strtoupper($input->getArgument('market'));
        $math = new Math;
        list($base, $counter) = explode('-', $this->market);

        $summary = $this->refreshMarket();

        // Check open orders
        $orders = $this->refreshOpenOrders();
        if (!empty($orders)) {
          $this->checkOrders($orders, $summary, $logger);
        }

        $wallets = $this->refreshWallets();

        // Do we have stuff to sell?
        // What rate would we sell at? The Ask.
        if ($math->gt($wallets[$counter]['Available'], 0)) {

        }
    }

    protected function checkOrders($orders, $summary, $logger)
    {
      $math = new Math;
      foreach ($orders as $order) {
        $order['Rate'] = (new FormattableFloat($order['Limit']))->getBtcString();
        $order['Quantity'] = $math->format($order['Quantity']);
        $order['Last'] = (new FormattableFloat($summary['Last']))->getBtcString();

        switch ($order['OrderType']) {
          // Check if the market has dropped.
          case 'LIMIT_SELL':
            $order['ClosingDistance'] = $math->format($math->sub(100, $math->percent($summary['Last'], $order['Limit'])), 2);
            break;

          // Check if the market has risen.
          case 'LIMIT_BUY':
            $order['ClosingDistance'] = $math->percent($math->sub($summary['Last'], $order['Limit']), $order['Limit']);
            break;

          default:
            throw new \ErrorException("Unknown order type: {$order['OrderType']}");
        }

        $logger->info(strtr('OrderType Quantity @ Rate (ClosingDistance% @ Last)', $order));

        // Movement is greating than 5%
        if ($order['ClosingDistance'] > 5) {
          $logger->error("Movement greater than 5%. Short term trade options don't look good. Cancelling trade...");
          $this->getApplication()->api()->cancel($order['OrderUuid']);
          continue;
        }
      }
    }

    protected function refreshWallets()
    {
      $balances = $this->getApplication()
                    ->api()
                    ->getBalances();
      $wallets = [];
      foreach ($balances as $bal) {
        $wallet[$bal['Currency']] = $bal;
      }
      return $bal;
    }

    protected function refreshMarket()
    {
      return $this->getApplication()
                    ->api()
                    ->getMarketSummary($this->market)[0];
    }

    protected function refreshOpenOrders()
    {
      return $this->getApplication()
                    ->api()
                    ->getOpenOrders($this->market);
    }
}
