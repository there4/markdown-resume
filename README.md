# Markdown Resume Generator

2.0 Work in progress, unstable and not yet ready for use.

- [x] Update composer for symfony dependencies
- [ ] Add pake and phar generator
- [ ] Update bin with new generated phar
- [x] Convert to new command structure
- [ ] Update help files

## Description

Turn a simple Markdown document into an elegant resume with both a perfect
pdf printable format, and a responsive css3 html5 file. You can view a sample
at the [blog post for the project][blog].

## Features

* Three styles to choose from: modern, blockish, unstyled
* PDF generation via `wkhtmltopdf`
* Responsive design for multiple device viewport sizes
* Simple Markdown formatting
* Single file deployment
* You can now version control and branch your resume.

## Quickstart

    php ./bin/resume.php --source resume/sample.md
    php ./bin/resume.php --source resume/sample.md --pdf

## Help

    Markdown Resume Generator version 2.0.0 by Craig Davis
    
    Usage:
      [options] command [arguments]
    
    Options:
      --help           -h Display this help message.
      --quiet          -q Do not output any message.
      --verbose        -v|vv|vvv Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
      --version        -V Display this application version.
      --ansi              Force ANSI output.
      --no-ansi           Disable ANSI output.
      --no-interaction -n Do not ask any interactive question.
    
    Available commands:
      help         Displays help for a command
      html         Generate an HTML resume from a markdown file
      list         Lists commands
      pdf          Generate a PDF from a markdown file
      selfupdate   Updates fb.phar to the latest version.
      templates    List available templates
      version      Show current version information
    
## Examples

Choose a template with the -t option.

    php ./bin/resume.php --source resume/sample.md -t blockish

If you want to edit your markdown resume in your editor while watching it
update in your browser, run this command:

    watch php ./bin/resume.php -s resume/sample.md -r
    
This makes the build script run periodically, and html document will refresh
every two seconds via a meta tag. Open the `./ouput/sample.html` file in
your browser, and then just save your markdown document when you want to see
a fresh preview.

## Development

Markdown is limited to basic html markup. Follow the `resume/sample.md` file 
as a guideline. This file includes various headers and several nested elements.
This allows us to construct a semantic HTML document for the resume, and then
use a CSS rules to display a very nice resume. Note that because we have very
few ways to nest or identify elements that many of the css rules are based
on descendant and adjacent selectors. 

## Development Dependencies

* composer install
* pear install PHP_CodeSniffer
* [install pake][pake]

## TODO

* Google Analytics include

## Acknowledgments

The initial inspiration is from the [Sample Resume Template][srt].
However, no HTML from that project has been used in this. General layout has been reused, and media queries
have been added. It's a nice template, and if you are a more comfortable with html than markdown, you should use it.

## Changelog

* __0.9.0__ : Add composer and update README with new changelog
* __0.8.8__ : Add Chinese text example (@ishitcno1)
* __0.8.7__ : Update pdf formatting of the modern template (@roleary)
* __0.8.6__ : Fix output path (@abhikandoi2000)
* __0.8.5__ : Fix issue #2
* __0.8.4__ : Correct chmod and add parameter for output directory (@kevinxucs)
* __0.8.2__ : Update build script and add refresh command option
* __0.8.1__ : Updating formatting of initial templates
* __0.8__ : Initial Release to Public 

[srt]: http://sampleresumetemplate.net/ "A great starting point"
[blog]: http://there4development.com/blog/2012/12/31/markdown-resume-builder/
[pake]: https://github.com/indeyets/pake/wiki/Installing-Pake
