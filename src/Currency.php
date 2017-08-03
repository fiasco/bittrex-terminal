<?php

namespace Bittrex;

class Currency {
  const precision = 9;
  const fee = 0.0025;
  protected $code;
  protected $markets = [];

  public function __construct($code)
  {
    $this->code = $code;
  }

  public function getCode() {
    return $this->code;
  }

  public function addMarket($currency, $market)
  {
    $market['Bid'] = number_format($market['Bid'], Currency::precision);
    $market['Ask'] = number_format($market['Ask'], Currency::precision);
    $this->markets[$currency] = $market;
    return $this;
  }

  public function getTradingCurrencies()
  {
    return array_keys($this->markets);
  }

  public function getMarket($currency)
  {
    if (!isset($this->markets[$currency])) {
      // throw new \Exception("No such market $currency in $this->code");
      return FALSE;
    }
    return $this->markets[$currency];
  }

  /**
   * To exchange $this->code for $currency.
   *
   * E.g. BTC buys DGB.
   */
  public function buy($currency, $amount)
  {
    $market = $this->getMarket($currency);
    // echo __METHOD__ . ": Buy $currency @ {$market['Bid']} $this->code\n";
    return self::process($market['Ask'], $amount);
  }

  /**
   * To exchange $currency for $this->code.
   *
   * E.g. DGB buys BTC.
   */
  public function sell($currency, $amount)
  {
    $market = $this->getMarket($currency);
    $rate = bcdiv(1, $market['Bid'], Currency::precision);
    // echo __METHOD__ . ": Buy {$this->code} @ $rate $currency\n";
    return self::process($rate, $amount);
  }

  static public function process($rate, $amount)
  {
    $amount = bcsub($amount, self::fee($amount), Currency::precision);
    $exchange = bcmul($rate, $amount, Currency::precision);
    return $exchange;
  }

  static public function fee($coin)
  {
    return bcmul($coin, Currency::fee, Currency::precision);
  }
}

 ?>
