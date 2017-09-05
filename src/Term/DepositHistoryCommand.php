<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Bittrex\Math\Math;

class DepositHistoryCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('deposit.history')

       // the short description shown while running "php bin/console list"
       ->setDescription('Shows deposit history')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Shows a view of the market based on the given coin')
       ->addArgument('currency', InputArgument::OPTIONAL, 'A currency, e.g. DGB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currency = $input->getArgument('currency');

        $history = $this->getApplication()
        ->api()
        ->getDepositHistory($currency);

        $table = new Table($output);
        $table->setHeaders(array_keys($history[0]));
        $table->setRows($history);
        $table->render();
    }
}
