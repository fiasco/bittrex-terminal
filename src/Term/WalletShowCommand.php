<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;

class WalletShowCommand extends Command {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('wallet.show')

       // the short description shown while running "php bin/console list"
       ->setDescription('Shows the balances of all wallets used.')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Shows the balances of all wallets used.');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $balances = $this->getApplication()
                      ->getStorage()
                      ->get('api')
                      ->getBalances();

     foreach ($balances as &$wallet) {
       $wallet['Balance'] = number_format($wallet['Balance'], 9);
       $wallet['Available'] = number_format($wallet['Available'], 9);
       $wallet['Pending'] = number_format($wallet['Pending'], 9);
     }

     $headers = array_values($balances);
     $headers = array_keys($headers[0]);

     $table = new Table($output);
        $table
            ->setHeaders($headers)
            ->setRows($balances)
        ;
        $table->render();
      return $balances;
   }
}
