<?php

namespace Bittrex\Term;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class PollingCommand extends Command
{

    protected function configure()
    {
      $this->addOption(
        'refresh-rate',
        '-r',
         InputOption::VALUE_NONE,
         'The frequency to re-render display'
       );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $model = (object) [];
      $model->count = 0;

      do {
        $this->sync($model, $input, $output);
        $this->process($model, $input, $output);
        $this->clear($output);
        $this->render($model, $input, $output);
        sleep(1);
        $model->count++;
      }
      while ($input->getOption('refresh-rate'));
    }

    abstract protected function sync($model, $input, $output);
    abstract protected function process($model, $input, $output);
    abstract protected function render($model, $input, $output);

    protected function clear($output)
    {
      $height = exec('tput lines');
      // Move the cursor to the beginning of the line
      $output->write("\x0D");
      // Erase the line
      $output->write("\x1B[2K");
      $output->write(str_repeat("\x1B[1A\x1B[2K", $height));
    }
}
