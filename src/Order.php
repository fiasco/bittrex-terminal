<?php

namespace Bittrex;

use Psr\Log\LoggerInterface;

class Order {

  use \Psr\Log\LoggerTrait;

  protected $logger;
  protected $trader;

  protected $position;
  protected $market;
  protected $quantity;
  protected $rate;
  protected $subtotal;
  protected $fee;
  protected $total;
  protected $uuid;

  public function __construct(Trader $trader, LoggerInterface $logger)
  {
    $this->trader = $trader;
    $this->logger = $logger;
  }

  public function getPosition()
  {
    return $this->position;
  }

  public function getMarket()
  {
    return $this->market;
  }

  public function getOrderName()
  {
    list($base, $counter) = explode('-', $this->market);
    return $this->position == 'buy' ? "$base=>$counter" : "$counter->$base";
  }

  public function buy($market, $quantity, $rate)
  {
    $quantity = $this->calcuateQuantity($quantity, $rate);

    if (isset($this->position)) {
      throw new \Exception("Cannot modify order once a position is taken.");
    }
    $this->position = 'buy';
    $this->market = $market;
    // Counter currency.
    $this->quantity = $quantity;
    $this->rate = $rate;
  }

  public function sell($market, $quantity, $rate)
  {
    if (isset($this->position)) {
      throw new \Exception("Cannot modify order once a position is taken.");
    }
    $this->position = 'sell';
    $this->market = $market;
    // Counter currency.
    $this->quantity = $quantity;
    $this->rate = $rate;
  }

  public function changeQuantity($quantity)
  {
    if ($this->position == 'buy') {
      $quantity = $this->calcuateQuantity($quantity, $this->rate);
    }
    $this->quantity = $quantity;
    $this->simulate();
    return $this;
  }

  public function simulate()
  {
    if (!isset($this->position)) {
      throw new \Exception("No buy or sell position specified.");
    }

    list($base, $counter) = explode('-', $this->market);

    $this->debug("Simulate $this->position order on $this->market for $this->quantity $counter @ $this->rate $base.");

    $this->subtotal = bcmul($this->quantity, $this->rate, Market::precision);
    $this->fee = bcmul($this->subtotal, 0.0025, Market::precision);

    $this->debug("\tSubtotal: $this->subtotal");
    $this->debug("\tFee: $this->fee");

    $this->total = bcadd($this->fee, $this->subtotal, Market::precision);
    $this->debug("\tTotal: $this->total");

    // Check wallet.
    $balance = min($this->trader->getBalance($base), 1);

    if ($balance >= $this->total) {
      $this->debug("Order can be purchased with wallet balance.");
    }
    else {
      $this->trader->debug("Insufficent funds in wallet: $balance $base. Require $this->total $base");
    }

    return $this->position == 'buy' ? $this->quantity : $this->subtotal;
  }

  public function execute()
  {
    $method = $this->position == 'buy' ? 'buyLimit' : 'sellLimit';

    $this->info("Calling $method($this->market, $this->quantity, $this->rate)");
    $ticket = call_user_func([$this->trader->getClient(), $method], $this->market, $this->quantity, $this->rate);
    $this->uuid = $ticket['uuid'];
    $this->info("Order created: $this->uuid");

    //$wait = 4;
    // Wait for order to fill.
    while (sleep(2) || TRUE) {
      $o = $this->trader->getClient()->getOrder($this->uuid);

      if (!$o['IsOpen']) {
        break;
      }

      // if (!$wait) {
      //   $this->error("Order fill took too long, cancelling...");
      //   $this->trader->getClient()->cancel($this->uuid);
      //   $o['CancelInitiated'] = 1;
      //   break;
      // }
      $this->info("Waiting for $this->market $this->position order to be filled. {$o['QuantityRemaining']} units remaining.");

      //$wait--;
    }

    if ($o['CancelInitiated']) {
      throw new \Exception("Order was cancelled. Trade stopped.");
    }

    $this->info("Asking quantity: $this->quantity. Got {$o['Quantity']} units.");

    if ($this->position == 'buy') {
      $this->info("Order cost {$o['Price']}. Estimated Subtotal: $this->subtotal. Estimated Total: $this->total.");
      return $o['Quantity'];
    }
    else {
      $this->info("Order made {$o['Price']}. Estimated Subtotal: $this->subtotal. Estimated Total: $this->total.");
      return $o['Price'];
    }
  }

  public function calcuateQuantity($coin, $rate, $pos = 'buy')
  {
    if (empty(floatval($coin)) || empty(floatval($rate))) {
      return 0.00000000;
    }
    return ($pos == 'buy') ? bcdiv($coin, $rate, Market::precision) : bcmul($coin, $rate, Market::precision);
  }

  static public function calculateTransactableVolume($balance)
  {
    $p = bcdiv($balance, 100.25, Market::precision);
    return bcmul($p, 100, Market::precision);
  }

  public function log($level, $message, array $context = array())
  {
    $this->logger->log($level, $message, $context);
  }
}
