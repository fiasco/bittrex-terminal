<?php

namespace Bittrex;

use Bittrex\Math\Math;

class MarketTicker {

  protected $market;
  protected $prev = [];
  protected $averagePeriod = 50;

  public function update($market)
  {
    if (!empty($this->market)) {
      $this->prev[] = $this->market;
    }

    $this->market = $market;

    if (count($this->prev) > $this->averagePeriod) {
      array_shift($this->prev);
    }
  }

  public function setAveragePeriod($int)
  {
    $this->averagePeriod = $int;
  }

  public function getAveragePeriod()
  {
    return count($this->prev);
  }

  public function history()
  {
    if (empty($this->prev)) {
      return FALSE;
    }
    $history = $this->prev[0];
    $math = new Math();
    foreach (array_keys($history) as $key) {
      if (!is_numeric($history[$key])) {
        continue;
      }
      $history[$key] = $math->avg(array_map(function ($entry) use ($key) {
        return $entry[$key];
      }, $this->prev));
    }
    return $history;
  }

  public function display($key)
  {
    $math = new Math();
    if (!is_numeric($this->market[$key])) {
      return strtr('<comment>@value</comment>', [
        '@value' => $this->market[$key]
      ]);
    }
    $d = 0;
    if (strpos($this->market[$key], '.') !== FALSE) {
      if (strpos($this->market[$key], '.') > 3) {
        $d = 2;
      }
      else {
        $d = strlen($this->market[$key]) - strpos($this->market[$key], '.');
      }
    }
    if (!$prev = $this->history()) {
      return $math->format($this->market[$key], $d);
    }
    if ($math->eq($this->market[$key], $prev[$key])) {
      return $math->format($this->market[$key], $d);
    }
    $pct = $math->format($math->percent($this->market[$key], $prev[$key]) - 100, 2);
    if ($math->gt($this->market[$key], $prev[$key])) {
      return strtr('<info>@value (▲ @change%)</info>', [
        '@value' => $math->format($this->market[$key], $d),
        '@change' => $pct,
      ]);
    }
    else {
      return strtr('<fg=red>@value (▼ @change%)</>', [
        '@value' => $math->format($this->market[$key], $d),
        '@change' => $pct,
      ]);
    }
  }
}

 ?>
