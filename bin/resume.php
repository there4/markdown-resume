<?php
define('APPLICATION_BASE_PATH', realpath(__DIR__ . '/..'));

require APPLICATION_BASE_PATH . '/vendor/autoload.php';
require APPLICATION_BASE_PATH . '/vendor/Mustache/Mustache.php';
require APPLICATION_BASE_PATH . '/vendor/smartypants/smartypants.php';
require APPLICATION_BASE_PATH . '/vendor/markdown-extra/markdown.php';
require APPLICATION_BASE_PATH . '/vendor/lessphp/lessc.inc.php';
require APPLICATION_BASE_PATH . '/vendor/simpledom/simple_html_dom.php';

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\Filter;

// Application defaults
$config = (object) array(
    "source"   => "",
    "template" => "modern",
    "refresh"  => false,
    "pdf"      => false
);

// Command line arguments to populate the config
$opts  = array(
    "s:" => "source:",   // source
    "t:" => "template:", // template
    "r"  => "refresh",    // refresh
    "p"  => "pdf"         // pdf output
);

// Fetch the options from the command line arguments
$options = getopt(implode("", array_keys($opts)), array_values($opts));

// Consolidate the short and long options into the config array
// Make sure that boolean options are set appropriately.
foreach ($opts as $short => $long) {
    $isBool = (substr($short, -1, 1) !== ":");
    $short = trim($short, ":");
    $long  = trim($long, ":");

    if (isset($options[$short])) {
      $config->$long = $isBool ? true : $options[$short];
    }
    else if (isset($options[$long])) {
      $config->$long = $isBool ? true : $options[$long];
    }
}

if (empty($config->source)) {
    exit("Please specify a source document: bin/resume.php -s resume/resume.pdf\n");
}

$basename      = pathinfo($config->source, PATHINFO_FILENAME);
$template_path = realpath(__DIR__ . '/../templates/' . $config->template);
$pdf_source    = './output/' . $basename . '-pdf.html';
$output        = './output/' . $basename . '.html';
$pdf_output    = './output/' . $basename . '.pdf';

if (!file_exists($config->source)) {
  exit("Please specify a valid source file.\n");
}

if (!file_exists($template_path)) {
  // TODO: List templates
  exit("Please specify a valid template.\n");
}

// We build these into a single string so that we can deploy this resume as a
// single file.
$css = new AssetCollection(
    array(new GlobAsset($template_path . '/css/*.css')),
    array(new Filter\LessphpFilter())
);
$style = $css->dump();

$template = file_get_contents($template_path . '/index.html');
$resume   = file_get_contents($config->source);

// Process with Markdown, and then use SmartyPants to clean up punctuation.
$resume = SmartyPants(Markdown($resume));

// We'll construct the title for the html document from the h1 and h2 tags
$html = str_get_html($resume);
$title = sprintf(
    '%s | %s',
    $html->find('h1', 0)->innertext,
    $html->find('h2', 0)->innertext
);

// We'll now render the Markdown into an html file with Mustache Templates
$m = new Mustache;
$rendered = $m->render(
    $template,
    array(
        'title'  => $title,
        'style'  => $style,
        'resume' => $resume,
        'reload' => $config->refresh
    )
);

// Save the fully rendered html to the final destination
file_put_contents($output, $rendered);
echo "Wrote html to $output\n";

// If the user wants to make a pdf file, we'll use wkhtmltopdf to convert
// the html document into a nice looking pdf.
if ($config->pdf) {

    // The pdf needs some extra css rules, and so we'll add them here
    // to our html document
    $pdf_classed = str_replace('body class=""', 'body class="pdf"', $rendered);

    // Save the new pdf-ready html to a temp destination
    file_put_contents($pdf_source, $pdf_classed );

    // Process the document with wkhtmltopdf
    exec('wkhtmltopdf ' . $pdf_source .' ' . $pdf_output);

    // Unlink the temporary file
    unlink($pdf_source);
    echo "Wrote pdf to $pdf_output\n";
}

/* End of file resume.php */
