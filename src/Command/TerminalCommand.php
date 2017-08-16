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
        $logo = <<<HTML

██████╗ ██╗████████╗████████╗██████╗ ███████╗██╗  ██╗
██╔══██╗██║╚══██╔══╝╚══██╔══╝██╔══██╗██╔════╝╚██╗██╔╝
██████╔╝██║   ██║      ██║   ██████╔╝█████╗   ╚███╔╝
██╔══██╗██║   ██║      ██║   ██╔══██╗██╔══╝   ██╔██╗
██████╔╝██║   ██║      ██║   ██║  ██║███████╗██╔╝ ██╗
╚═════╝ ╚═╝   ╚═╝      ╚═╝   ╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
████████╗███████╗██████╗ ███╗   ███╗██╗███╗   ██╗ █████╗ ██╗
╚══██╔══╝██╔════╝██╔══██╗████╗ ████║██║████╗  ██║██╔══██╗██║
   ██║   █████╗  ██████╔╝██╔████╔██║██║██╔██╗ ██║███████║██║
   ██║   ██╔══╝  ██╔══██╗██║╚██╔╝██║██║██║╚██╗██║██╔══██║██║
   ██║   ███████╗██║  ██║██║ ╚═╝ ██║██║██║ ╚████║██║  ██║███████╗
   ╚═╝   ╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝╚═╝╚═╝  ╚═══╝╚═╝  ╚═╝╚══════╝

HTML;

        $output->writeln("<info>$logo</info>");
        $output->writeln("Donations welcome");
        $output->writeln("  Bitcoin - <comment>1HR3w4Fme1uuUnfpF69aFeoBNSNm7otfFg</comment>");
        $output->writeln("  Neo - <comment>APcdVor1bZGPjAxpeSe3AVx3UeYCswSQ9B</comment>");
        $output->writeln("");

        $helper = $this->getHelper('question');
        $question = new Question('<comment>Bittrex</comment> > ', '');
        $registry = self::commandRegistry();

        $kernel = new KernelApplication("<info>$logo</info>", 'version 0.1');
        $kernel->setAutoExit(false);

        $history = [];

        foreach ($registry as $command) {
            $instance = new $command();
            $history[] = $instance->getName();
            $kernel->add($instance);
        }
        $question->setAutocompleterValues($history);

        $kernel->run(new ArgvInput([__FILE__, 'help']), $output);

        while (true) {
            $question->setAutocompleterValues($history);
            $in = $helper->ask($input, $output, $question);
            $history[] = $in;
            $in = explode(' ', $in);
            array_unshift($in, __FILE__);
            try {
                $kernel->run(new ArgvInput($in), $output);
                $output->writeln('<comment>Completed at '.date('c').'</comment>');
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
        }
    }

    static public function commandRegistry()
    {
        return [
        'Bittrex\Term\WalletShowCommand',
        'Bittrex\Term\MarketShowCommand',
        'Bittrex\Term\MarketAnalyseCommand',
        'Bittrex\Term\OrderListCommand',
        'Bittrex\Term\OrderShowCommand',
        'Bittrex\Term\OrderCancelCommand',
        'Bittrex\Term\OrderHistoryCommand',
        'Bittrex\Term\OrderStatesCommand',
        'Bittrex\Term\BuyCommand',
        'Bittrex\Term\SellCommand',
        'Bittrex\Term\CoinAnalyseCommand',
        'Bittrex\Term\LoopCommand',
        'Bittrex\Term\DepositHistoryCommand',
      ];
    }
}
