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
        $balances = $this->getApplication()
                      ->api()
                      ->getBalances();

        $data = $this->getApplication()
                     ->api()
                     ->getMarketSummaries();

        $math = new Math();

        $markets = [];
        foreach ($data as $market) {
            $markets[$market['MarketName']] = $market;
        }

        foreach ($balances as &$wallet) {
            $wallet['Balance'] = $math->format($wallet['Balance']);
            $wallet['Available'] = $math->format($wallet['Available']);
            $wallet['Est. BTC'] = 0;
            unset($wallet['Pending'], $wallet['CryptoAddress']);

            switch ($wallet['Currency']) {
              case 'BTC':
                $wallet['Est. BTC'] = $wallet['Balance'];
                break;

              case 'USDT':
                $market = $markets['USDT-BTC'];
                $wallet['Est. BTC'] = $math->div($wallet['Balance'], $market['Last']);
                break;

              default:
                $market = 'BTC-' . $wallet['Currency'];
                if (!isset($markets[$market])) {
                  continue;
                }
                $market = $markets[$market];
                $wallet['Est. BTC'] = $math->mul($wallet['Balance'], $market['Last']);
                break;
            }
        }

        $estBTC = 0;

        $rows = [];
        foreach ($balances as &$wallet) {
            if (!floatval($wallet['Balance'])) {
                continue;
            }
            $wallet['Est. USD'] = $math->format($math->mul($markets['USDT-BTC']['Last'], $wallet['Est. BTC']), 2);
            $estBTC = $math->add($estBTC, $wallet['Est. BTC']);
            // $wallet['Portfolio %'] = $math->float($math->div($wallet['USD'], $usdTotal) * 100, 2).'%';
            $rows[] = $wallet;
        }

        $headers = array_keys($rows[0]);

        $this->formatColumns($rows);

        $table = new Table($output);
        $table
          ->setHeaders($headers)
          ->setRows($rows);
        $table->render();

        $usd = $math->mul($markets['USDT-BTC']['Last'], $estBTC);
        $output->writeln('<info>Est. Total: '.$math->format($estBTC, 9).' BTC / $' . $math->format($usd,2) . ' USD</info>');

        return $balances;
    }

    protected function formatColumns(&$rows) {
      $colWdith = [];
      foreach ($rows as $row) {
        foreach ($row as $column => $value) {
          if (!isset($colWidth[$column])) {
            $colWidth[$column] = strlen($value);
            continue;
          }
          $colWidth[$column] = max($colWidth[$column], strlen($value), strlen($column));
        }
      }

      foreach ($rows as &$row) {
        foreach ($row as $column => $value) {
          if ($column == 'Currency') {
            continue;
          }
          $row[$column] = str_pad($value, $colWidth[$column], ' ', STR_PAD_LEFT);
        }
      }
    }
}
