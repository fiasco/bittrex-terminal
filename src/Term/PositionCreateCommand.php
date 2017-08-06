<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Bittrex\Position;

class PositionCreateCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('position.create')

       // the short description shown while running "php bin/console list"
       ->setDescription('Take a position against a currency')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Take a position against a currency')
       ->addArgument('currency', InputArgument::REQUIRED, 'A currency code to take a position against')
       ->addArgument('quantity', InputArgument::REQUIRED, 'The quantity (units) to hold in the position');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     try {
       $store = $this->getApplication()->getStorage();
       $positions = $store->get('positions');
     }
     catch (\Exception $e) {
       $positions = [];
     }

     $positions = Position::loadPositions($positions);

     $idx = count($positions) ? max(array_keys($positions)) : 0;
     $idx++;

     $positions[$idx] = new Position([
       'ID' => $idx,
       'Currency' => $input->getArgument('currency'),
       'Quantity' => $input->getArgument('quantity'),
       'CurrentCurrency' => $input->getArgument('currency'),
       'CurrentQuantity' => $input->getArgument('quantity'),
       'Created' => date('c'),
     ]);

     $store->set('positions', $positions);
     $output->writeln("<info>Position created</info>");
   }
}
