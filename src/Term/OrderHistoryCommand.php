<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Bittrex\Math\Math;

class OrderHistoryCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('history')

       // the short description shown while running "php bin/console list"
       ->setDescription('List your historic orders')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('List your historic orders')
       ->addArgument('market', InputArgument::OPTIONAL, 'Filter orders by market');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orders = $this->getApplication()
        ->api()
        ->getOrderHistory($input->getArgument('market'));

        if (!count($orders)) {
            $output->writeln('No orders found');

            return;
        }

        $math = new Math();

        $rows = [];
        foreach ($orders as &$order) {
            $order['Limit'] = number_format($order['Limit'], 9);
            $rows[] = [
         $order['OrderUuid'],
         $order['OrderType'],
         $order['Exchange'],
         $math->float($order['PricePerUnit']),
         $math->sub($order['Quantity'], $order['QuantityRemaining']),
         $math->float($order['Price']),
         $order['TimeStamp'],
       ];
        }
        $table = new Table($output);
        $table->setHeaders(['UUID', 'Type', 'Market', 'Rate', 'Quantity', 'Price', 'Timestamp']);
        $table->setRows($rows);
        $table->render();
    }
}
