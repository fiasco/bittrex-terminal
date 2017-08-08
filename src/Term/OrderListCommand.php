<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class OrderListCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('orders')

       // the short description shown while running "php bin/console list"
       ->setDescription('List your current orders')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('List your current orders');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $orders = $this->getApplication()
        ->api()
        ->getOpenOrders();

     if (!count($orders)) {
       $output->writeln("No open orders currently.");
       return;
     }

     $rows = [];
     foreach ($orders as &$order) {
       $order['Limit'] = number_format($order['Limit'], 9);
       $rows[] = [
         $order['OrderUuid'],
         $order['OrderType'],
         $order['Exchange'],
         $order['Limit'],
         $order['Quantity'],
         $order['Price'],
         $order['Opened']
       ];
     }
     $table = new Table($output);
     $table->setHeaders(['UUID', 'Type', 'Market', 'Rate', 'Quantity', 'Price', 'Opened']);
     $table->setRows($rows);
     $table->render();


   }
}
