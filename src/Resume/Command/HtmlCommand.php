<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HtmlCommand extends Command
{
    protected function configure()
    {
        // resume html source.md resume.html -template blockish -refresh
        $this
            ->setName('html')
            ->setDescription('Generate an HTML resume from a markdown file')
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
            )
            ->addOption(
               'refresh',
               'r',
               InputOption::VALUE_NONE,
               'If set, the html will include a meta command to refresh the ' .
               'document every 5 seconds.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}

/* End of file HtmlCommand.php */
