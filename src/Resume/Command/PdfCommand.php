<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PdfCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('pdf')
            ->setDescription('Generate a PDF from a markdown file')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source markdown document'
            )
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Output html document'
            )
            ->addOption(
               'template',
               't',
               InputOption::VALUE_NONE,
               'Which of the templates to use'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}

/* End of file PdfCommand.php */
