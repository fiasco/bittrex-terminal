<?php

namespace Bittrex\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class SetupCommand extends Command
{
    protected function configure()
    {
        $this
         // the name of the command (the part after "bin/console")
         ->setName('setup')

         // the short description shown while running "php bin/console list"
         ->setDescription('Sets up API keys to use terminal.')

         // the full command description shown when running the command with
         // the "--help" option
         ->setHelp('Sets up API keys to use terminal.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $creds = [];
        $helper = $this->getHelper('question');
        $question = new Question('<comment>What is your API Key? </comment>', '');
        $creds['Key'] = $helper->ask($input, $output, $question);

        $question = new Question('<comment>What is your API Secret? </comment>', '');
        $creds['Secret'] = $helper->ask($input, $output, $question);

        $credsFile = $_SERVER['HOME'] . '/.bittrex_terminal';

        file_put_contents($credsFile, json_encode($creds));
        $output->writeln("Written credentials to <comment>$credsFile</comment>.");
    }
}
