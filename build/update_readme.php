#!/usr/bin/env php
<?php
/**
 * Run the markdown resume and update the readme with the generated help
 */

$baseDir    = dirname(__DIR__);
$startPoint = '## Help';
$endPoint   = '## Examples';
$readme     = file_get_contents('README.md');
$help       = shell_exec('php '.$baseDir.'/bin/md2resume list --no-interaction');
$output     = preg_replace(
    '/('.preg_quote($startPoint).')(.*)('.preg_quote($endPoint).')/si',
    "$1\n```\n" . $help . "\n```\n$3",
    $readme
);

file_put_contents($baseDir.'/README.md', $output);

/* End of file updated_readme.php */
