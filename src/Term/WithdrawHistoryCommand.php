<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Bittrex\Math\Math;

class WithdrawHistoryCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('withdraw.history')

       // the short description shown while running "php bin/console list"
       ->setDescription('Shows withdraw history')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Shows withdraw history')
       ->addArgument('currency', InputArgument::OPTIONAL, 'A currency, e.g. DGB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currency = $input->getArgument('currency');

        $history = $this->getApplication()
        ->api()
        ->getWithdrawalHistory($currency);

        // $table = new Table($output);
        // $table->setHeaders(array_keys($history[0]));
        // $table->setRows($history);
        // $table->render();

        $math = new Math();
        $totals = [];
        foreach ($history as $withdrawal) {
          if (!isset($totals[$withdrawal['Currency']])) {
            $totals[$withdrawal['Currency']] = 0;
          }
          $totals[$withdrawal['Currency']] = $math->add($withdrawal['Amount'], $totals[$withdrawal['Currency']]);
        }

        $rows = [];
        foreach ($totals as $curr => $total) {
          $rows[] = ["<info>$curr</info>", $math->format($total)];
        }

        $table = new Table($output);
        $table->setHeaders(['Currency', 'Amount']);
        $table->setRows($rows);
        $table->render();
    }
}
