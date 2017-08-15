<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Bittrex\Trade\Router;
use Bittrex\Math\Math;

class LoopCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('loop')

       // the short description shown while running "php bin/console list"
       ->setDescription('Looks for amplifing loops in the trading market')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Looks for amplifing loops in the trading market')
       ->addArgument('currency', InputArgument::OPTIONAL, 'Refine routing to a single currency in your wallet');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $balances = $this->getApplication()
        ->api()
        ->getBalances();

        $markets = $this->getApplication()
        ->api()
        ->getMarketSummaries();

        $currency = $input->getArgument('currency') ? strtoupper($input->getArgument('currency')) : FALSE;

        $streams = [];

        foreach ($balances as $wallet) {
            if (!floatval($wallet['Available'])) {
                continue;
            }
            if ($currency && $currency != $wallet['Currency']) {
              continue;
            }
            $output->writeln("<comment>Routing {$wallet['Currency']}</comment>");
            $router = new Router($wallet['Currency'], $wallet['Available'], $markets);

            // Filter down to profitable routes. If any.
            $routes = array_filter($router->getRoutes(), function ($route) use ($wallet) {
              return $route->count() == 5 && $route->route($wallet['Available']) > $route->getInitialAmount();
            });

            $streams = array_merge($streams, $routes);
        }

        // Sort routes by profitability.
        usort($streams, function ($a, $b) {
          if ($a->getAmount() == $b->getAmount()) {
            return 0;
          }
          return $a->getAmount() > $b->getAmount() ? -1 : 1;
        });

        $streams = array_slice($streams, 0, 3);

        foreach ($streams as $route) {
            $output->writeln('<info>' . $route->getTitle() . '</info>');
            $table = new Table($output);
            $table->setRows([
              ['In', $route->getInitialAmount()],
              ['Out', $route->getAmount()],
              ['ROI', $route->getROI()]
            ]);
            $table->render();

            $rows = [];
            foreach ($route->getOrders() as $order) {
                $rows[] = [
                  $order->getProperty('Exchange'),
                  $order->getProperty('Type'),
                  $order->getProperty('Limit'),
                  $order->getProperty('Quantity'),
                  $order->getProperty('Price'),
                ];
            }
            $table = new Table($output);
            $table->setHeaders(['Exchange', 'Type', 'Limit', 'Quantity', 'Price']);
            $table->setRows($rows);
            $table->render();
        }
    }



}
