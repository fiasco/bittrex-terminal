<?php

namespace Bittrex;

use Bittrex\Math\Math;

class FormattableFloat {

  protected $value = 0.00;

  protected $highlighting = FALSE;

  protected $indicator = FALSE;

  protected $precision = FALSE;

  protected $prefix = '';

  protected $suffix = '';

  public function __construct($value)
  {
    $this->value = (float) $value;
  }

  public function setPrefix($prefix)
  {
    $this->prefix = $prefix;
    return $this;
  }

  public function setSuffix($suffix)
  {
    $this->suffix = $suffix;
    return $this;
  }

  public function setHighlighting($enabled = TRUE)
  {
    $this->highlighting = $enabled;
    return $this;
  }

  public function setIndicator($enabled = TRUE)
  {
    $this->indicator = $enabled;
    return $this;
  }

  public function setPrecision($precision)
  {
    $this->precision = $precision;
    return $this;
  }

  public function asFloat()
  {
    return $this->value;
  }

  public function __toString()
  {
    $math = new Math();
    $output = $math->format(abs($this->value), $this->precision);
    $negative = $math->lt($this->value, number_format(0, $this->precision));

    if ($this->indicator && (bccomp($this->value, 0) !== 0)) {
      $indicator = !$negative ? '▲' : '▼';
      $this->prefix .= $indicator;
    }

    if ($this->prefix) {
      $output = $this->prefix . $output;
    }

    if ($this->suffix) {
      $output .= $this->suffix;
    }

    if ($negative) {
      $output = "($output)";
    }

    if ($this->highlighting && !$math->eq($this->value, number_format(0, $this->precision))) {
      $color = !$negative ? 'green' : 'red';
      $output = '<fg=' . $color . '>' . $output . '</>';
    }
    return $output;
  }

  public function getBtcString()
  {
    $math = new Math();
    $float = number_format($this->value, 8, '.', '');
    $sats  = $math->mul($float, 100000000);

    if ($math->lt(abs($sats), 1000)) {
      $this->__construct($sats);
      $this->setSuffix(' sats');

      return $this;
    }
    elseif ($math->lt(abs($sats), 100000)) {
      $this->__construct($sats/1000);
      $this->setSuffix('k sats');

      return $this;
    }

    $mBTC = $sats / 100000;

    if ($math->lt(abs($mBTC), 1000)) {
      $this->__construct($mBTC);
      $this->setSuffix(' mBTC');
      return $this;
    }

    $this->setSuffix(' BTC');

    return $this;
    return $float . ' BTC';
  }
}

 ?>
