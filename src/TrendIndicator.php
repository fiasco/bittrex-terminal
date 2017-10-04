<?php

namespace Bittrex;

use Bittrex\Math\Math;

class TrendIndicator {

  protected $value = 0.00;

  protected $highlighting = FALSE;

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

  public function __toString()
  {

    if (bccomp($this->value, 0) === 0) {
      return '';
    }

    $output = bccomp($this->value, 0) === 1 ? '▲' : '▼';


    if ($this->prefix) {
      $output = $this->prefix . $output;
    }

    if ($this->suffix) {
      $output .= $this->suffix;
    }

    if ($this->highlighting &&  (bccomp($this->value, 0) !== 0)) {
      $color = bccomp($this->value, 0) === 1 ? 'green' : 'red';
      $output = '<fg=' . $color . '>' . $output . '</>';
    }
    return $output;
  }
}

 ?>
