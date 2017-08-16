<?php

namespace Bittrex\Application;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
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

        $credentials_file = $_SERVER['HOME'] . '/.bittrex_terminal';

        if (file_exists($credentials_file)) {
          $keys = file_get_contents($_SERVER['HOME'] . '/.bittrex_terminal');
          $keys = json_decode($keys);

          $this->client = new Client($keys->Key, $keys->Secret);
        }
    }

    public function api()
    {
        if (empty($this->client)) {
          $arguments = [];
          $arguments['command'] = 'help';
          $arguments['command_name'] = 'setup';

          $i = new ArrayInput($arguments);
          $this->find($arguments['command'])
             ->run($i, new ConsoleOutput());
          throw new \Exception("Credentials do no exist, please run setup.");
        }
        return $this->client;
    }
}
