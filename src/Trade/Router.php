<?php

namespace Bittrex\Trade;

use Bittrex\Math\Math;
use Bittrex\Order;

class Router
{
    protected $currency = 'BTC';

    protected $amount = 0.0;

    protected $routes;

    protected $counters = [];
    protected $bases = [];
    protected $markets = [];

    public function __construct($currency, $amount, array $markets)
    {
        $this->math = new Math();
        $this->currency = $currency;
        $this->amount = $this->math->float($amount);

        foreach ($markets as $market) {
            if (floatval($market['BaseVolume']) < 800) {
                continue;
            }
            $this->markets[$market['MarketName']] = $market;
            list($base, $counter) = explode('-', $market['MarketName']);
            $this->counters[$counter][$base] = $market;
            $this->bases[$base][$counter] = $market;
        }
    }

    protected function isCounterCurrency($currency)
    {
        return isset($this->counters[$currency]);
    }

    protected function isBaseCurrency($currency)
    {
        return isset($this->bases[$currency]);
    }

    public function getRoutes()
    {
        $routes = [];
        if ($this->isBaseCurrency($this->currency)) {
            foreach ($this->bases[$this->currency] as $market) {
                $this->routeToCounter($this->currency, $market, $routes);
            }
        }
        else {
            if (!isset($this->counters[$this->currency]) || count($this->counters[$this->currency]) == 1) {
              return [];
            }
            foreach ($this->counters[$this->currency] as $market) {
                $this->routeToBase($this->currency, $market, $routes);
                break;
            }
        }

        $markets = $this->markets;

        return array_map(function ($route) use ($markets) {
          return new Route($route, $markets);
        }, $routes);
    }

    public function routeToBase($counter, $baseMarket, &$routes, $chain = array())
    {
        // TODO: Track order.
        // Sell $counter to obtain $base.

        list($baseCurrency, ) = explode('-', $baseMarket['MarketName']);

        $chain[] = $counter;

        // // Get the number of bases left to traverse.
        // $bases = array_diff(array_keys($this->bases), $chain);
        //
        // var_dump($bases);die;
        //
        // // If this is the last base then we need to traverse back to the beginning.
        // if (count($bases) == 1 && reset($bases) == $baseCurrency) {
        //     var_dump("Returning to starting currency: $this->currency");
        //     $this->routeToCounter($this->currency, $this->bases[$baseCurrency][$this->currency], $routes, $chain);
        //
        //     return;
        // }

        $counterMarkets = array_keys($this->bases[$baseCurrency]);
        // Do not consider markets already in the chain.
        $counterMarkets = array_diff($counterMarkets, $chain);

        foreach ($counterMarkets as $currency) {
            $this->routeToCounter($baseCurrency, $this->bases[$baseCurrency][$currency], $routes, $chain);
        }
    }

    public function routeToCounter($base, $counterMarket, &$routes, $chain = array())
    {
        // TODO: Track order.
        // Sell $base to obtain $counter.

        list(, $counterCurrency) = explode('-', $counterMarket['MarketName']);

        $chain[] = $base;

        // Routing back to the begining currency completes the route.
        if (reset($chain) == $counterCurrency) {
            $chain[] = $counterCurrency;
            $routes[implode(',', $chain)] = $chain;
            return;
        }

        // Get the number of bases left to traverse.
        $bases = array_diff(array_keys($this->bases), $chain);

        // // If this is the last base then we need to traverse back to the beginning.
        // if (count($bases) == 1 && reset($bases) == $baseCurrency) {
        //   $this->routeToCounter($this->currency, $this->bases[$baseCurrency][$this->currency]);
        //   return;
        // }

        $markets = $this->counters[$counterCurrency];

        $baseMarkets = array_keys($this->bases);
        // Do not consider markets already in the chain.
        $baseMarkets = array_diff($baseMarkets, $chain);
        // Ensure they are available markets
        $baseMarkets = array_intersect($baseMarkets, array_keys($markets));

        if (empty($baseMarkets)) {
          $chain[] = $this->currency;
          $routes[implode(',', $chain)] = $chain;
          return;
        }

        foreach ($baseMarkets as $currency) {
            $this->routeToBase($counterCurrency, $markets[$currency], $routes, $chain);
        }
    }
}
