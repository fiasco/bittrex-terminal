<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class OrderShowCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('order')

       // the short description shown while running "php bin/console list"
       ->setDescription('Show an order')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Show an order')
       ->addArgument('uuid', InputArgument::REQUIRED, 'The order uuid to show');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $order = $this->getApplication()
        ->api()
        ->getOrder($input->getArgument('uuid'));

     $rows = [];
     foreach ($order as $key => $value) {
       if (is_float($value)) {
         $value = number_format($value, 9);
       }
       $rows[] = [$key, $value];
     }

     $table = new Table($output);
     $table->setRows($rows);
     $table->render();
     return $order;
   }
}
