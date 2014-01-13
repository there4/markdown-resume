<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('stats')
            ->setDescription('Generate a word frequency analysis of your resume')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source markdown document'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app    = $this->getApplication();
        $source       = $input->getArgument('source');

        $text = file_get_contents($source);
        $text = $this->stripCommon($text);
        $analysis = array(
            'single' => $this->buildStats($text, 1),
            'double' => $this->buildStats($text, 2),
            'triple' => $this->buildStats($text, 3),
        );

        $template = $this->app->twig->loadTemplate('frequency.twig');
        $view = $template->render($analysis);
        $output->write($view, true, $this->app->outputFormat);

        return true;
    }

    private function stripCommon($content)
    {
        $content = preg_replace("/(,|\"|\.|\?|:|!|;|#|-|>|{|\*| - )/", " ", $content);
        $content = preg_replace("/\n/", " ", $content);
        $content = preg_replace("/\s\s+/", " ", $content);
        $content = explode(" ", $content);

        return $content;
    }

    // source: https://github.com/benbalter/Frequency-Analysis/blob/master/frequency-analysis.php
    private function buildStats($input, $num)
    {
        $results = array();

        foreach ($input as $key => $word) {
            $phrase = '';

            //look for every n-word pattern and tally counts in array
            for ($i=0; $i < $num; $i++) {
                if ($i != 0) {
                    $phrase .= ' ';
                }
                if (!empty($input[$key+$i])) {
                    $phrase .= strtolower($input[$key+$i]);
                }
            }
            if (!isset( $results[$phrase])) {
                $results[$phrase] = 1;
            } else {
                $results[$phrase]++;
            }
        }
        if ($num == 1) {
            //clean boring words
            $a = explode(
                " ",
                "the of and to a in that it is was i for on you he be with as by " .
                "at have are this not but had his they from she which or we an there " .
                "her were one do been all their has would will what if can when so my"
            );
            foreach ($a as $banned) {
                unset($results[$banned]);
            }
        }

        //sort, clean, return
        array_multisort($results, SORT_DESC);
        unset($results[""]);

        return $results;
    }
}

/* End of file StatsCommand.php */
