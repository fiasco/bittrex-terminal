<?php

namespace Bittrex;

class TransactionChain {

  protected $startingPosition = 0.0;
  protected $currentPosition = 0.0;
  protected $closingPosition = 0.0;
  protected $chain = [];

  public function setStartingPosition($coin)
  {
    $this->startingPosition = $coin;
    return $this;
  }

  public function setClosingPosition($coin)
  {
    $this->closingPosition = $coin;
    return $this;
  }

  public function addOrder(Order $order)
  {
    $this->chain[] = $order;
    return $this->currentPosition = $order->simulate();
  }

  public function execute(Trader $trader)
  {
    $this->currentPosition = $this->startingPosition;
    foreach ($this->chain as $order) {
      $order->changeQuantity(Order::calculateTransactableVolume($this->currentPosition));
      $this->currentPosition = $order->execute();
    }
    $trader->info("START: $this->currentPosition END: $this->currentPosition");
    return $this->closingPosition = $this->currentPosition;
  }

  public function getPercentageMovement()
  {
    $gain = bcdiv($this->closingPosition, $this->startingPosition, 6) * 100;
    return bcsub($gain, 100, 4);
  }

  public function getChainName()
  {
    $names = [];
    foreach ($this->chain as $order) {
      $names[] = $order->getOrderName();
    }
    return implode(' ', $names);
  }
}
