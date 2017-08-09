<?php


namespace Bittrex\Math;


class Math {
  const precision = 9;

  public function float($val, $precision = 9) {
    $val = number_format($val, $precision, '.', '');

    while (substr($val, 0, -1) === '0')
    {
      $val = substr($val, -1);
    }

    // If all the numbers right of the decimal were zeros then this wasn't
    // really a float in the first place.
    if (substr($val, 0, -1) === '.') {
      $val = substr($val, -1);
    }

    return $val;
  }

  public function format($val, $precision = 9)
  {
    return number_format($this->float($val), $precision);
  }

  public function add($a, $b)
  {
    $factors = func_get_args();
    $total = array_shift($factors);
    foreach ($factors as $factor) {
      $total = bcadd($this->float($total), $this->float($factor), self::precision);
    }
    return $this->float($total);
  }

  public function sub($a, $b)
  {
    $factors = func_get_args();
    $total = array_shift($factors);
    foreach ($factors as $factor) {
      $total = bcsub($this->float($total), $this->float($factor), self::precision);
    }
    return $this->float($total);
  }

  public function mul($a, $b)
  {
    $factors = func_get_args();
    $total = array_shift($factors);
    foreach ($factors as $factor) {
      $total = bcmul($this->float($total), $this->float($factor), self::precision);
    }
    return $this->float($total);
  }

  public function div($a, $b)
  {
    return $this->float(bcdiv($this->float($a), $this->float($b), self::precision));
  }
}


 ?>
