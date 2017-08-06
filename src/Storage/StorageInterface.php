<?php

namespace Bittrex\Storage;

interface StorageInterface {

  public function get($key);

  public function set($key, $value);
}

 ?>
