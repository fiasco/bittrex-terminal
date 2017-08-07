<?php

namespace Bittrex\Application;

use Symfony\Component\Console\Application;
use Bittrex\Storage\JsonStorage;

class KernelApplication extends Application {
  protected $storage;

  /**
   * @param string $name    The name of the application
   * @param string $version The version of the application
   */
  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
  {
    parent::__construct($name, $version);
    $this->storage = new JsonStorage();
  }

  public function getStorage()
  {
    return $this->storage;
  }

}
 ?>
