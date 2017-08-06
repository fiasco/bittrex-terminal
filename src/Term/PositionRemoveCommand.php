<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class PositionRemoveCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('position.remove')

       // the short description shown while running "php bin/console list"
       ->setDescription('Remove a position')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Remove a position')
       ->addArgument('position_id', InputArgument::REQUIRED, 'A currency code to take a position against');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     try {
       $store = $this->getApplication()->getStorage();
       $positions = $store->get('positions');
     }
     catch (\Exception $e) {
       $positions = [];
       $output->writeln("No positions found");
       return;
     }

     $idx = $input->getArgument('position_id');

     if (!isset($positions[$idx])) {
       $output->writeln("No such position exists: $idx");
     }

     unset($positions[$idx]);
     $store->set('positions', $positions);

     $output->writeln("Position removed: $idx");
   }
}
