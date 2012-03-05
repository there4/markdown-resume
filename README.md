# Markdown Resume Styles

Turn a simple Markdown document into an elegant resume.

## Features

* PDF generation via `wkhtmltopdf`
* Responsive design for multiple device viewport sizes
* Simple Markdown formatting
* Single file deployment
* You can now version control and branch your resume.

## Quickstart

    php ./build/build.php --source sample.md
    php ./build/build.php --source sample.md --pdf

## Development

Markdown is limited to basic html markup. Follow the `resume/sample.md` file 
as a guideline. This file includes various headers and several nested elements.
This allows us to construct a semantic HTML document for the resume, and then
use a CSS rules to display a very nice resume. Note that because we have very
few ways to nest or identify elements that many of the css rules are based
on descendant and adjacent selectors. 

## TODO

* Additional styles
* Google Analytics include
* Command line documentation

## Acknowledgments

The initial inspiration is from the [Sample Resume Template](http://sampleresumetemplate.net/).
However, no HTML from that project has been used in this. General layout has been reused, and media queries
have been added. It's a nice template, and if you are a more comfortable with html than markdown, you should use it.