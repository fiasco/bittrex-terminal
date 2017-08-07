<?php

namespace Bittrex\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Bittrex\Application\KernelApplication;

class TerminalCommand extends Command
{
    protected function configure()
    {
      $this
         // the name of the command (the part after "bin/console")
         ->setName('shell')

         // the short description shown while running "php bin/console list"
         ->setDescription('Creates a trading terminal shell')

         // the full command description shown when running the command with
         // the "--help" option
         ->setHelp('This command allows you to interact with Bittrex via CLI');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('> ', '');
        $registry = $this->commandRegistry();


        $application = new KernelApplication();
        $application->setAutoExit(FALSE);

        $autocomplete = [];

        foreach ($registry as $command) {
          $instance = new $command();
          $autocomplete[] = $instance->getName();
          $application->add($instance);
        }

        $question->setAutocompleterValues($autocomplete);

        while (TRUE) {
          $in = $helper->ask($input, $output, $question);
          $in = explode(' ', $in);
          array_unshift($in, __FILE__);
          $in = new ArgvInput($in);
          try {
            $application->run($in, $output);
          }
          catch (\Exception $e) {
            $output->writeln($e->getMessage());
          }
        }
    }

    protected function commandRegistry()
    {
      return [
        'Bittrex\Term\WalletShowCommand',
        'Bittrex\Term\MarketShowCommand',
        'Bittrex\Term\OrderListCommand',
        'Bittrex\Term\OrderShowCommand',
        'Bittrex\Term\OrderCancelCommand',
        'Bittrex\Term\OrderHistoryCommand',
        'Bittrex\Term\OrderStatesCommand',
        'Bittrex\Term\BuyCommand',
        'Bittrex\Term\SellCommand',
        'Bittrex\Term\CoinAnalyseCommand',
      ];
    }
}

 ?>
