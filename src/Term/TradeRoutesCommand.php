<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Bittrex\MarketPlace;

class TradeRoutesCommand extends TerminalCommand {
  protected function configure()
  {
    $this
       // the name of the command (the part after "bin/console")
       ->setName('trade.routes')

       // the short description shown while running "php bin/console list"
       ->setDescription('Plans trading routes they may be profitable.')

       // the full command description shown when running the command with
       // the "--help" option
       ->setHelp('Plans trading routes they may be profitable.');
   }

   protected function execute(InputInterface $input, OutputInterface $output)
   {
     $output->writeln("Building market summaries...");

     $markets = self::api()->getMarketSummaries();
     $spread = MarketPlace::buildSpread($markets);

     $helper = new QuestionHelper;
     $question = new Question('Which currency would you like to route? ', 'BTC');

     $question->setAutocompleterValues(array_keys($spread));

     $helper->ask($input, $output, $question);


    //  $table = new Table($output);
    //     $table
    //         ->setHeaders($headers)
    //         ->setRows($balances)
    //     ;
    //     $table->render();
   }
}
