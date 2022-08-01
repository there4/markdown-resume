<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sunra\PhpSimple\HtmlDomParser;

class PdfCommand extends HtmlCommand
{
    protected function configure()
    {
        $this
            ->setName('pdf')
            ->setDescription('Generate a PDF from a markdown file')
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
                'htmlonly',
                'H',
                InputOption::VALUE_NONE,
                'Only render interim HTML (don\'t run wkhtmltopdf)'
            )
            ->addOption(
                'keephtml',
                'k',
                InputOption::VALUE_NONE,
                'Keep interim HTML'
            )
            ->addOption(
                'pdfargs',
                'p',
                InputOption::VALUE_REQUIRED,
                'Passthrough arguments for wkhtmltopdf',
                '--dpi 300 -s Letter'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'The optional override of default filename to output to'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app    = $this->getApplication();
        $source       = $input->getArgument('source');
        $sourceName   = pathinfo($source, PATHINFO_FILENAME);
        $destination  = rtrim($input->getArgument('destination'), DIRECTORY_SEPARATOR);
        $template     = $input->getOption('template');
        $pdfSource    = join(DIRECTORY_SEPARATOR, array($destination, '.tmp_pdf_source.html'));
        $optFilename  = $input->getOption('output');
        $htmlonly  = $input->getOption('htmlonly');
        $keephtml  = $input->getOption('keephtml');
        $pdfargs  = $input->getOption('pdfargs');

        $destFilename = join(DIRECTORY_SEPARATOR, array($destination, pathinfo($source, PATHINFO_FILENAME) . '.pdf'));

        if ($optFilename) {
            $destFilename = $destination . DIRECTORY_SEPARATOR . $optFilename . '.pdf';
        } else {
            $destFilename = $destination . DIRECTORY_SEPARATOR . $sourceName . '.pdf';
        }
        // Make sure we've got out converter available
        exec('wkhtmltopdf -V', $results, $returnVal);
        if ($returnVal) {
            $output->writeln(
                "\n<error>Error:</error> Unable to locate wkhtmltopdf.\n" .
                "  Please make sure that it is installed and available in " .
                "your path. \n  For installation help, please read: " .
                "https://github.com/pdfkit/pdfkit/wiki/Installing-WKHTMLTOPDF \n\n",
                $this->app->outputFormat
            );

            return false;
        }

        $rendered = $this->generateHtml($source, $template, false);

        // The pdf needs some extra css rules, and so we'll add them here
        // to our html document
        $simpleDom = HtmlDomParser::str_get_html($rendered);
        $body = $simpleDom->find('body', 0);
        $body->setAttribute('class', $body->getAttribute('class') . ' pdf');
        $rendered = (string) $simpleDom;

        // Save to a temp destination for the pdf renderer to use
        file_put_contents($pdfSource, $rendered);

        // command that will be invoked to convert html to pdf
        $cmd = "wkhtmltopdf $pdfargs $pdfSource $destFilename";

        // Process the document with wkhtmltopdf
        if(!$htmlonly)
            exec($cmd);

        // Unlink the temporary file
        if(!($htmlonly || $keephtml))
            unlink($pdfSource);
        else
            $output->writeln(
                sprintf(
                    "Keeping interim HTML: <info>%s</info>",
                    $pdfSource
                ),
                $this->app->outputFormat
            );

        $output->writeln(
            sprintf(
                "Wrote pdf resume to: <info>%s</info>",
                $destFilename
            ),
            $this->app->outputFormat
        );

        return true;
    }
}

/* End of file PdfCommand.php */
