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
        foreach (glob($this->app->templatePath . '/*', GLOB_ONLYDIR) as $dir) {
            $tplData['templates'][] = (object) array(
                'name' => basename($dir),
                'description' => file_exists($dir . '/description.txt')
                    ? trim(file_get_contents($dir . '/description.txt'))
                    : 'No description available'
            );
        }
        $template = $this->app->twig->loadTemplate('templates.twig');
        $view = $template->render($tplData);
        $output->write($view, true, $this->app->outputFormat);
    }
}

/* End of file TemplatesCommand.php */
