<?php

define('APPLICATION_BASE_PATH', realpath(__DIR__ . '/..'));

spl_autoload_register(function ($className) {
    $namespaces = explode('\\', $className);
    if (count($namespaces) > 1) {
        $classPath
            = APPLICATION_BASE_PATH
            . '/vendor/'
            . implode('/', $namespaces)
            . '.php';
        if (file_exists($classPath)) {
            require_once($classPath);
        }
    }
});

include_once APPLICATION_BASE_PATH . '/vendor/Mustache/Mustache.php';
include_once APPLICATION_BASE_PATH . '/vendor/smartypants/smartypants.php';
include_once APPLICATION_BASE_PATH . '/vendor/markdown-extra/markdown.php';
include_once APPLICATION_BASE_PATH . '/vendor/lessphp/lessc.inc.php';
include_once APPLICATION_BASE_PATH . '/vendor/simpledom/simple_html_dom.php';


use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\Filter;

$shortopts  = "";
$shortopts .= "s:";
$shortopts .= "rp";

$longopts  = array(
    "source:",
    "refresh",
    "pdf"
);
$options = getopt($shortopts, $longopts);

if (!isset($options['s'])) {
    exit('Please specify a source document  build.php -s resume.pdf');
}

$basename     = pathinfo($options['s'], PATHINFO_FILENAME);
$source       = './resume/' . $options['s'];
$pdf_source   = './output/' . $basename . '-pdf.html';
$output       = './output/' . $basename . '.html';
$pdf_output   = './output/' . $basename . '.pdf';


$refresh_dev = isset($options['r']) || isset($options['refresh']);

$css = new AssetCollection(
    array(
        new GlobAsset(APPLICATION_BASE_PATH . '/assets/css/*.css')
    ),
    array(
        new Filter\LessphpFilter(),
    )
);
$style = $css->dump();

$template = file_get_contents(APPLICATION_BASE_PATH . '/assets/templates/default.html');
$resume   = file_get_contents(APPLICATION_BASE_PATH . '/resume/resume.md');

$resume = Markdown($resume);
$resume = SmartyPants($resume);

$html = str_get_html($resume);
$title = sprintf(
    '%s | %s',
    $html->find('h1', 0)->innertext,
    $html->find('h2', 0)->innertext
);
    
$m = new Mustache;
$rendered = $m->render(
    $template,
    array(
        'title'  => $title,
        'style'  => $style,
        'resume' => $resume,
        'reload' => $refresh_dev
    )
);

file_put_contents(
    APPLICATION_BASE_PATH . '/output/resume.html',
    $rendered
);


$pdf_classed = str_replace(
    'body class=""',
    'body class="pdf"',
    $rendered
);

if (isset($options['pdf'])) {
    file_put_contents(
        $pdf_source,
        $pdf_classed
    );

    exec(
        'wkhtmltopdf '
        . $pdf_source .' '
        . $pdf_output
        . ' && open ' . $pdf_output
    );
    
    //unlink(APPLICATION_BASE_PATH . '/resume/resume-pdf.html');
}



/* End of file build.php */