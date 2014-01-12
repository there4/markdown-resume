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
                'destination',
                InputArgument::REQUIRED,
                'Output html document'
            )
            ->addOption(
               'template',
               't',
               InputOption::VALUE_OPTIONAL,
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
        $this->app   = $this->getApplication();
        $source      = $input->getArgument('source');
        $destination = $input->getArgument('destination');
        $template    = $input->getOption('template');
        $refresh     = $input->getOption('refresh');

        // Check that the source file is sane
        if (!file_exists($source)) {
            $output->writeln(
                sprintf(
                    "<error>Unable to open source file: %s</error>",
                    $source
                ),
                $this->app->outputFormat
            );
            return false;
        }

        // Check that our template is sane, or set to the default one
        if (!$template) {
            $template = $this->app->defaultTemplate;
        }
        $templatePath = join(DIRECTORY_SEPARATOR, array(
            $this->app->templatePath, basename($template), '/index.html'
        ));
        if (!file_exists($templatePath)) {
            $output->writeln(
                sprintf(
                    "<error>Unable to open template file: %s</error>",
                    $templatePath
                ),
                $this->app->outputFormat
            );
            return false;
        }
    }
}

/* End of file HtmlCommand.php */
