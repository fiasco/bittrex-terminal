<?php

namespace Bittrex;

use Psr\Log\LoggerInterface;

class Order {

  const precision = 9;
  const fee = 0.0025;

  protected $AccountId = NULL;
  protected $OrderUuid = NULL;
  protected $Exchange = 'BTC-ETH';
  protected $Type = 'LIMIT_BUY';
  protected $Quantity = 1000;
  protected $QuantityRemaining = 1000;
  protected $Limit = 1.00;
  protected $Reserved = 1.000000000000;
  protected $ReserveRemaining = 1.0000000000;
  protected $CommissionReserved = 0.0;
  protected $CommissionReserveRemaining = 0.0;
  protected $CommissionPaid = 0;
  protected $Price = 0;
  protected $PricePerUnit = NULL;
  protected $Opened = '2014-07-13T07:45:46.27';
  protected $Closed = NULL;
  protected $IsOpen = true;
  protected $Sentinel = '';
  protected $CancelInitiated = false;
  protected $ImmediateOrCancel = false;
  protected $IsConditional = false;
  protected $Condition = 'NONE';
  protected $ConditionTarget = NULL;

  public function getProperty($prop)
  {
      return $this->{$prop};
  }

  public function setProperty($prop, $value)
  {
    if (!isset($this->{$prop})) {
      throw new \InvalidArgumentException("No such property: $prop");
    }
    $this->{$prop} = $value;
    return $this;
  }

  static public function buyLimit($market, $quantity, $rate)
  {
    $order = new static();
    $order->setProperty('Type', 'LIMIT_BUY')
          ->setProperty('Exchange', $market)
          ->setProperty('Quantity', $quantity)
          ->setProperty('Limit', $rate)
          ->setPrice();
    return $order;
  }

  static public function sellLimit($market, $quantity, $rate)
  {
    $order = new static();
    $order->setProperty('Type', 'LIMIT_SELL')
          ->setProperty('Exchange', $market)
          ->setProperty('Quantity', $quantity)
          ->setProperty('Limit', $rate)
          ->setPrice();
    return $order;
  }

  public function setPrice()
  {
    $subtotal = bcmul($this->Quantity, $this->Limit, Order::precision);
    $fee = bcmul($subtotal, Order::fee, Order::precision);

    switch ($this->Type) {
      case 'LIMIT_SELL':
        $this->Price = bcsub($subtotal, $fee, Order::precision);
        break;

      case 'LIMIT_BUY':
      default:
        $this->Price = bcadd($fee, $subtotal, Order::precision);
        break;
    }
  }

  static public function calculateTransactableVolume($balance)
  {
    $p = bcdiv($balance, 100.25, Order::precision);
    return bcmul($p, 100, Order::precision);
  }
}
