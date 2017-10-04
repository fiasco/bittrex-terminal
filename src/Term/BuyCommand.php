<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Bittrex\Order;
use Bittrex\Math\Math;

class BuyCommand extends Command
{
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
     )
     ->addOption(
       'fast',
       'f',
       InputOption::VALUE_NONE,
       'Buy at the selling rate'
     )
     ->addOption(
       'confirm',
       'c',
       InputOption::VALUE_NONE,
       'Wait to confirm order or present option to cancel'
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
        $helper = $this->getHelper('question');

        if ($fast || !floatval($rate)) {
            $summary = $this->getApplication()
                          ->api()
                          ->getMarketSummary($market);

            if ($fast) {
              $rate = $math->float($summary[0]['Ask']);
            }
            else {
              $arguments = [];
              $arguments['command'] = 'market.show';
              $arguments['market'] = $market;

              $i = new ArrayInput($arguments);
              $this->getApplication()
                 ->find($arguments['command'])
                 ->run($i, $output);

              $question = new Question('What rate would you like to place the buy at? ');
              $rate = $math->float($helper->ask($input, $output, $question));
            }
        }

        if (!floatval($quantity)) {
            // Check balances
            $balance = $this->getApplication()
                          ->api()
                          ->getBalance($base);

            $avail = $math->float($balance['Available']);

            if (!floatval($avail)) {
                $output->writeln("<error>You have no $base to sell</error>");
                return 1;
            }

            $output->writeln("You have a $base balance of <info>$avail</info> available.");
            $coin = Order::calculateTransactableVolume($balance['Available']);
            $units = $math->div($coin, $rate);
            $output->writeln("You can buy a maximum of $units $counter @ $rate $base.");

            $question = new Question("How many $counter would you like to buy? ", 0);
            $quantity = $helper->ask($input, $output, $question);

            // if ($balance['Available'] < $price) {
            //     $output->writeln("Cannot issue order: You don't have the correct balance.");
            //     $output->writeln("You need $price $base to facilitate that order.");
            //     $output->writeln("You have {$balance['Available']} $base available.");
            //
            //     return;
            // }
        }

        $order = Order::buyLimit($market, $quantity, $rate);
        $price = $order->getProperty('Price');

        if (empty(floatval($quantity)) || empty(floatval($rate))) {
          $output->writeln("Order incomplete. Backing out.");
          return;
        }

        $output->write('Placing order...');

        $uuid = $this->getApplication()
              ->api()
              ->buyLimit($market, $quantity, $rate);

        $confirmed = !$input->getOption('confirm');

        $timeout = 5;

        while (!$confirmed) {
          $output->write(' waiting...');
          $order = $this->getApplication()
            ->api()
            ->getOrder($uuid['uuid']);

          $confirmed = !empty($order['Closed']);

          if (!$timeout) {
            $question = new ConfirmationQuestion("Order is taking a long time to fill. Cancel order? ");
            if ($helper->ask($input, $output, $question)) {
              $this->getApplication()
                ->api()
                ->cancel($uuid['uuid']);
              break;
            }
          }

          $output->write('.');
          sleep(2);
        }

        $output->writeln('');

        $arguments = [];
        $arguments['command'] = 'order';
        $arguments['uuid'] = $uuid['uuid'];

        $i = new ArrayInput($arguments);
        $market = $this->getApplication()
       ->find($arguments['command'])
       ->run($i, $output);
    }
}
