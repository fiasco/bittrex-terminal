<?php

namespace Bittrex;

use Bittrex\Storage\StorageInterface;

class Position {

  protected $id;
  protected $currency;
  protected $quantity;
  protected $currentCurrency;
  protected $currentQuantity;
  protected $created;
  protected $lastModified;

  /**
   * @var Bittrex\Storage\StorageInterface
   */
  protected $store;

  public function __construct(StorageInterface $store)
  {
      $this->store = $store;
  }

  public function load($id)
  {
    $data = $this->store->get("position.$id");
    foreach ($data as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getCurrency()
  {
    return $this->currency;
  }

  public function getQuantity()
  {
    return $this->quantity;
  }

  public function getCurrentCurrency()
  {
    return $this->currentCurrency;
  }

  public function getCurrentQuantity()
  {
    return $this->currentQuantity;
  }

  public function getDate()
  {
    return $this->date;
  }

  public function getLastModified()
  {
    return $this->lastModified;
  }

  protected function set($property, $value)
  {
    $this->properties[$property] = $value;
    $this->lastModified = date('c');
    return $value;
  }
}

 ?>
