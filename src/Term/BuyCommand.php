<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use Bittrex\Order;

class BuyCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('buy')

       // the short description shown while running "php bin/console list"
       ->setDescription('Place a buy order on a market')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Place a buy order on a market')
       ->addArgument('market', InputArgument::REQUIRED, 'The order uuid to cancel')
       ->addOption(
        'quantity',
        'u',
        InputOption::VALUE_OPTIONAL,
        'What quanity do you wish to spend'
      )
      ->addOption(
       'rate',
       'r',
       InputOption::VALUE_OPTIONAL,
       'What rate do you wish to trade at?'
     );
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $market = strtoupper($input->getArgument('market'))  ;

     // A buy requires a balance in the base currency.
     list($base, $counter) = explode('-', $market);

     // Check balances
     $balance = $this->getApplication()
                      ->api()
                      ->getBalance($base);

     $arguments['command'] = 'market.show';
     $arguments['market'] = $market;
     $i = new ArrayInput($arguments);
     $this->getApplication()
        ->find($arguments['command'])
        ->run($i, $output);

     $helper = $this->getHelper('question');

     // What rate would you like to place the buy at?
     if (!$rate = $input->getOption('rate')) {
       $question = new Question('What rate would you like to place the buy at? ');
       $rate = $helper->ask($input, $output, $question);
     }


     $avail = number_format($balance['Available'], Order::precision);

     $output->writeln("You have a $base balance of <info>$avail</info> available.");

     $coin = Order::calculateTransactableVolume($balance['Available']);
     $units = bcdiv($coin, $rate, Order::precision);

     $output->writeln("You can buy a maximum of $units $counter @ $rate $base.");

     if (!$quantity = $input->getOption('quantity')) {
       $question = new Question("How many $counter would you like to buy? ", 0);
       $quantity = $helper->ask($input, $output, $question);
     }

     $order = Order::buyLimit($market, $quantity, $rate);
     $price = $order->getProperty('Price');

     if ($balance['Available'] < $price) {
       $output->writeln("Cannot issue order: You don't have the correct balance.");
       $output->writeln("You need $price $base to facilitate that order.");
       $output->writeln("You have {$balance['Available']} $base available.");
       return;
     }

     $output->writeln("Placing order...");

     $uuid = $this->getApplication()
              ->api()
              ->buyLimit($market, $quantity, $rate);

     sleep(1);

    $arguments = [];
    $arguments['command'] = 'order.show';
    $arguments['uuid'] = $uuid['uuid'];

    $i = new ArrayInput($arguments);
    $market = $this->getApplication()
       ->find($arguments['command'])
       ->run($i, $output);

   }
}
