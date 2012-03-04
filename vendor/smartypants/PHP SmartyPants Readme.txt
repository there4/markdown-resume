PHP SmartyPants Typographer
===========================

Version 1.0 - Wed 28 Jun 2006

by Michel Fortin
<http://www.michelf.com/>

Original SmartyPants by John Gruber  
<http://daringfireball.net/>


Introduction
------------

This is a special version of PHP SmartyPants with extra features. See 
<http://www.michelf.com/projects/php-smartypants/typographer/> for 
details.

PHP SmartyPants is a free web publishing plug-in for WordPress and 
Smarty template engine that easily translates plain ASCII punctuation 
characters into "smart" typographic punctuation HTML entities. 
SmartyPants can also be invoked as a standalone PHP function.

PHP SmartyPants is a port to PHP of the original SmartyPants written 
in Perl by John Gruber.

SmartyPants can perform the following transformations:

*   Straight quotes (`"` and `'`) into "curly" quote HTML entities
*   Backtick-style quotes (` ``like this'' `) into "curly" quote HTML
    entities
*   Dashes (`--` and `---`) into en- and em-dash entities
*   Three consecutive dots (`...`) into an ellipsis entity

This means you can write, edit, and save using plain old ASCII straight 
quotes, plain dashes, and plain dots, but your published posts (and 
final HTML output) will appear with smart quotes, em-dashes, and proper 
ellipses.

SmartyPants does not modify characters within `<pre>`, `<code>`,
`<kbd>`, or `<script>` tag blocks. Typically, these tags are used to
display text where smart quotes and other "smart punctuation" would not
be appropriate, such as source code or example markup.


### Backslash Escapes ###

If you need to use literal straight quotes (or plain hyphens and
periods), SmartyPants accepts the following backslash escape sequences
to force non-smart punctuation. It does so by transforming the escape
sequence into a decimal-encoded HTML entity:


    Escape  Value  Character
    ------  -----  ---------
      \\    &#92;    \
      \"    &#34;    "
      \'    &#39;    '
      \.    &#46;    .
      \-    &#45;    -
      \`    &#96;    `


This is useful, for example, when you want to use straight quotes as
foot and inch marks:

    6\'2\" tall

translates into:

    6&#39;2&#34; tall

in SmartyPants's HTML output. Which, when rendered by a web browser,
looks like:

    6'2" tall


Installation and Requirement
----------------------------

PHP SmartyPants require PHP version 4.0.5 or later.


### WordPress ###

WordPress already include a filter called "Texturize" with the same 
goal as SmartyPants. You could still find some usefulness to 
PHP SmartyPants if you are not happy enough with the standard algorithm.

PHP SmartyPants works with [WordPress][wp], version 1.2 or later.

[wp]: http://wordpress.org/

1.  To use PHP SmartyPants with WordPress, place the "smartypants.php" 
    file in the "plugins" folder. This folder is hidden inside 
    "wp-content" at the root of your site:

        (site home)/wp-content/plugins/smartypants.php

2.  Activate the plugin with the administrative interface of WordPress. 
    In the "Plugins" section you will now find SmartyPants. To activate 
    the plugin, click on the "Activate" button on the same line than 
    SmartyPants. Your entries will now be filtered by PHP SmartyPants.

Note: It is not possible at this time to apply a different set of 
filters to different entries. All your entries will be filtered by 
PHP SmartyPants if the plugin is active. This is currently a limitation 
of WordPress.


### In your programs ###

You can use PHP SmartyPants easily in your current PHP program. Simply 
include the file and then call the `SmartyPants` function on the text 
you want to convert:

	include_once "smartypants.php";
	$my_text = SmartyPants($my_text);


### With Smarty ###

If your program use the [Smarty][sm] template engine, PHP SmartyPants 
can now be used as a modifier for your templates. Rename 
"smartypants.php" to "modifier.smartypants.php" and put it in your 
smarty plugins folder.

[sm]: http://smarty.php.net/


Options and Configuration
-------------------------

