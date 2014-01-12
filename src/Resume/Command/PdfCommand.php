<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PdfCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('pdf')
            ->setDescription('Generate a PDF from a markdown file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}

/* End of file PdfCommand.php */
