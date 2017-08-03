<?php

namespace Bittrex;

use Psr\Log\LoggerInterface;

class Trader {

  use \Psr\Log\LoggerTrait;

  protected $logger;
  protected $client;
  protected $wallets = [];
  protected $markets = [];

  public function __construct(Client $client, LoggerInterface $logger)
  {
    $this->logger = $logger;
    $this->client = $client;

    $this->getBalances();
  }

  public function getClient()
  {
    return $this->client;
  }

  public function getBalances()
  {
    $this->info("Getting balances...");
    $bals = $this->client->getBalances();
    foreach ($bals as $bal) {
      $this->info(sprintf('%s wallet has a balance of %s', $bal['Currency'], number_format($bal['Available'], Currency::precision)));
      $this->wallets[$bal['Currency']] = $bal;
    }
    return $this->wallets;
  }

  public function getBalance($currency)
  {
    // if ($currency == 'BTC') {
    //   $this->warning("faking BTC balance.");
    //   return 0.91234;
    // }
    if (!isset($this->wallets[$currency])) {
      throw new \Exception("$currency wallet has no currency in it.");
    }
    return $this->wallets[$currency]['Available'];
  }

  public function analyseMarkets()
  {
    $result = $this->client->getMarketSummaries();
    $markets = [];
    $comp = [];
    foreach ($result as $data) {
      $market = Market::create($data, $this->logger);
      $markets[$market->getMarketName()] = $market;
      $comp[$market->getCounter()][$market->getBase()]['Buy'] = $market->getMarketBuyRate();
      $comp[$market->getCounter()][$market->getBase()]['Sell'] = $market->getMarketSellRate();
    }
    $this->markets = $markets;

    // Base order on BTC-ETH. The starting point of all analysis.
    $order1 = new Order($this, $this->logger);
    $bitcoin = $this->getBalance('BTC');
    $coin = Order::calculateTransactableVolume($bitcoin);
    $market = $this->getMarket('BTC-ETH');
    $this->debug("Got transaction volume of $coin BTC. Looking for ETH @ " . $market->getMarketBuyRate());
    $order1->buy('BTC-ETH', $coin, $market->getMarketBuyRate());
    $ethCoin = $order1->simulate();

    $chains = [];

    foreach ($comp as $counter => $bases) {

      if (!isset($bases['BTC']) || !isset($bases['ETH'])) {
        $this->debug("$counter cannot be traded between BTC and ETH.");
        continue;
      }
      $this->debug("$counter trades with " . implode(' and ', array_keys($bases)));

      $chain = new TransactionChain();
      $chain->setStartingPosition($bitcoin);

      $coin = $chain->addOrder($order1);

      $etx = $bases['ETH'];
      $order = new Order($this, $this->logger);
      $coin = Order::calculateTransactableVolume($coin);
      $this->debug("Got transaction volume of $coin ETH. Looking for $counter @ {$etx['Buy']}");
      $order->buy('ETH-' . $counter, $coin, $etx['Buy']);

      $coin = $chain->addOrder($order);

      $btx = $bases['BTC'];
      $order = new Order($this, $this->logger);
      $coin = Order::calculateTransactableVolume($coin);
      $this->debug("Got transaction volume of $coin $counter. Looking for BTC @ {$btx['Sell']}");
      $order->sell('BTC-' . $counter, $coin, $btx['Sell']);
      $coin = $chain->addOrder($order);

      $chain->setClosingPosition($coin);

      $chains[$counter] = $chain;

      $this->debug("$bitcoin BTC changed value by " . $chain->getPercentageMovement() . '%');
    }
    return $chains;
  }

  public function filterProfitableTrades($trades)
  {
    $trades = array_filter($trades, function (TransactionChain $trade) {
      return $trade->getPercentageMovement() > 0.8;
    });

    usort($trades, function (TransactionChain $a, TransactionChain $b) {
      if ($a->getPercentageMovement() == $b->getPercentageMovement()) {
        return 0;
      }
      return $a->getPercentageMovement() > $b->getPercentageMovement() ? 1 : -1;
    });

    return $trades;
  }

  public function getMarket($name)
  {
    return $this->markets[$name];
  }

  public function log($level, $message, array $context = array())
  {
    $this->logger->log($level, $message, $context);
  }

}

 ?>
