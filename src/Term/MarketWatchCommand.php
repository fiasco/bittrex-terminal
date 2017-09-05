<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Bittrex\Math\Math;
use Bittrex\MarketTicker;

class MarketWatchCommand extends Command
{
    protected function configure()
    {
        $this
       // the name of the command (the part after "bin/console")
       ->setName('watch')

       // the short description shown while running "php bin/console list"
       ->setDescription('Shows the state of a given market')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Shows the state of a given market')
       ->addArgument('market', InputArgument::REQUIRED, 'The market to show. E.g. BTC-ETH')
       ->addOption(
        'frequency',
        'f',
         InputOption::VALUE_OPTIONAL,
         'Update frequency in seconds. Defaults to 5.',
         5
       );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $math = new Math();
      $update = FALSE;
      $ticker = new MarketTicker();
      while (TRUE) {
        $markets = $this->getApplication()
          ->api()
          ->getMarketSummary($input->getArgument('market'));

        $ticker->update($markets[0]);


        $rows = [];
        foreach (array_keys($markets[0]) as $field) {
          if (in_array($field, ['Created', 'TimeStamp'])) {
            continue;
          }
          $rows[] = [$field, $ticker->display($field)];
        }

        if ($update) {
          $output->write("\x0D");

          // Erase the line
          $output->write("\x1B[2K");
          $output->write(str_repeat("\x1B[1A\x1B[2K", count($rows) + 3));
        }

        $table = new Table($output);
        $table->setRows($rows);
        $table->render();

        $timeframe = $math->format($math->mul($ticker->getAveragePeriod(), $input->getOption('frequency')), 0);

        $output->writeln("<comment>Movement based on a period of $timeframe seconds.</comment>");

        $update = TRUE;

        sleep($input->getOption('frequency'));
      }


        $rows = [];
        foreach ($markets[0] as $key => $value) {
            if (is_float($value)) {
                $value = number_format($value, 9);
            }
            $rows[] = [$key, $value];
        }

        $table = new Table($output);
        $table->setStyle('borderless');
        $table->setRows($rows);
        $table->render();

        return $markets[0];
    }
}
