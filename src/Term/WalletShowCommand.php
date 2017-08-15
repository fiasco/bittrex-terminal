<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Bittrex\Math\Math;

class WalletShowCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('wallet')

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
                      ->api()
                      ->getBalances();

        //$output->writeln("Getting last orders...");

        $orders = $this->getApplication()
                      ->api()
                      ->getOrderHistory();

        //$output->writeln("Getting current market rates");

        $data = $this->getApplication()
                     ->api()
                     ->getMarketSummaries();

        $math = new Math();

        $markets = [];
        foreach ($data as $market) {
            $markets[$market['MarketName']] = $market;
        }

        // USD gives us our dollar value.
        $usd = $markets['USDT-BTC'];
        $usdTotal = 0.0;

        foreach ($balances as &$wallet) {
            $wallet['USD'] = '';
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
                $variation = $math->sub($market['High'], $market['Low']);
                if (floatval($variation)) {
                    $variation = $math->div($variation, $market['Low']);
                    $wallet['Spread'] = $math->float($math->mul($variation, 100), 2).'%';
                }

                // What the current sell rate is.
                $wallet['Ask'] = $math->float($market['Ask'], 9, '.', '');

                $direction = $math->sub($market['Ask'], $market['Low']);
                if (floatval($direction)) {
                    $direction = $math->div($direction, $market['Low'], $variation);
                    // $direction = $math->div($direction, $variation);
                    $direction = $math->mul($direction, 100);
                    $wallet['Direction'] = $math->float($direction, 2).'%';
                }

                // What the estimated USD value is.
                $value = $math->mul($wallet['Balance'], $market['Ask']);
                $value = $math->mul($value, $usd['Ask']);
                $usdTotal = $math->add($usdTotal, $value);
                $wallet['USD'] = $math->float($value, 2);

                $wallet['Volume'] = round($math->mul($market['Volume'], $market['Ask']));
            }
            // Because currency is based on BTC value, we have to handle this one.
            elseif ($wallet['Currency'] == 'BTC') {
                $value = $math->mul($wallet['Balance'], $usd['Ask']);
                $usdTotal = $math->add($usdTotal, $value);
                $wallet['USD'] = $math->float($value, 2);
                $wallet['Volume'] = $math->float($market['Volume'], 0);
            }
        }

        $rows = [];
        foreach ($balances as &$wallet) {
            if (!floatval($wallet['Balance'])) {
                continue;
            }
            $wallet['Portfolio %'] = $math->float($math->div($wallet['USD'], $usdTotal) * 100, 2).'%';
            $rows[] = $wallet;
        }

        $headers = array_keys($rows[0]);

        $table = new Table($output);
        $table
          ->setHeaders($headers)
          ->setRows($rows);
        $table->render();

        $output->writeln('<info>Total: $'.$math->format($usdTotal, 2).' USD</info>');
        $output->writeln('<info>Ask:</info> The current rate for the currency');
        $output->writeln('<info>Spread:</info> The difference between the 24hr high and low');
        $output->writeln("<info>Direction:</info> Where the current Ask sits in today's high low spread");
        $output->writeln('<info>Volume:</info> The size of the market measured in BTC');

        return $balances;
    }

    public function castFloatToString(&$item, $key)
    {
        if (is_float($item)) {
            $item = number_format($item, 9, '.', '');
        }
    }
}
