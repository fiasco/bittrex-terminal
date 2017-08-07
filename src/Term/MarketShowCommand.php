<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class MarketShowCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('market.show')

       // the short description shown while running "php bin/console list"
       ->setDescription('Shows the state of a given market')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Shows the state of a given market')
       ->addArgument('market', InputArgument::REQUIRED, 'The market to show. E.g. BTC-ETH');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $markets = $this->getApplication()
        ->api()
        ->getMarketSummary($input->getArgument('market'));

     $rows = [];
     foreach ($markets[0] as $key => $value) {
       if (is_float($value)) {
         $value = number_format($value, 9);
       }
       $rows[] = [$key, $value];
     }

     $table = new Table($output);
     $table->setRows($rows);
     $table->render();

     return $markets[0];
   }
}