Settings are specified by editing the value of the `$smartypants_attr`
variable in the "smartypants.php" file. For users of the Smarty template 
engine, the "smartypants" modifier also takes an optional attribute where 
you can specify configuration options, like this: 
`{$var|smartypants:1}` (where "1" is the configuration option).

Numeric values are the easiest way to configure SmartyPants's behavior:

"0"
    Suppress all transformations. (Do nothing.)

"1"
    Performs default SmartyPants transformations: quotes (including
    backticks-style), em-dashes, and ellipses. `--` (dash dash) is
    used to signify an em-dash; there is no support for en-dashes.

"2"
    Same as smarty_pants="1", except that it uses the old-school
    typewriter shorthand for dashes: `--` (dash dash) for en-dashes,
    `---` (dash dash dash) for em-dashes.

"3"
    Same as smarty_pants="2", but inverts the shorthand for dashes: `--`
    (dash dash) for em-dashes, and `---` (dash dash dash) for en-dashes.

"-1"
    Stupefy mode. Reverses the SmartyPants transformation process,
    turning the HTML entities produced by SmartyPants into their ASCII
    equivalents. E.g. `&#8220;` is turned into a simple double-quote
    (`"`), `&#8212;` is turned into two dashes, etc. This is useful if you
    wish to suppress smart punctuation in specific pages, such as
    RSS feeds.

The following single-character attribute values can be combined to
toggle individual transformations from within the smarty_pants
attribute. For example, to educate normal quotes and em-dashes, but not
ellipses or backticks-style quotes:

    $smartypants_attr = "qd";

Or inside a Smarty template:

    {$var|smartypants:"qd"}

"q"
    Educates normal quote characters: (`"`) and (`'`).

"b"
    Educates ` ``backticks'' ` double quotes.

"B"
    Educates backticks-style double quotes and ` `single' ` quotes.

"d"
    Educates em-dashes.

"D"
    Educates em-dashes and en-dashes, using old-school typewriter
    shorthand: (dash dash) for en-dashes, (dash dash dash) for
    em-dashes.

"i"
    Educates em-dashes and en-dashes, using inverted old-school
    typewriter shorthand: (dash dash) for em-dashes, (dash dash dash)
    for en-dashes.

"e"
    Educates ellipses.

"w"
    Translates any instance of `&quot;` into a normal double-quote
    character. This should be of no interest to most people, but of
    particular interest to anyone who writes their posts using
    Dreamweaver, as Dreamweaver inexplicably uses this entity to
    represent a literal double-quote character. SmartyPants only
    educates normal quotes, not entities (because ordinarily, entities
    are used for the explicit purpose of representing the specific
    character they represent). The "w" option must be used in
    conjunction with one (or both) of the other quote options ("q" or
    "b"). Thus, if you wish to apply all SmartyPants transformations
    (quotes, en- and em-dashes, and ellipses) and also translate
    `&quot;` entities into regular quotes so SmartyPants can educate
    them, you should set the SMARTYPANTS_ATTR constant at the top of 
    the file to:

        define( 'SMARTYPANTS_ATTR',    "qDew" );

    Inside a Smarty template, you could also pass the string as a 
    parameter:

        {$var|smartypants:"qDew"}


### Algorithmic Shortcomings ###

One situation in which quotes will get curled the wrong way is when
apostrophes are used at the start of leading contractions. For example:

    'Twas the night before Christmas.

In the case above, SmartyPants will turn the apostrophe into an opening
single-quote, when in fact it should be a closing one. I don't think
this problem can be solved in the general case -- every word processor
I've tried gets this wrong as well. In such cases, it's best to use the
proper HTML entity for closing single-quotes (`&#8217;` or `&rsquo;`) by
hand.


Bugs
----

To file bug reports or feature requests (other than topics listed in the
Caveats section above) please send email to:

<michel.fortin@michelf.com>

If the bug involves quotes being curled the wrong way, please send
example text to illustrate.


Version History
---------------

1.0 (28 Jun 2006)

*   First public release of PHP SmartyPants Typographer.
