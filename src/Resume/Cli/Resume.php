<?php
namespace Resume\Cli;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Resume\Cli;
use Resume\Command;

class Resume extends Application
{
    public $defaultTemplate = 'modern';

    public $recentCaseLimit = 10;

    public function initialize($templatePath, $consoleTemplatePath, $project)
    {
        $runSetup = false;

        // Add the composer information for use in version info and such.
        $this->project = $project;

        // The absolute path to the html output templates
        $this->templatePath = $templatePath;

        // https://github.com/symfony/Console/blob/master/Output/Output.php
        // the alternative is OutputInterface::OUTPUT_PLAIN;
        $this->outputFormat = OutputInterface::OUTPUT_NORMAL;

        // Exits on missing dependencies
        $this->checkDependencies();

        // We do this now because we've loaded the project info from the composer file
        $this->setName($this->project->description);
        $this->setVersion($this->project->version);

        // Load our commands into the application
        $this->add(new Command\HtmlCommand());
        $this->add(new Command\PdfCommand());
        $this->add(new Command\SelfUpdateCommand());
        $this->add(new Command\StatsCommand());
        $this->add(new Command\TemplatesCommand());
        $this->add(new Command\VersionCommand());

        // We'll use [Twig](http://twig.sensiolabs.org/) for template output
        $loader = new \Twig_Loader_Filesystem($consoleTemplatePath);
        $this->twig = new \Twig_Environment(
            $loader,
            array(
                "cache"            => false,
                "autoescape"       => false,
                "strict_variables" => false // SET TO TRUE WHILE DEBUGGING
            )
        );

        // These are helpers that we use to format output on the cli: styling and padding and such
        $this->twig->addFilter('pad', new \Twig_Filter_Function("Resume\Cli\TwigFormatters::strpad"));
        $this->twig->addFilter('style', new \Twig_Filter_Function("Resume\Cli\TwigFormatters::style"));
        $this->twig->addFilter('repeat', new \Twig_Filter_Function("str_repeat"));
        $this->twig->addFilter('wrap', new \Twig_Filter_Function("wordwrap"));
    }

    public function getLongVersion()
    {
        return parent::getLongVersion().' by <comment>Craig Davis</comment>';
    }

    public function checkDependencies()
    {
        $output = new ConsoleOutput();
        if (!extension_loaded('mbstring')) {
            $output->writeln(
                "\n<error>Missing Dependency: Please install the Multibyte String Functions.</error>\n" .
                "More help: http://www.php.net/manual/en/mbstring.installation.php\n",
                $this->outputFormat
            );
            exit(1);
        }
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
        }

        return parent::run($input, $output);
    }
}

/* End of file Resume.php */
