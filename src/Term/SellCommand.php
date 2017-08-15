<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use Bittrex\Order;
use Bittrex\Math\Math;

class SellCommand extends Command
{
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
      )
      ->addOption(
        'fast',
        'f',
        InputOption::VALUE_NONE,
        'Sell at the buying rate'
      )
      ->addOption(
        'all',
        'a',
        InputOption::VALUE_NONE,
        'Sell all of the counter currency owned'
      );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $market = strtoupper($input->getArgument('market'));
        $math = new Math;

        // A buy requires a balance in the base currency.
        list($base, $counter) = explode('-', $market);

        $quantity = $math->float($input->getOption('quantity'));
        $rate = $math->float($input->getOption('rate'));
        $fast = $input->getOption('fast');
        $all = $input->getOption('all');
        $helper = $this->getHelper('question');

        if ($fast || !floatval($rate)) {
            $summary = $this->getApplication()
                          ->api()
                          ->getMarketSummary($market);

            if ($fast) {
              $rate = $math->float($summary[0]['Bid']);
            }
            else {
              $arguments = [];
              $arguments['command'] = 'market.show';
              $arguments['market'] = $market;

              $i = new ArrayInput($arguments);
              $this->getApplication()
                 ->find($arguments['command'])
                 ->run($i, $output);

              $question = new Question('What rate would you like to place the sell at? ');
              $rate = $math->float($helper->ask($input, $output, $question));
            }
        }

        if ($all || !floatval($quantity)) {
            // Check balances
            $balance = $this->getApplication()
                          ->api()
                          ->getBalance($counter);

            $avail = $math->float($balance['Available']);

            if (!floatval($avail)) {
                $output->writeln("<error>You have no $counter to sell</error>");
                return 1;
            }

            if ($all) {
              $quantity = $avail;
            }
            else {
              $output->writeln("You have a $counter balance of <info>$avail</info> available.");
              $question = new Question("How many $counter would you like to sell? ", 0);
              $quantity = $math->float($helper->ask($input, $output, $question));
            }

            if (floatval($balance['Available']) < floatval($quantity)) {
                $output->writeln("Cannot issue order: You don't have the correct balance.");
                $output->writeln("You need $quantity $counter to facilitate that order.");
                $output->writeln("You have {$balance['Available']} $counter available.");
                return;
            }
        }

        $output->writeln('Placing order...');

        $uuid = $this->getApplication()
              ->api()
              ->sellLimit($market, $quantity, $rate);

        sleep(1);

        $arguments = [];
        $arguments['command'] = 'order';
        $arguments['uuid'] = $uuid['uuid'];

        $i = new ArrayInput($arguments);
        $this->getApplication()
         ->find($arguments['command'])
         ->run($i, $output);
    }
}
