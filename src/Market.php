<?php

namespace Bittrex;

use Psr\Log\LoggerInterface;

class Market {
  use \Psr\Log\LoggerTrait;

  const precision = 9;

  protected $properties;

  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  static public function create(Array $data, LoggerInterface $logger)
  {
    $market = new static($logger);
    foreach ($data as $key => $value) {
      $market->set($key, $value);
    }
    return $market;
  }

  public function getBase()
  {
    $pair = explode('-', $this->getMarketName());
    return $pair[0];
  }

  public function getCounter()
  {
    $pair = explode('-', $this->getMarketName());
    return $pair[1];
  }

  /**
   * To buy the counter currency for the base currency.
   */
  public function getMarketBuyRate()
  {
    $this->debug("Calculating a buy rate for {$this->properties['MarketName']}");
    $this->debug("{$this->properties['OpenBuyOrders']} buy orders in market of {$this->properties['Volume']} " . $this->getBase());

    // Tread cautiously with small volume markets.
    if ($this->properties['Volume'] < 100) {
      return $this->getAsk();
    }

    $rate = bcadd($this->getBid(), $this->getAsk(), self::precision);
    $rate = bcdiv($rate, 2, self::precision);
    return $rate;
  }

  /**
   * To sell the counter currency for the base currency.
   */
  public function getMarketSellRate()
  {
    $this->debug("Calculating a sell rate for {$this->properties['MarketName']}");
    $this->debug("{$this->properties['OpenSellOrders']} sell orders in market of {$this->properties['Volume']} " . $this->getBase());

    // Tread cautiously with small volume markets.
    if ($this->properties['Volume'] < 100) {
      return $this->getBid();
    }

    $rate = bcadd($this->getBid(), $this->getAsk(), self::precision);
    $rate = bcdiv($rate, 2, self::precision);
    $rate = bcadd($this->getBid(), $rate, self::precision);
    $rate = bcdiv($rate, 2, self::precision);
    return $rate;
  }

  public function __call($method, array $args)
  {
    preg_match_all('/((?:^|[A-Z])[a-z]+)/',$method,$matches);

    if (!in_array($matches[0][0], ['get', 'set'])) {
      throw new \InvalidArgumentException("No known method: $method.");
    }

    $property = implode('', array_slice($matches[0], 1));

    if ($matches[0][0] == 'set') {
      $this->properties[$property] = array_shift($args);
    }

    if (!isset($this->properties[$property])) {
      throw new \InvalidArgumentException("No known property: $property.");
    }

    return $this->properties[$property];
  }

  public function set($property, $value) {
    $this->properties[$property] = $value;
    return $value;
  }

  public function log($level, $message, array $context = array())
  {
    $this->logger->log($level, $message, $context);
  }
}
