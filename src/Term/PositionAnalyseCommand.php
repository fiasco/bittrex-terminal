<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Bittrex\MarketPlace;

class PositionAnalyseCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('position.analyse')

       // the short description shown while running "php bin/console list"
       ->setDescription('Identify net profitiable moves for this position.')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Identify net profitiable moves for this position.')
       ->addArgument('position_id', InputArgument::REQUIRED, 'A currency code to take a position against');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     try {
       $store = $this->getApplication()->getStorage();
       $positions = $store->get('positions');
     }
     catch (\Exception $e) {
       $output->writeln("No positions available");
       return;
     }

     $idx = $input->getArgument('position_id');

     if (!isset($positions[$idx])) {
       $output->writeln("No such position: $idx.");

       $arguments['command'] = 'position.list';
       $input = new ArrayInput($arguments);
       return $this->getApplication()
          ->find('position.list')
          ->run($input, $output);
     }
     $position = $positions[$idx];

     if ($position['Currency'] == $position['CurrentCurrency'] && $position['Quantity'] < $position['CurrentQuantity']) {
       $output->writeln("Current position has a net increase from starting position. No analysis required.");
       return;
     }
     try {
       $this->analyse($position);
     }
     catch (\Exception $e) {
       $output->writeln($e->getMessage());
     }
   }

   /**
    * Plan a route through the currencies that creates a net gain position.
    */
   protected function analyse($position)
   {
     $marketplace = new MarketPlace($this->getApplication()->getStorage()->get('api'));
     $spread = $marketplace->getCurrencySpread($position['CurrentCurrency']);

     // If this currency only has a single market then we don't really have
     // a postion we can route. This position is done.
     if (count($spread) == 1 && $position['Currency'] == $position['CurrentCurrency']) {
       $market = array_shift($spread);
       throw new \Exception("{$market['Market']} is the only market available. No routable position.");
     }
   }
}
