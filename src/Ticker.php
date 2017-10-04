<?php

namespace Bittrex;

use Bittrex\Math\Math;

class Ticker {

  protected $period = [];
  protected $maxLength = 50;

  public function update($value)
  {
    $this->period[] = $value;

    if (count($this->period) > $this->maxLength) {
      array_shift($this->period);
    }
    return $this;
  }

  public function setPeriodLength($int)
  {
    $this->maxLength = $int;
  }

  public function getPeriodLength()
  {
    return count($this->period);
  }

  public function last()
  {
    return end($this->period);
  }

  public function avg()
  {
    $math = new Math();
    return $math->avg($this->period);
  }

  public function high()
  {
    return max($this->period);
  }

  public function low()
  {
    return min($this->period);
  }

  public function change()
  {
    $math = new Math();
    $change = $math->percent($this->last(), $this->avg()) - 100;
    return $change;
  }
}

 ?>
