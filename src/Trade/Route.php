<?php

namespace Bittrex\Trade;

use Bittrex\Order;
use Bittrex\Math\Math;

class Route {
  /**
   * @var Array
   */
  protected $markets;

  /**
   * @var Array of Bittrex\Order.
   */
  protected $orders;

  /**
   * @var Array
   */
  protected $route;

  protected $amount = 0;

  protected $roi = 0;

  protected $initialAmount;

  public function __construct($route, $markets)
  {
    $this->markets = $markets;
    $this->route = $route;
  }

  public function route($amount = 1)
  {
     $this->initialAmount = $amount;
     $math = new Math();
     // Reset orders.
     $this->orders = [];
     $left = reset($this->route);
     while ($right = next($this->route)) {
       if (isset($this->markets["$left-$right"])) {
         $market = "$left-$right";

         $quantity = $math->div(Order::calculateTransactableVolume($amount), $this->markets[$market]['Ask']);
         // Buy.
         $order = new Order();
         $order->setProperty('Type', 'LIMIT_BUY')
           ->setProperty('Exchange', $market)
           ->setProperty('Limit', $this->markets[$market]['Ask'])
           ->setProperty('Quantity', $quantity)
           ->setPrice();

         $amount = $quantity;
       }
       elseif (isset($this->markets["$right-$left"])) {
         // Sell.
         $market = "$right-$left";
         $order = new Order();
         $order->setProperty('Type', 'LIMIT_SELL')
           ->setProperty('Exchange', $market)
           ->setProperty('Limit', $this->markets[$market]['Bid'])
           ->setProperty('Quantity', $amount)
           ->setPrice();

         $amount = $order->getProperty('Price');
       }
       else {
         // throw new \Exception("Unavailable market: $left-$right");
         // Invalid route.
         return -1;
       }

       $this->orders[] = $order;
       $left = $right;
     }

     $this->amount = $amount;
     $this->roi = $math->mul($math->div($amount, $this->initialAmount), 100);
     return $amount;
  }

  public function count()
  {
    return count($this->route);
  }

  public function getAmount()
  {
    return $this->amount;
  }

  public function getInitialAmount()
  {
    return $this->initialAmount;
  }

  public function getROI()
  {
    return $this->roi;
  }

  public function getCurrency()
  {
    return reset($this->route);
  }

  public function getTitle()
  {
    return implode(' => ', $this->route);
  }

  public function getOrders()
  {
    return $this->orders;
  }




}


 ?>
