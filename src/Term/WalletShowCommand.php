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
     //$output->writeln("Getting balances...");
     $balances = $this->getApplication()
                      ->getStorage()
                      ->get('api')
                      ->getBalances();

     //$output->writeln("Getting last orders...");

     $orders = $this->getApplication()
                      ->getStorage()
                      ->get('api')
                      ->getOrderHistory();

     //$output->writeln("Getting current market rates");

     $data = $this->getApplication()
                     ->getStorage()
                     ->get('api')
                     ->getMarketSummaries();

     $markets = [];
     foreach ($data as $market) {
       $markets[$market['MarketName']] = $market;
     }

     // USD gives us our dollar value.
     $usd = $markets['USDT-BTC'];
     $usdTotal = 0.0;

     foreach ($balances as &$wallet) {
       $wallet['Value'] = '';
       $wallet['Balance'] = number_format($wallet['Balance'], 9, '.', '');
       $wallet['Available'] = number_format($wallet['Available'], 9, '.', '');
       unset($wallet['Pending'], $wallet['CryptoAddress']);
       $wallet['Ask'] = '';
       $wallet['Spread'] = '';
       $wallet['Direction'] = '';
       $wallet['Volume'] = '';

       array_walk($wallet, [$this, 'castFloatToString']);

       // If there is a market avalible in this currency with BTC, generate
       // some stats.
       if (isset($markets["BTC-{$wallet['Currency']}"])) {
         $market = $markets["BTC-{$wallet['Currency']}"];

         array_walk($market, [$this, 'castFloatToString']);

         // Spread: How much the currency moves in a 24-hr period.
         $variation = bcsub($market['High'], $market['Low'], 9);
         if (floatval($variation)) {
           $variation = bcdiv($variation, $market['Low'], 9);
           $wallet['Spread'] = bcmul($variation, 100, 1) . '%';
         }

         // What the current sell rate is.
         $wallet['Ask'] = number_format($market['Ask'], 9, '.', '');

         $direction = bcsub($market['Ask'], $market['Low'], 9);
         if (floatval($direction)) {
           $direction = bcdiv($direction, $market['Low'], 9);
           $direction = bcdiv($direction, $variation, 9);
           $direction = bcmul($direction, 100, 2);
           $wallet['Direction'] = $direction . '%';
         }

         // What the estimated USD value is.
         $value = bcmul($wallet['Balance'], $market['Ask'], 9);
         $value = bcmul($value, $usd['Ask'], 2);
         $usdTotal = bcadd($usdTotal, $value, 4);
         $wallet['Value'] = '$' . number_format($value,2);

         $wallet['Volume'] = number_format(bcmul($market['Volume'], $market['Ask']));
       }
       // Because currency is based on BTC value, we have to handle this one.
       elseif ($wallet['Currency'] == 'BTC') {
         $value = bcmul($wallet['Balance'], $usd['Ask'], 2);
         $usdTotal = bcadd($usdTotal, $value, 4);
         $wallet['Value'] = '$' . number_format($value,2);
         $wallet['Volume'] = number_format(floor($market['Volume']));
       }
     }

     $headers = array_values($balances);
     $headers = array_keys($headers[0]);

     $table = new Table($output);
     $table
          ->setHeaders($headers)
          ->setRows($balances);
     $table->render();

     $output->writeln("<info>Total: \$" . number_format($usdTotal, 2) . " USD</info>");
     $output->writeln("<info>Ask:</info> The current rate for the currency");
     $output->writeln("<info>Spread:</info> The difference between the 24hr high and low");
     $output->writeln("<info>Direction:</info> Where the current Ask sits in today's high low spread");
     $output->writeln("<info>Volume:</info> The size of the market measured in BTC");
     return $balances;
   }

   public function castFloatToString(&$item, $key)
   {
     if (is_float($item)) {
       $item = number_format($item, 9, '.', '');
     }
   }
}
