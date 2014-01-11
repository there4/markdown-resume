<?php
namespace FogBugz\Command;

use FogBugz\Cli\AuthCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends AuthCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('version')
            ->setDescription('Show version information')
            ->requireAuth(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getApplication();
        $output->writeln($this->app->project->version, $this->app->outputFormat);
    }
}

/* End of file VersionCommand.php */
