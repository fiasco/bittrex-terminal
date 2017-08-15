<?php

namespace Bittrex;

use Bittrex\Math\Math;

class Order
{
    const precision = 9;
    const fee = 0.0025;

    protected $AccountId = null;
    protected $OrderUuid = null;
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
    protected $PricePerUnit = null;
    protected $Opened = '2014-07-13T07:45:46.27';
    protected $Closed = null;
    protected $IsOpen = true;
    protected $Sentinel = '';
    protected $CancelInitiated = false;
    protected $ImmediateOrCancel = false;
    protected $IsConditional = false;
    protected $Condition = 'NONE';
    protected $ConditionTarget = null;

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

    public static function buyLimit($market, $baseQuantity, $rate)
    {
        $math = new Math();
        $quantity = $math->mul(self::calculateTransactableVolume($baseQuantity), $rate);

        $order = new static();
        $order->setProperty('Type', 'LIMIT_BUY')
          ->setProperty('Exchange', $market)
          ->setProperty('Quantity', $quantity)
          ->setProperty('Limit', $rate)
          ->setPrice();

        return $order;
    }

    public static function sellLimit($market, $counterQuantity, $rate)
    {
        $order = new static();
        $order->setProperty('Type', 'LIMIT_SELL')
          ->setProperty('Exchange', $market)
          ->setProperty('Quantity', $counterQuantity)
          ->setProperty('Limit', $rate)
          ->setPrice();

        return $order;
    }

    public function setPrice()
    {
        $subtotal = bcmul($this->Quantity, $this->Limit, self::precision);
        $fee = bcmul($subtotal, self::fee, self::precision);

        switch ($this->Type) {
      case 'LIMIT_SELL':
        $this->Price = bcsub($subtotal, $fee, self::precision);
        break;

      case 'LIMIT_BUY':
      default:
        $this->Price = bcadd($fee, $subtotal, self::precision);
        break;
    }
    }

    public static function calculateTransactableVolume($balance)
    {
        $p = bcdiv($balance, 100.25, self::precision);

        return bcmul($p, 100, self::precision);
    }
}
