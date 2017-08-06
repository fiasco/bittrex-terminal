<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class PositionListCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('position.list')

       // the short description shown while running "php bin/console list"
       ->setDescription('List taken positions')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('List taken positions')
       ;
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     try {
       $store = $this->getApplication()->getStorage();
       $positions = $store->get('positions');
     }
     catch (\Exception $e) {
       $output->writeln('No curreny positions taken');
       return;
     }
     $table = new Table($output);
     $table->setHeaders(['ID', 'Currency', 'Quantity', 'CurrentCurrency', 'CurrencyQuantity', 'Date'])
           ->setRows($positions);
    $table->render();
   }
}
