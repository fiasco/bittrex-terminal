<?php

namespace Bittrex\Storage;

class JsonStorage extends MemoryStorage
{
  const fileLocation = 'db.json';

  public function __construct()
  {
    if (file_exists(self::fileLocation) && $json = file_get_contents(self::fileLocation)) {
      $this->memory = json_decode($json, TRUE);
    }
  }

  public function set($key, $value)
  {
    $this->memory[$key] = $value;
    file_put_contents(self::fileLocation, json_encode($this->memory));
    return $value;
  }
}

 ?>
