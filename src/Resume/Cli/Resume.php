<?php
namespace FogBugz\Cli;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Yaml;
use There4\FogBugz;
use FogBugz\Cli;
use FogBugz\Command;

class Working extends Application
{

    public $recentCaseLimit = 10;

    public $configFile;

    public function initialize($configFile, $templatePath, $project)
    {
        $runSetup = false;
        $this->configFile = $configFile;

        // Add the composer information for use in version info and such.
        $this->project = $project;

        // Load our application config information
        if (file_exists($configFile)) {
            $this->config = Yaml::parse($configFile);
        } else {
            $runSetup = true;
            $this->config = $this->getDefaultConfig();
        }

        // https://github.com/symfony/Console/blob/master/Output/Output.php
        $this->outputFormat
            = $this->config['UseColor']
            ? OutputInterface::OUTPUT_NORMAL
            : OutputInterface::OUTPUT_PLAIN;

        // We do this now because we've loaded the project info from the composer file
        $this->setName($this->project->description);
        $this->setVersion($this->project->version);

        // Load our commands into the application
        $this->add(new Command\AssignCommand());
        $this->add(new Command\CasesCommand());
        $this->add(new Command\CloseCommand());
        $this->add(new Command\CurrentCommand());
        $this->add(new Command\EstimateCommand());
        $this->add(new Command\FiltersCommand());
        $this->add(new Command\LoginCommand());
        $this->add(new Command\LogoutCommand());
        $this->add(new Command\NoteCommand());
        $this->add(new Command\OpenCommand());
        $this->add(new Command\ParentCommand());
        $this->add(new Command\ReactivateCommand());
        $this->add(new Command\RecentCommand());
        $this->add(new Command\ReopenCommand());
        $this->add(new Command\ResolveCommand());
        $this->add(new Command\SearchCommand());
        $this->add(new Command\SelfUpdateCommand());
        $this->add(new Command\SetFilterCommand());
        $this->add(new Command\SetupCommand());
        $this->add(new Command\StartCommand());
        $this->add(new Command\StarCommand());
        $this->add(new Command\StopCommand());
        $this->add(new Command\UnstarCommand());
        $this->add(new Command\VersionCommand());
        $this->add(new Command\ViewCommand());

        // We'll use [Twig](http://twig.sensiolabs.org/) for template output
        $loader = new \Twig_Loader_Filesystem($templatePath);
        $this->twig = new \Twig_Environment(
            $loader,
            array(
                "cache"            => false,
                "autoescape"       => false,
                "strict_variables" => false // SET TO TRUE WHILE DEBUGGING
            )
        );

        // These are helpers that we use to format output on the cli: styling and padding and such
        $this->twig->addFilter('pad', new \Twig_Filter_Function("FogBugz\Cli\TwigFormatters::strpad"));
        $this->twig->addFilter('style', new \Twig_Filter_Function("FogBugz\Cli\TwigFormatters::style"));
        $this->twig->addFilter('repeat', new \Twig_Filter_Function("str_repeat"));
        $this->twig->addFilter('wrap', new \Twig_Filter_Function("wordwrap"));

        // If the config file is empty, run the setup script here
        // If the config file version is a different major number, run the setup script here
        $currentVersion = explode('.', $this->project->version);
        $configVersion  = explode('.', $this->config['ConfigVersion']);
        $majorVersionChange = $currentVersion[0] != $configVersion[0];
        // We need to be able to skip setup for the list and help
        $helpRequested = (
            empty($_SERVER['argv'][1]) ||
            ($_SERVER['argv'][1] == 'list') ||
            ($_SERVER['argv'][1] == 'help')
        );
        if (($runSetup || $majorVersionChange) && !$helpRequested) {
            $command = $this->find('setup');
            $arguments = array(
                'command' => 'setup'
            );
            $input = new ArrayInput($arguments);
            $command->run($input, new ConsoleOutput());
        }
    }

    public function getLongVersion()
    {
        return parent::getLongVersion().' by <comment>Craig Davis</comment>';
    }

    public function getDefaultConfig()
    {
        return array(
            'ConfigVersion' => '0.0.1',
            'UseColor'      => true,
            'Host'          => '',
            'User'          => '',
            'AuthToken'     => '',
            'RecentCases'   => array()
        );
    }

    public function getCurrent($user = '')
    {
        if ($user === '') {
            $user = $this->fogbugz->user;
        }
        $xml = $this->fogbugz->viewPerson(array('sEmail' => $user));

        return (int) $xml->people->person->ixBugWorkingOn;
    }

    public function getRecent()
    {
        return
            is_array($this->config['RecentCases'])
            ? $this->config['RecentCases']
            : array();
    }

    public function pushRecent($case, $title)
    {
        $recentCases = $this->getRecent();
        array_push(
            $recentCases,
            array(
                "id"    => $case,
                "title" => $title
            )
        );
        // Only keep the last x number of cases in the list
        $this->config['RecentCases'] = array_slice($recentCases, -1 * $this->recentCaseLimit);
        $this->saveConfig();

        return true;
    }

    public function saveConfig()
    {
        // the second param is the depth for starting yaml inline formatting
        $yaml = Yaml::dump($this->config, 2);

        return file_put_contents($this->configFile, $yaml);
    }

    public function registerStyles(&$output)
    {
        // https://github.com/symfony/Console/blob/master/Formatter/OutputFormatterStyle.php
        // http://symfony.com/doc/2.0/components/console/introduction.html#coloring-the-output
        //
        // * <info></info> green
        // * <comment></comment> yellow
        // * <question></question> black text on a cyan background
        // * <alert></alert> yellow
        // * <error></error> white text on a red background
        // * <fire></fire> red text on a yellow background
        // * <notice></notice> blue
        // * <heading></heading> black on white

        $style = new OutputFormatterStyle('red', 'yellow', array('bold'));
        $output->getFormatter()->setStyle('fire', $style);

        $style = new OutputFormatterStyle('blue', 'black', array());
        $output->getFormatter()->setStyle('notice', $style);

        $style = new OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('alert', $style);

        $style = new OutputFormatterStyle('white', 'black', array('bold'));
        $output->getFormatter()->setStyle('bold', $style);

        $style = new OutputFormatterStyle('black', 'white', array());
        $output->getFormatter()->setStyle('heading', $style);

        $style = new OutputFormatterStyle('blue', 'black', array('bold'));
        $output->getFormatter()->setStyle('logo', $style);

        return $output;
    }

    public function statusStyle($status)
    {
        switch (true) {
            case (strpos(strtolower($status), 'closed') === 0):
                return 'alert';
            case (strpos(strtolower($status), 'open') === 0):
            case (strpos(strtolower($status), 'active') === 0):
                return 'logo';
            // fallthrough to final return
        }

        return "info";
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        $this->registerStyles($output);

        // Did they supply a command name?
        $name = $this->getCommandName($input);
        if ($name) {
            // Does the command exist and is not ambiguous?
            try {
                $command = $this->find($name);
            } catch (\Exception $e) {
                exit($e->getMessage() . "\n");
            }

            // Does the command require authentication?
            if (property_exists($command, "requireAuth") && $command->requireAuth) {
                $simple_input = new ArgvInput(
                    array(
                        $_SERVER['argv'][0],
                        $_SERVER['argv'][1],
                        "--quiet"
                    )
                );
                $login = $this->find('login');
                $returnCode = $login->run($simple_input, $output);
            }
        }

        return parent::run($input, $output);
    }
}

/* End of file Working.php */
