<?php

namespace Bittrex;

class Trader {

  protected $options = [];
  protected $map = [];

  public function __construct($markets)
  {
    $this->analyseMarkets($markets);
  }

  public function hasOpportunity()
  {
      foreach ($this->options as $play => $value) {
        echo "$play:\t $value\n";
      }
      echo "OP: " . end($this->options) . PHP_EOL;
      return end($this->options) > 1;
  }

  public function getOpportunity()
  {
    end($this->options);
    return explode('-', key($this->options));
  }

  public function getCurrency($curr)
  {
    if (!isset($this->map[$curr])) {
      throw new \Exception("$curr is not a supported Currency.");
    }
    return $this->map[$curr];
  }

  protected function analyseMarkets($markets)
  {
    $map = [];

    foreach ($markets as $market)
    {
      list($a, $b) = explode('-', $market['MarketName']);

      if (!in_array($a, ['BTC', 'ETH'])) {
        continue;
      }

      if (!isset($map[$a])) {
        $map[$a] = new Currency($a);
      }

      $map[$a]->addMarket($b, $market);
    }

    $plays = [];

    foreach ($map['BTC']->getTradingCurrencies() as $currency) {
      if (isset($map[$currency])) {
        continue;
      }
      if (!$map['ETH']->getMarket($currency)) {
        continue;
      }

      try {
        // $play = implode('-', ['BTC', $currency, 'ETH', 'BTC']);
        // $btc = 1;
        // $coin = $map['BTC']->buy($currency, $btc);
        // $eth = $map['ETH']->sell($currency, $coin);
        // $btc = $map['BTC']->sell('ETH', $eth);

        // $plays[$play] = $btc;

        $play = implode('-', ['BTC', 'ETH', $currency, 'BTC']);
        $btc = 1;
        $eth = $map['BTC']->buy('ETH', $btc);
        $coin = $map['ETH']->buy($currency, $eth);
        $btc = $map['BTC']->sell($currency, $coin);

        $plays[$play] = $btc;
      }
      catch (\Exception $e) {
        print $e->getTraceAsString();
      }
    }

    asort($plays, SORT_NUMERIC);
    $this->options = $plays;
    $this->map = $map;
    return $this;
  }

}

 ?>
