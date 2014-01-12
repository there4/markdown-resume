<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\GlobAsset;
use Assetic\Filter;
use Michelf\MarkdownExtra;
use Michelf\SmartyPants;

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
                'Output destination folder'
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
        $this->app    = $this->getApplication();
        $source       = $input->getArgument('source');
        $destination  = trim($input->getArgument('destination'), DIRECTORY_SEPARATOR);
        $template     = $input->getOption('template');
        $refresh      = $input->getOption('refresh');
        $destFilename = join(DIRECTORY_SEPARATOR, array($destination, pathinfo($source, PATHINFO_FILENAME) . '.html'));

        $rendered = $this->generateHtml($source, $template, $refresh);
        file_put_contents($destFilename, $rendered);
        $output->writeln(
            sprintf(
                "Wrote resume to: <info>%s</info>",
                $destFilename
            ),
            $this->app->outputFormat
        );

        return true;
    }

    protected function generateHtml($source, $template, $refresh)
    {
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
        $templatePath = join(DIRECTORY_SEPARATOR, array($this->app->templatePath, basename($template)));
        $templateIndexPath = join(DIRECTORY_SEPARATOR, array($templatePath, 'index.html'));
        if (!file_exists($templateIndexPath)) {
            $output->writeln(
                sprintf(
                    "<error>Unable to open template file: %s</error>",
                    $templateIndexPath
                ),
                $this->app->outputFormat
            );

            return false;
        }

        // We build these into a single string so that we can deploy this resume as a
        // single file.
        $cssAssetSelector = join(DIRECTORY_SEPARATOR, array($templatePath, '/css/*.css'));
        $css = new AssetCollection(
            array(new GlobAsset($cssAssetSelector)),
            array(new Filter\LessphpFilter())
        );
        $style = $css->dump();

        $templateContent = file_get_contents($templateIndexPath);
        $resumeContent   = file_get_contents($source);

        // Process with Markdown, and then use SmartyPants to clean up punctuation.
        $resumeHtml = MarkdownExtra::defaultTransform($resumeContent);
        $resumeHtml = SmartyPants::defaultTransform($resumeHtml);

        // We'll construct the title for the html document from the h1 and h2 tags
        $simpleDom = new \simple_html_dom();
        $simpleDom->load($resumeHtml);
        $title = sprintf(
            '%s | %s',
            $simpleDom->find('h1', 0)->innertext,
            $simpleDom->find('h2', 0)->innertext
        );

        // We'll now render the Markdown into an html file with Mustache Templates
        $m = new \Mustache_Engine;
        $rendered = $m->render(
            $templateContent,
            array(
                'title'  => $title,
                'style'  => $style,
                'resume' => $resumeHtml,
                'reload' => $refresh
            )
        );

        return $rendered;
    }
}

/* End of file HtmlCommand.php */
