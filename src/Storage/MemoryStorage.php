<?php

namespace Bittrex\Storage;

class MemoryStorage implements StorageInterface
{
  protected $memory;

  public function get($key)
  {
    if (!isset($this->memory[$key])) {
      throw new \Exception("No such item exists: $key");
    }
    return $this->memory[$key];
  }

  public function set($key, $value)
  {
    $this->memory[$key] = $value;
    return $value;
  }
}

 ?>
