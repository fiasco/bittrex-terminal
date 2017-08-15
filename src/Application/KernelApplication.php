<?php

namespace Bittrex\Application;

use Symfony\Component\Console\Application;
use Bittrex\Client;

class KernelApplication extends Application
{
    protected $client;

    /**
     * @param string $name    The name of the application
     * @param string $version The version of the application
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        // Bittrex operates in UTC.
        date_default_timezone_get('UTC');

        $keys = file_get_contents(dirname(__FILE__) . '/../../keys.json');
        $keys = json_decode($keys);

        $this->client = new Client($keys->Key, $keys->Secret);
    }

    public function api()
    {
        return $this->client;
    }
}
