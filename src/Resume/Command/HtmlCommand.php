<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter;
use Michelf\MarkdownExtra;
use Michelf\SmartyPants;
use Sunra\PhpSimple\HtmlDomParser;

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
                InputOption::VALUE_REQUIRED,
                'Which of the templates to use'
            )
            ->addOption(
                'refresh',
                'r',
                InputOption::VALUE_REQUIRED,
                'Regenerate the html and include a meta command to refresh the ' .
                'document every periodically. Measured in seconds.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app    = $this->getApplication();
        $source       = $input->getArgument('source');
        $destination  = rtrim($input->getArgument('destination'), DIRECTORY_SEPARATOR);
        $template     = $input->getOption('template');
        $refresh      = $input->getOption('refresh');
        $destFilename = join(DIRECTORY_SEPARATOR, array($destination, pathinfo($source, PATHINFO_FILENAME) . '.html'));

        $rendered = $this->generateHtml($source, $template, $refresh);
        file_put_contents($destFilename, $rendered);
        $output->writeln(
            sprintf(
                'Wrote resume to: <info>%s</info>',
                $destFilename
            ),
            $this->app->outputFormat
        );

        return true;
    }

    protected function generateContent($templatePath, $contentType)
    {
        // We build these into a single string so that we can deploy this resume as a
        // single file.
        $assetPath = join(DIRECTORY_SEPARATOR, array($templatePath, $contentType));

        if (!file_exists($assetPath)) {
            return '';
        }

        $assets = array();

        // Our PHAR deployment can't handle the GlobAsset typically used here
        foreach (new \DirectoryIterator($assetPath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                continue;
            }
            array_push($assets, new FileAsset($fileInfo->getPathname()));
        }

        $collection = new AssetCollection(
            $assets
        );

        switch ($contentType) {
            case 'css':
                $collection->ensureFilter(new Filter\LessphpFilter());
                break;
        }

        return $collection->dump();
    }

    protected function generateHtml($source, $template, $refresh)
    {
        // Check that the source file is sane
        if (!file_exists($source)) {
            throw new \Exception("Unable to open source file: $source");
        }

        // Check that our template is sane, or set to the default one
        if (!$template) {
            $template = $this->app->defaultTemplate;
        }
        $templatePath = join(DIRECTORY_SEPARATOR, array($this->app->templatePath, basename($template)));
        $templateIndexPath = join(DIRECTORY_SEPARATOR, array($templatePath, 'index.html'));

        if (!file_exists($templateIndexPath)) {
            throw new \Exception("Unable to open template file: $templateIndexPath");
        }

        $style = $this->generateContent($templatePath, 'css');

        $links = $this->generateContent($templatePath, 'links');

        $templateContent = file_get_contents($templateIndexPath);
        $resumeContent   = file_get_contents($source);

        // Process with Markdown, and then use SmartyPants to clean up punctuation.
        $resumeHtml = MarkdownExtra::defaultTransform($resumeContent);
        $resumeHtml = SmartyPants::defaultTransform($resumeHtml);

        // Construct the title for the html document from the h1 and h2 tags
        $simpleDom = HtmlDomParser::str_get_html($resumeHtml);
        $title = sprintf(
            '%s | %s',
            $simpleDom->find('h1', 0)->innertext,
            $simpleDom->find('h2', 0)->innertext
        );

        // Render the Markdown into an html file with Mustache Templates
        $m = new \Mustache_Engine;
        $rendered = $m->render($templateContent, array(
            'title'        => $title,
            'style'        => $style,
            'links'        => $links,
            'resume'       => $resumeHtml,
            'reload'       => (bool) $refresh,
            'refresh_rate' => $refresh
        ));

        return $rendered;
    }
}

/* End of file HtmlCommand.php */
