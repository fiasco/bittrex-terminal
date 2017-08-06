<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class OrderHistoryCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('order.history')

       // the short description shown while running "php bin/console list"
       ->setDescription('List your historic orders')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('List your historic orders');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $orders = $this->getApplication()
        ->getStorage()
        ->get('api')
        ->getOrderHistory();

     if (!count($orders)) {
       $output->writeln("No orders found");
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
         $order['TimeStamp']
       ];
     }
     $table = new Table($output);
     $table->setHeaders(['UUID', 'Type', 'Market', 'Rate', 'Quantity', 'Price', 'Timestamp']);
     $table->setRows($rows);
     $table->render();
   }
}
