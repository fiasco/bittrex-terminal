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

class SellCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('sell')

       // the short description shown while running "php bin/console list"
       ->setDescription('Place a sell order on a market')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Place a sell order on a market')
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
                      ->getStorage()
                      ->get('api')
                      ->getBalance($counter);

    if (empty($balance['Available'])) {
      $output->writeln("You have no $counter to sell");
      return;
    }

     $arguments['command'] = 'market.show';
     $arguments['market'] = $market;
     $i = new ArrayInput($arguments);
     $this->getApplication()
        ->find($arguments['command'])
        ->run($i, $output);

     $helper = $this->getHelper('question');

     // What rate would you like to place the buy at?
     if (!$rate = $input->getOption('rate')) {
       $question = new Question('What rate would you like to place the sell at? ');
       $rate = $helper->ask($input, $output, $question);
     }


     $avail = number_format($balance['Available'], Order::precision);

     $output->writeln("You have a $counter balance of <info>$avail</info> available.");

     if (!$quantity = $input->getOption('quantity')) {
       $question = new Question("How many $counter would you like to sell? ", 0);
       $quantity = $helper->ask($input, $output, $question);
     }

     if ($balance['Available'] < $quantity) {
       $output->writeln("Cannot issue order: You don't have the correct balance.");
       $output->writeln("You need $quantity $counter to facilitate that order.");
       $output->writeln("You have {$balance['Available']} $counter available.");
       return;
     }

     $output->writeln("Placing order...");

     $uuid = $this->getApplication()
              ->getStorage()
              ->get('api')
              ->sellLimit($market, $quantity, $rate);

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
