<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HtmlCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('html')
            ->setDescription('Generate an HTML resume from a markdown file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}

/* End of file HtmlCommand.php */
