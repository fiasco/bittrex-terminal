<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class OrderCancelCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('cancel')

       // the short description shown while running "php bin/console list"
       ->setDescription('Cancel an order')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Cancel an order')
       ->addArgument('uuid', InputArgument::REQUIRED, 'The order uuid to cancel');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $orders = $this->getApplication()
        ->api()
        ->cancel($input->getArgument('uuid'));

     $output->writeln("Order cancelled.");
   }
}
