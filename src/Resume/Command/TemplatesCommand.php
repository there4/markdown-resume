<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TemplatesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('templates')
            ->setDescription('List available templates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getApplication();
        $tplData = array('templates' => array());
        foreach (new \DirectoryIterator($this->app->templatePath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }
            $descriptionPath = $fileInfo->getPathname() . '/description.txt';
            print $descriptionPath . "\n";
            $tplData['templates'][] = (object) array(
                'name' => $fileInfo->getBasename(),
                'description' => file_exists($descriptionPath)
                    ? trim(file_get_contents($descriptionPath))
                    : 'No description available'
            );
        }
        $template = $this->app->twig->loadTemplate('templates.twig');
        $view = $template->render($tplData);
        $output->write($view, true, $this->app->outputFormat);
    }
}

/* End of file TemplatesCommand.php */
