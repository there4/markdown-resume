<?php
#
# SmartyPants Typographer  -  Smart typography for web sites
#
# PHP SmartyPants & Typographer  
# Copyright (c) 2004-2006 Michel Fortin
# <http://www.michelf.com/>
#
# Original SmartyPants
# Copyright (c) 2003-2004 John Gruber
# <http://daringfireball.net/>
#


define( 'SMARTYPANTS_VERSION',            "1.5.1oo2" ); # Unreleased
define( 'SMARTYPANTSTYPOGRAPHER_VERSION', "1.0"      ); # Wed 28 Jun 2006


#
# Default configuration:
#
#  1  ->  "--" for em-dashes; no en-dash support  
#  2  ->  "---" for em-dashes; "--" for en-dashes  
#  3  ->  "--" for em-dashes; "---" for en-dashes  
#  See docs for more configuration options.
#
define( 'SMARTYPANTS_ATTR',    1 );

# Openning and closing smart double-quotes.
define( 'SMARTYPANTS_SMART_DOUBLEQUOTE_OPEN',  "&#8220;" );
define( 'SMARTYPANTS_SMART_DOUBLEQUOTE_CLOSE', "&#8221;" );

# Space around em-dashes.  "He_—_or she_—_should change that."
define( 'SMARTYPANTS_SPACE_EMDASH',      " " );

# Space around en-dashes.  "He_–_or she_–_should change that."
define( 'SMARTYPANTS_SPACE_ENDASH',      " " );

# Space before a colon. "He said_: here it is."
define( 'SMARTYPANTS_SPACE_COLON',       "&#160;" );

# Space before a semicolon. "That's what I said_; that's what he said."
define( 'SMARTYPANTS_SPACE_SEMICOLON',   "&#160;" );

# Space before a question mark and an exclamation mark: "¡_Holà_! What_?"
define( 'SMARTYPANTS_SPACE_MARKS',       "&#160;" );

# Space inside french quotes. "Voici la «_chose_» qui m'a attaqué."
define( 'SMARTYPANTS_SPACE_FRENCHQUOTE', "&#160;" );

# Space as thousand separator. "On compte 10_000 maisons sur cette liste."
define( 'SMARTYPANTS_SPACE_THOUSAND',    "&#160;" );

# Space before a unit abreviation. "This 12_kg of matter costs 10_$."
define( 'SMARTYPANTS_SPACE_UNIT',        "&#160;" );

# SmartyPants will not alter the content of these tags:
define( 'SMARTYPANTS_TAGS_TO_SKIP', 'pre|code|kbd|script|math');



### Standard Function Interface ###

define( 'SMARTYPANTS_PARSER_CLASS', 'SmartyPantsTypographer_Parser' );

function SmartyPants($text, $attr = SMARTYPANTS_ATTR) {
#
# Initialize the parser and return the result of its transform method.
#
	# Setup static parser array.
	static $parser = array();
	if (!isset($parser[$attr])) {
		$parser_class = SMARTYPANTS_PARSER_CLASS;
		$parser[$attr] = new $parser_class($attr);
	}

	# Transform text using parser.
	return $parser[$attr]->transform($text);
}

function SmartQuotes($text, $attr = 1) {
	switch ($attr) {
		case 0:  return $text;
		case 2:  $attr = 'qb'; break;
		default: $attr = 'q'; break;
	}
	return SmartyPants($text, $attr);
}

function SmartDashes($text, $attr = 1) {
	switch ($attr) {
		case 0:  return $text;
		case 2:  $attr = 'D'; break;
		case 3:  $attr = 'i'; break;
		default: $attr = 'd'; break;
	}
	return SmartyPants($text, $attr);
}

function SmartEllipsis($text, $attr = 1) {
	switch ($attr) {
		case 0:  return $text;
		default: $attr = 'e'; break;
	}
	return SmartyPants($text, $attr);
}


### WordPress Plugin Interface ###

/*
Plugin Name: SmartyPants Typographer
Plugin URI: http://www.michelf.com/projects/php-smartypants/
Description: SmartyPants is a web publishing utility that translates plain ASCII punctuation characters into &#8220;smart&#8221; typographic punctuation HTML entities. The Typographer extension will also replace normal spaces with unbrekable ones where appropriate to silently remove unwanted line breaks around punctuation and at some other places. This plugin <strong>replace the default WordPress Texturize algorithm</strong> for the content and the title of your posts, the comments body and author name, and everywhere else Texturize normally apply.
Version: 1.0
Author: Michel Fortin
Author URI: http://www.michelf.com/
*/

if (isset($wp_version)) {
	# Remove default Texturize filter that would conflict with SmartyPants.
	remove_filter('category_description', 'wptexturize');
	remove_filter('list_cats', 'wptexturize');
	remove_filter('comment_author', 'wptexturize');
	remove_filter('comment_text', 'wptexturize');
	remove_filter('single_post_title', 'wptexturize');
	remove_filter('the_title', 'wptexturize');
	remove_filter('the_content', 'wptexturize');
	remove_filter('the_excerpt', 'wptexturize');
	# Add SmartyPants filter with priority 10 (same as Texturize).
	add_filter('category_description', 'SmartyPants', 10);
	add_filter('list_cats', 'SmartyPants', 10);
	add_filter('comment_author', 'SmartyPants', 10);
	add_filter('comment_text', 'SmartyPants', 10);
	add_filter('single_post_title', 'SmartyPants', 10);
	add_filter('the_title', 'SmartyPants', 10);
	add_filter('the_content', 'SmartyPants', 10);
	add_filter('the_excerpt', 'SmartyPants', 10);
}


### Smarty Modifier Interface ###

function smarty_modifier_smartypants($text, $attr = NULL) {
	return SmartyPants($text, $attr);
}



#
# SmartyPants Parser Class
#

class SmartyPants_Parser {

	# Options to specify which transformations to make:
	var $do_nothing   = 0;
	var $do_quotes    = 0;
	var $do_backticks = 0;
	var $do_dashes    = 0;
	var $do_ellipses  = 0;
	var $do_stupefy   = 0;
	var $convert_quot = 0; # should we translate &quot; entities into normal quotes?

	function SmartyPants_Parser($attr = SMARTYPANTS_ATTR) {
	#
	# Initialize a SmartyPants_Parser with certain attributes.
	#
	# Parser attributes:
	# 0 : do nothing
	# 1 : set all
	# 2 : set all, using old school en- and em- dash shortcuts
	# 3 : set all, using inverted old school en and em- dash shortcuts
	# 
	# q : quotes
	# b : backtick quotes (``double'' only)
	# B : backtick quotes (``double'' and `single')
	# d : dashes
	# D : old school dashes
	# i : inverted old school dashes
	# e : ellipses
	# w : convert &quot; entities to " for Dreamweaver users
	#
		if ($attr == "0") {
			$this->do_nothing   = 1;
		}
		else if ($attr == "1") {
			# Do everything, turn all options on.
			$this->do_quotes    = 1;
			$this->do_backticks = 1;
			$this->do_dashes    = 1;
			$this->do_ellipses  = 1;
		}
		else if ($attr == "2") {
			# Do everything, turn all options on, use old school dash shorthand.
			$this->do_quotes    = 1;
			$this->do_backticks = 1;
			$this->do_dashes    = 2;
			$this->do_ellipses  = 1;
		}
		else if ($attr == "3") {
			# Do everything, turn all options on, use inverted old school dash shorthand.
			$this->do_quotes    = 1;
			$this->do_backticks = 1;
			$this->do_dashes    = 3;
			$this->do_ellipses  = 1;
		}
		else if ($attr == "-1") {
			# Special "stupefy" mode.
			$this->do_stupefy   = 1;
		}
		else {
			$chars = preg_split('//', $attr);
			foreach ($chars as $c){
				if      ($c == "q") { $this->do_quotes    = 1; }
				else if ($c == "b") { $this->do_backticks = 1; }
				else if ($c == "B") { $this->do_backticks = 2; }
				else if ($c == "d") { $this->do_dashes    = 1; }
				else if ($c == "D") { $this->do_dashes    = 2; }
				else if ($c == "i") { $this->do_dashes    = 3; }
				else if ($c == "e") { $this->do_ellipses  = 1; }
				else if ($c == "w") { $this->convert_quot = 1; }
				else {
					# Unknown attribute option, ignore.
				}
			}
		}
	}

	function transform($text) {

		if ($this->do_nothing) {
			return $text;
		}

		$tokens = $this->tokenizeHTML($text);
		$result = '';
		$in_pre = 0;  # Keep track of when we're inside <pre> or <code> tags.

		$prev_token_last_char = ""; # This is a cheat, used to get some context
									# for one-character tokens that consist of 
									# just a quote char. What we do is remember
									# the last character of the previous text
									# token, to use as context to curl single-
									# character quote tokens correctly.

		foreach ($tokens as $cur_token) {
			if ($cur_token[0] == "tag") {
				# Don't mess with quotes inside tags.
				$result .= $cur_token[1];
				if (preg_match('@<(/?)(?:'.SMARTYPANTS_TAGS_TO_SKIP.')[\s>]@', $cur_token[1], $matches)) {
					$in_pre = isset($matches[1]) && $matches[1] == '/' ? 0 : 1;
				}
			} else {
				$t = $cur_token[1];
				$last_char = substr($t, -1); # Remember last char of this token before processing.
				if (! $in_pre) {
					$t = $this->educate($t, $prev_token_last_char);
				}
				$prev_token_last_char = $last_char;
				$result .= $t;
			}
		}

		return $result;
	}


	function educate($t, $prev_token_last_char) {
		$t = $this->processEscapes($t);

		if ($this->convert_quot) {
			$t = preg_replace('/&quot;/', '"', $t);
		}

		if ($this->do_dashes) {
			if ($this->do_dashes == 1) $t = $this->educateDashes($t);
			if ($this->do_dashes == 2) $t = $this->educateDashesOldSchool($t);
			if ($this->do_dashes == 3) $t = $this->educateDashesOldSchoolInverted($t);
		}

		if ($this->do_ellipses) $t = $this->educateEllipses($t);

		# Note: backticks need to be processed before quotes.
		if ($this->do_backticks) {
			$t = $this->educateBackticks($t);
			if ($this->do_backticks == 2) $t = $this->educateSingleBackticks($t);
		}

		if ($this->do_quotes) {
			if ($t == "'") {
				# Special case: single-character ' token
				if (preg_match('/\S/', $prev_token_last_char)) {
					$t = "&#8217;";
				}
				else {
					$t = "&#8216;";
				}
			}
			else if ($t == '"') {
				# Special case: single-character " token
				if (preg_match('/\S/', $prev_token_last_char)) {
					$t = "&#8221;";
				}
				else {
					$t = "&#8220;";
				}
			}
			else {
				# Normal case:
				$t = $this->educateQuotes($t);
			}
		}

		if ($this->do_stupefy) $t = $this->stupefyEntities($t);
		
		return $t;
	}


	function educateQuotes($_) {
	#
	#   Parameter:  String.
	#
	#   Returns:    The string, with "educated" curly quote HTML entities.
	#
	#   Example input:  "Isn't this fun?"
	#   Example output: &#8220;Isn&#8217;t this fun?&#8221;
	#
		# Make our own "punctuation" character class, because the POSIX-style
		# [:PUNCT:] is only available in Perl 5.6 or later:
		$punct_class = "[!\"#\\$\\%'()*+,-.\\/:;<=>?\\@\\[\\\\\]\\^_`{|}~]";

		# Special case if the very first character is a quote
		# followed by punctuation at a non-word-break. Close the quotes by brute force:
		$_ = preg_replace(
			array("/^'(?=$punct_class\\B)/", "/^\"(?=$punct_class\\B)/"),
			array('&#8217;',                 '&#8221;'), $_);


		# Special case for double sets of quotes, e.g.:
		#   <p>He said, "'Quoted' words in a larger quote."</p>
		$_ = preg_replace(
			array("/\"'(?=\w)/",    "/'\"(?=\w)/"),
			array('&#8220;&#8216;', '&#8216;&#8220;'), $_);

		# Special case for decade abbreviations (the '80s):
		$_ = preg_replace("/'(?=\\d{2}s)/", '&#8217;', $_);

		$close_class = '[^\ \t\r\n\[\{\(\-]';
		$dec_dashes = '&\#8211;|&\#8212;';

		# Get most opening single quotes:
		$_ = preg_replace("{
			(
				\\s          |   # a whitespace char, or
				&nbsp;      |   # a non-breaking space entity, or
				--          |   # dashes, or
				&[mn]dash;  |   # named dash entities
				$dec_dashes |   # or decimal entities
				&\\#x201[34];    # or hex
			)
			'                   # the quote
			(?=\\w)              # followed by a word character
			}x", '\1&#8216;', $_);
		# Single closing quotes:
		$_ = preg_replace("{
			($close_class)?
			'
			(?(1)|          # If $1 captured, then do nothing;
			  (?=\\s | s\\b)  # otherwise, positive lookahead for a whitespace
			)               # char or an 's' at a word ending position. This
							# is a special case to handle something like:
							# \"<i>Custer</i>'s Last Stand.\"
			}xi", '\1&#8217;', $_);

		# Any remaining single quotes should be opening ones:
		$_ = str_replace("'", '&#8216;', $_);


		# Get most opening double quotes:
		$_ = preg_replace("{
			(
				\\s          |   # a whitespace char, or
				&nbsp;      |   # a non-breaking space entity, or
				--          |   # dashes, or
				&[mn]dash;  |   # named dash entities
				$dec_dashes |   # or decimal entities
				&\\#x201[34];    # or hex
			)
			\"                   # the quote
			(?=\\w)              # followed by a word character
			}x", '\1&#8220;', $_);

		# Double closing quotes:
		$_ = preg_replace("{
			($close_class)?
			\"
			(?(1)|(?=\\s))   # If $1 captured, then do nothing;
							   # if not, then make sure the next char is whitespace.
			}x", '\1&#8221;', $_);

		# Any remaining quotes should be opening ones.
		$_ = str_replace('"', '&#8220;', $_);

		return $_;
	}


	function educateBackticks($_) {
	#
	#   Parameter:  String.
	#   Returns:    The string, with ``backticks'' -style double quotes
	#               translated into HTML curly quote entities.
	#
	#   Example input:  ``Isn't this fun?''
	#   Example output: &#8220;Isn't this fun?&#8221;
	#

		$_ = str_replace(array("``",       "''",),
						 array('&#8220;', '&#8221;'), $_);
		return $_;
	}


	function educateSingleBackticks($_) {
	#
	#   Parameter:  String.
	#   Returns:    The string, with `backticks' -style single quotes
	#               translated into HTML curly quote entities.
	#
	#   Example input:  `Isn't this fun?'
	#   Example output: &#8216;Isn&#8217;t this fun?&#8217;
	#

		$_ = str_replace(array("`",       "'",),
						 array('&#8216;', '&#8217;'), $_);
		return $_;
	}


	function educateDashes($_) {
	#
	#   Parameter:  String.
	#
	#   Returns:    The string, with each instance of "--" translated to
	#               an em-dash HTML entity.
	#

		$_ = str_replace('--', '&#8212;', $_);
		return $_;
	}


	function educateDashesOldSchool($_) {
	#
	#   Parameter:  String.
	#
	#   Returns:    The string, with each instance of "--" translated to
	#               an en-dash HTML entity, and each "---" translated to
	#               an em-dash HTML entity.
	#

		#                      em         en
		$_ = str_replace(array("---",     "--",),
						 array('&#8212;', '&#8211;'), $_);
		return $_;
	}


	function educateDashesOldSchoolInverted($_) {
	#
	#   Parameter:  String.
	#
	#   Returns:    The string, with each instance of "--" translated to
	#               an em-dash HTML entity, and each "---" translated to
	#               an en-dash HTML entity. Two reasons why: First, unlike the
	#               en- and em-dash syntax supported by
	#               EducateDashesOldSchool(), it's compatible with existing
	#               entries written before SmartyPants 1.1, back when "--" was
	#               only used for em-dashes.  Second, em-dashes are more
	#               common than en-dashes, and so it sort of makes sense that
	#               the shortcut should be shorter to type. (Thanks to Aaron
	#               Swartz for the idea.)
	#

		#                      en         em
		$_ = str_replace(array("---",     "--",),
						 array('&#8211;', '&#8212;'), $_);
		return $_;
	}


	function educateEllipses($_) {
	#
	#   Parameter:  String.
	#   Returns:    The string, with each instance of "..." translated to
	#               an ellipsis HTML entity. Also converts the case where
	#               there are spaces between the dots.
	#
	#   Example input:  Huh...?
	#   Example output: Huh&#8230;?
	#

		$_ = str_replace(array("...",     ". . .",), '&#8230;', $_);
		return $_;
	}


	function stupefyEntities($_) {
	#
	#   Parameter:  String.
	#   Returns:    The string, with each SmartyPants HTML entity translated to
	#               its ASCII counterpart.
	#
	#   Example input:  &#8220;Hello &#8212; world.&#8221;
	#   Example output: "Hello -- world."
	#

							#  en-dash    em-dash
		$_ = str_replace(array('&#8211;', '&#8212;'),
						 array('-',       '--'), $_);

		# single quote         open       close
		$_ = str_replace(array('&#8216;', '&#8217;'), "'", $_);

		# double quote         open       close
		$_ = str_replace(array('&#8220;', '&#8221;'), '"', $_);

		$_ = str_replace('&#8230;', '...', $_); # ellipsis

		return $_;
	}


	function processEscapes($_) {
	#
	#   Parameter:  String.
	#   Returns:    The string, with after processing the following backslash
	#               escape sequences. This is useful if you want to force a "dumb"
	#               quote or other character to appear.
	#
	#               Escape  Value
	#               ------  -----
	#               \\      &#92;
	#               \"      &#34;
	#               \'      &#39;
	#               \.      &#46;
	#               \-      &#45;
	#               \`      &#96;
	#
		$_ = str_replace(
			array('\\\\',  '\"',    "\'",    '\.',    '\-',    '\`'),
			array('&#92;', '&#34;', '&#39;', '&#46;', '&#45;', '&#96;'), $_);

		return $_;
	}


	function tokenizeHTML($str) {
	#
	#   Parameter:  String containing HTML markup.
	#   Returns:    An array of the tokens comprising the input
	#               string. Each token is either a tag (possibly with nested,
	#               tags contained therein, such as <a href="<MTFoo>">, or a
	#               run of text between tags. Each element of the array is a
	#               two-element array; the first is either 'tag' or 'text';
	#               the second is the actual value.
	#
	#
	#   Regular expression derived from the _tokenize() subroutine in 
	#   Brad Choate's MTRegex plugin.
	#   <http://www.bradchoate.com/past/mtregex.php>
	#
		$index = 0;
		$tokens = array();

		$match = '(?s:<!(?:--.*?--\s*)+>)|'.	# comment
				 '(?s:<\?.*?\?>)|'.				# processing instruction
												# regular tags
				 '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)'; 

		$parts = preg_split("{($match)}", $str, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($parts as $part) {
			if (++$index % 2 && $part != '') 
				$tokens[] = array('text', $part);
			else
				$tokens[] = array('tag', $part);
		}
		return $tokens;
	}

}


#
# SmartyPants Typographer Parser Class
#
class SmartyPantsTypographer_Parser extends SmartyPants_Parser {

	# Options to specify which transformations to make:
	var $do_comma_quotes      = 0;
	var $do_guillemets        = 0;
	var $do_space_emdash      = 0;
	var $do_space_endash      = 0;
	var $do_space_colon       = 0;
	var $do_space_semicolon   = 0;
	var $do_space_marks       = 0;
	var $do_space_frenchquote = 0;
	var $do_space_thousand    = 0;
	var $do_space_unit        = 0;
	
	# Smart quote characters:
	var $smart_doublequote_open  = SMARTYPANTS_SMART_DOUBLEQUOTE_OPEN;
	var $smart_doublequote_close = SMARTYPANTS_SMART_DOUBLEQUOTE_CLOSE;
	var $smart_singlequote_open  = '&#8216;';
	var $smart_singlequote_close = '&#8217;'; # Also apostrophe.

	# Space characters for different places:
	var $space_emdash      = SMARTYPANTS_SPACE_EMDASH;
	var $space_endash      = SMARTYPANTS_SPACE_ENDASH;
	var $space_colon       = SMARTYPANTS_SPACE_COLON;
	var $space_semicolon   = SMARTYPANTS_SPACE_SEMICOLON;
	var $space_marks       = SMARTYPANTS_SPACE_MARKS;
	var $space_frenchquote = SMARTYPANTS_SPACE_FRENCHQUOTE;
	var $space_thousand    = SMARTYPANTS_SPACE_THOUSAND;
	var $space_unit        = SMARTYPANTS_SPACE_UNIT;
	
	# Expression of a space (breakable or not):
	var $space = '(?: | |&nbsp;|&#0*160;|&#x0*[aA]0;)';

	

	function SmartyPantsTypographer_Parser($attr = SMARTYPANTS_ATTR) {
	#
	# Initialize a SmartyPantsTypographer_Parser with certain attributes.
	#
	# Parser attributes:
	# 0 : do nothing
	# 1 : set all, except dash spacing
	# 2 : set all, except dash spacing, using old school en- and em- dash shortcuts
	# 3 : set all, except dash spacing, using inverted old school en and em- dash shortcuts
	# 
	# Punctuation:
	# q -> quotes
	# b -> backtick quotes (``double'' only)
	# B -> backtick quotes (``double'' and `single')
	# c -> comma quotes (,,double`` only)
	# g -> guillemets (<<double>> only)
	# d -> dashes
	# D -> old school dashes
	# i -> inverted old school dashes
	# e -> ellipses
	# w -> convert &quot; entities to " for Dreamweaver users
	#
	# Spacing:
	# : -> colon spacing +-
	# ; -> semicolon spacing +-
	# m -> question and exclamation marks spacing +-
	# h -> em-dash spacing +-
	# H -> en-dash spacing +-
	# f -> french quote spacing +-
	# t -> thousand separator spacing -
	# u -> unit spacing +-
	#   (you can add a plus sign after some of these options denoted by + to 
	#    add the space when it is not already present, or you can add a minus 
	#    sign to completly remove any space present)
	#
		# Initialize inherited SmartyPants parser.
		parent::SmartyPants_Parser($attr);
				
		if ($attr == "1" || $attr == "2" || $attr == "3") {
			# Do everything, turn all options on.
			$this->do_comma_quotes      = 1;
			$this->do_guillemets  = 1;
			$this->do_space_emdash      = 1;
			$this->do_space_endash      = 1;
			$this->do_space_colon       = 1;
			$this->do_space_semicolon   = 1;
			$this->do_space_marks       = 1;
			$this->do_space_frenchquote = 1;
			$this->do_space_thousand    = 1;
			$this->do_space_unit        = 1;
		}
		else if ($attr == "-1") {
			# Special "stupefy" mode.
			$this->do_stupefy   = 1;
		}
		else {
			$chars = preg_split('//', $attr);
			foreach ($chars as $c){
				if      ($c == "c") { $current =& $this->do_comma_quotes; }
				else if ($c == "g") { $current =& $this->do_guillemets; }
				else if ($c == ":") { $current =& $this->do_space_colon; }
				else if ($c == ";") { $current =& $this->do_space_semicolon; }
				else if ($c == "m") { $current =& $this->do_space_marks; }
				else if ($c == "h") { $current =& $this->do_space_emdash; }
				else if ($c == "H") { $current =& $this->do_space_endash; }
				else if ($c == "f") { $current =& $this->do_space_frenchquote; }
				else if ($c == "t") { $current =& $this->do_space_thousand; }
				else if ($c == "u") { $current =& $this->do_space_unit; }
				else if ($c == "+") {
					$current = 2;
					unset($current);
				}
				else if ($c == "-") {
					$current = -1;
					unset($current);
				}
				else {
					# Unknown attribute option, ignore.
				}
				$current = 1;
			}
		}
	}


	function educate($t, $prev_token_last_char) {
		$t = parent::educate($t, $prev_token_last_char);
		
		if ($this->do_comma_quotes)      $t = $this->educateCommaQuotes($t);
		if ($this->do_guillemets)        $t = $this->educateGuillemets($t);
		
		if ($this->do_space_emdash)      $t = $this->spaceEmDash($t);
		if ($this->do_space_endash)      $t = $this->spaceEnDash($t);
		if ($this->do_space_colon)       $t = $this->spaceColon($t);
		if ($this->do_space_semicolon)   $t = $this->spaceSemicolon($t);
		if ($this->do_space_marks)       $t = $this->spaceMarks($t);
		if ($this->do_space_frenchquote) $t = $this->spaceFrenchQuotes($t);
		if ($this->do_space_thousand)    $t = $this->spaceThousandSeparator($t);
		if ($this->do_space_unit)        $t = $this->spaceUnit($t);
		
		return $t;
	}


	function educateQuotes($_) {
	#
	#   Parameter:  String.
	#
	#   Returns:    The string, with "educated" curly quote HTML entities.
	#
	#   Example input:  "Isn't this fun?"
	#   Example output: &#8220;Isn&#8217;t this fun?&#8221;
	#
		$dq_open  = $this->smart_doublequote_open;
		$dq_close = $this->smart_doublequote_close;
		$sq_open  = $this->smart_singlequote_open;
		$sq_close = $this->smart_singlequote_close;
	
		# Make our own "punctuation" character class, because the POSIX-style
		# [:PUNCT:] is only available in Perl 5.6 or later:
		$punct_class = "[!\"#\\$\\%'()*+,-.\\/:;<=>?\\@\\[\\\\\]\\^_`{|}~]";

		# Special case if the very first character is a quote
		# followed by punctuation at a non-word-break. Close the quotes by brute force:
		$_ = preg_replace(
			array("/^'(?=$punct_class\\B)/", "/^\"(?=$punct_class\\B)/"),
			array($sq_close,                 $dq_close), $_);

		# Special case for double sets of quotes, e.g.:
		#   <p>He said, "'Quoted' words in a larger quote."</p>
		$_ = preg_replace(
			array("/\"'(?=\w)/",     "/'\"(?=\w)/"),
			array($dq_open.$sq_open, $sq_open.$dq_open), $_);

		# Special case for decade abbreviations (the '80s):
		$_ = preg_replace("/'(?=\\d{2}s)/", $sq_close, $_);

		$close_class = '[^\ \t\r\n\[\{\(\-]';
		$dec_dashes = '&\#8211;|&\#8212;';

		# Get most opening single quotes:
		$_ = preg_replace("{
			(
				\\s          |   # a whitespace char, or
				&nbsp;      |   # a non-breaking space entity, or
				--          |   # dashes, or
				&[mn]dash;  |   # named dash entities
				$dec_dashes |   # or decimal entities
				&\\#x201[34];    # or hex
			)
			'                   # the quote
			(?=\\w)              # followed by a word character
			}x", '\1'.$sq_open, $_);
		# Single closing quotes:
		$_ = preg_replace("{
			($close_class)?
			'
			(?(1)|          # If $1 captured, then do nothing;
			  (?=\\s | s\\b)  # otherwise, positive lookahead for a whitespace
			)               # char or an 's' at a word ending position. This
							# is a special case to handle something like:
							# \"<i>Custer</i>'s Last Stand.\"
			}xi", '\1'.$sq_close, $_);

		# Any remaining single quotes should be opening ones:
		$_ = str_replace("'", $sq_open, $_);


		# Get most opening double quotes:
		$_ = preg_replace("{
			(
				\\s          |   # a whitespace char, or
				&nbsp;      |   # a non-breaking space entity, or
				--          |   # dashes, or
				&[mn]dash;  |   # named dash entities
				$dec_dashes |   # or decimal entities
				&\\#x201[34];    # or hex
			)
			\"                   # the quote
			(?=\\w)              # followed by a word character
			}x", '\1'.$dq_open, $_);

		# Double closing quotes:
		$_ = preg_replace("{
			($close_class)?
			\"
			(?(1)|(?=\\s))   # If $1 captured, then do nothing;
							   # if not, then make sure the next char is whitespace.
			}x", '\1'.$dq_close, $_);

		# Any remaining quotes should be opening ones.
		$_ = str_replace('"', $dq_open, $_);

		return $_;
	}


	function educateCommaQuotes($_) {
	#
	#   Parameter:  String.
	#   Returns:    The string, with ,,comma,, -style double quotes
	#               translated into HTML curly quote entities.
	#
	#   Example input:  ,,Isn't this fun?,,
	#   Example output: &#8222;Isn't this fun?&#8222;
	#
	# Note: this is meant to be used alongside with backtick quotes; there is 
	# no language that use only lower quotations alone mark like in the example.
	#
		$_ = str_replace(",,", '&#8222;', $_);
		return $_;
	}


	function educateGuillemets($_) {
	#
	#   Parameter:  String.
	#   Returns:    The string, with << guillemets >> -style quotes
	#               translated into HTML guillemets entities.
	#
	#   Example input:  << Isn't this fun? >>
	#   Example output: &#8222; Isn't this fun? &#8222;
	#
		$_ = preg_replace("/(?:<|&lt;){2}/", '&#171;', $_);
		$_ = preg_replace("/(?:>|&gt;){2}/", '&#187;', $_);
		return $_;
	}


	function spaceFrenchQuotes($_) {
	#
	#	Parameters: String, replacement character, and forcing flag.
	#	Returns:    The string, with appropriates spaces replaced 
	#				inside french-style quotes, only french quotes.
	#
	#	Example input:  Quotes in « French », »German« and »Finnish» style.
	#	Example output: Quotes in «_French_», »German« and »Finnish» style.
	#
		$opt = ( $this->do_space_frenchquote ==  2 ? '?' : '' );
		$chr = ( $this->do_space_frenchquote != -1 ? $this->space_frenchquote : '' );
		
		# Characters allowed immediatly outside quotes.
		$outside_char = $this->space . '|\s|[.,:;!?\[\](){}|@*~=+-]|¡|¿';
		
		$_ = preg_replace(
			"/(^|$outside_char)(&#171;|«|&#8250;|‹)$this->space$opt/",
			"\\1\\2$chr", $_);
		$_ = preg_replace(
			"/$this->space$opt(&#187;|»|&#8249;|›)($outside_char|$)/", 
			"$chr\\1\\2", $_);
		return $_;
	}


	function spaceColon($_) {
	#
	#	Parameters: String, replacement character, and forcing flag.
	#	Returns:    The string, with appropriates spaces replaced 
	#				before colons.
	#
	#	Example input:  Ingredients : fun.
	#	Example output: Ingredients_: fun.
	#
		$opt = ( $this->do_space_colon ==  2 ? '?' : '' );
		$chr = ( $this->do_space_colon != -1 ? $this->space_colon : '' );
		
		$_ = preg_replace("/$this->space$opt(:)(\\s|$)/m",
						  "$chr\\1\\2", $_);
		return $_;
	}


	function spaceSemicolon($_) {
	#
	#	Parameters: String, replacement character, and forcing flag.
	#	Returns:    The string, with appropriates spaces replaced 
	#				before semicolons.
	#
	#	Example input:  There he goes ; there she goes.
	#	Example output: There he goes_; there she goes.
	#
		$opt = ( $this->do_space_semicolon ==  2 ? '?' : '' );
		$chr = ( $this->do_space_semicolon != -1 ? $this->space_semicolon : '' );
		
		$_ = preg_replace("/$this->space(;)(?=\\s|$)/m", 
						  " \\1", $_);
		$_ = preg_replace("/((?:^|\\s)(?>[^&;\\s]+|&#?[a-zA-Z0-9]+;)*)".
						  " $opt(;)(?=\\s|$)/m", 
						  "\\1$chr\\2", $_);
		return $_;
	}


	function spaceMarks($_) {
	#
	#	Parameters: String, replacement character, and forcing flag.
	#	Returns:    The string, with appropriates spaces replaced 
	#				around question and exclamation marks.
	#
	#	Example input:  ¡ Holà ! What ?
	#	Example output: ¡_Holà_! What_?
	#
		$opt = ( $this->do_space_marks ==  2 ? '?' : '' );
		$chr = ( $this->do_space_marks != -1 ? $this->space_marks : '' );

		// Regular marks.
		$_ = preg_replace("/$this->space$opt([?!]+)/", "$chr\\1", $_);

		// Inverted marks.
		$imarks = "(?:¡|&iexcl;|&#161;|&#x[Aa]1;|¿|&iquest;|&#191;|&#x[Bb][Ff];)";
		$_ = preg_replace("/($imarks+)$this->space$opt/", "\\1$chr", $_);
	
		return $_;
	}


	function spaceEmDash($_) {
	#
	#	Parameters: String, two replacement characters separated by a hyphen (`-`),
	#				and forcing flag.
	#
	#	Returns:    The string, with appropriates spaces replaced 
	#				around dashes.
	#
	#	Example input:  Then — without any plan — the fun happend.
	#	Example output: Then_—_without any plan_—_the fun happend.
	#
		$opt = ( $this->do_space_emdash ==  2 ? '?' : '' );
		$chr = ( $this->do_space_emdash != -1 ? $this->space_emdash : '' );
		$_ = preg_replace("/$this->space$opt(&#8212;|—)$this->space$opt/", 
			"$chr\\1$chr", $_);
		return $_;
	}
	
	
	function spaceEnDash($_) {
	#
	#	Parameters: String, two replacement characters separated by a hyphen (`-`),
	#				and forcing flag.
	#
	#	Returns:    The string, with appropriates spaces replaced 
	#				around dashes.
	#
	#	Example input:  Then — without any plan — the fun happend.
	#	Example output: Then_—_without any plan_—_the fun happend.
	#
		$opt = ( $this->do_space_endash ==  2 ? '?' : '' );
		$chr = ( $this->do_space_endash != -1 ? $this->space_endash : '' );
		$_ = preg_replace("/$this->space$opt(&#8211;|–)$this->space$opt/", 
			"$chr\\1$chr", $_);
		return $_;
	}


	function spaceThousandSeparator($_) {
	#
	#	Parameters: String, replacement character, and forcing flag.
	#	Returns:    The string, with appropriates spaces replaced 
	#				inside numbers (thousand separator in french).
	#
	#	Example input:  Il y a 10 000 insectes amusants dans ton jardin.
	#	Example output: Il y a 10_000 insectes amusants dans ton jardin.
	#
		$chr = ( $this->do_space_thousand != -1 ? $this->space_thousand : '' );
		$_ = preg_replace('/([0-9]) ([0-9])/', "\\1$chr\\2", $_);
		return $_;
	}


	var $units = '
		### Metric units (with prefixes)
		(?:
			p |
			µ | &micro; | &\#0*181; | &\#[xX]0*[Bb]5; |
			[mcdhkMGT]
		)?
		(?:
			[mgstAKNJWCVFSTHBL]|mol|cd|rad|Hz|Pa|Wb|lm|lx|Bq|Gy|Sv|kat|
			Ω | Ohm | &Omega; | &\#0*937; | &\#[xX]0*3[Aa]9;
		)|
		### Computers units (KB, Kb, TB, Kbps)
		[kKMGT]?(?:[oBb]|[oBb]ps|flops)|
		### Money
		¢ | &cent; | &\#0*162; | &\#[xX]0*[Aa]2; |
		M?(?:
			£ | &pound; | &\#0*163; | &\#[xX]0*[Aa]3; |
			¥ | &yen;   | &\#0*165; | &\#[xX]0*[Aa]5; |
			€ | &euro;  | &\#0*8364; | &\#[xX]0*20[Aa][Cc]; |
			$
		)|
		### Other units
		(?: ° | &deg; | &\#0*176; | &\#[xX]0*[Bb]0; ) [CF]? | 
		%|pt|pi|M?px|em|en|gal|lb|[NSEOW]|[NS][EOW]|ha|mbar
		'; //x

	function spaceUnit($_) {
	#
	#	Parameters: String, replacement character, and forcing flag.
	#	Returns:    The string, with appropriates spaces replaced
	#				before unit symbols.
	#
	#	Example input:  Get 3 mol of fun for 3 $.
	#	Example output: Get 3_mol of fun for 3_$.
	#
		$opt = ( $this->do_space_unit ==  2 ? '?' : '' );
		$chr = ( $this->do_space_unit != -1 ? $this->space_unit : '' );

		$_ = preg_replace('/
			(?:([0-9])[ ]'.$opt.') # Number followed by space.
			('.$this->units.')     # Unit.
			(?![a-zA-Z0-9])  # Negative lookahead for other unit characters.
			/x',
			"\\1$chr\\2", $_);

		return $_;
	}


	function spaceAbbr($_) {
	#
	#	Parameters: String, replacement character, and forcing flag.
	#	Returns:    The string, with appropriates spaces replaced
	#				around abbreviations.
	#
	#	Example input:  Fun i.e. something pleasant.
	#	Example output: Fun i.e._something pleasant.
	#
		$opt = ( $this->do_space_abbr ==  2 ? '?' : '' );
		
		$_ = preg_replace("/(^|\s)($this->abbr_after) $opt/m",
			"\\1\\2$this->space_abbr", $_);
		$_ = preg_replace("/( )$opt($this->abbr_sp_before)(?![a-zA-Z'])/m", 
			"\\1$this->space_abbr\\2", $_);
		return $_;
	}


	function stupefyEntities($_) {
	#
	#   Adding angle quotes and lower quotes to SmartyPants's stupefy mode.
	#
		$_ = parent::stupefyEntities($_);

		$_ = str_replace(array('&#8222;', '&#171;', '&#187'), '"', $_);

		return $_;
	}


	function processEscapes($_) {
	#
	#   Adding a few more escapes to SmartyPants's escapes:
	#
	#               Escape  Value
	#               ------  -----
	#               \,      &#44;
	#               \<      &#60;
	#               \>      &#62;
	#
		$_ = parent::processEscapes($_);

		$_ = str_replace(
			array('\,',    '\<',    '\>',    '\&lt;', '\&gt;'),
			array('&#44;', '&#60;', '&#62;', '&#60;', '&#62;'), $_);

		return $_;
	}
}


/*

PHP SmartyPants Typographer
===========================

Version History
---------------

1.0 (28 Jun 2006)

*   First public release of PHP SmartyPants Typographer.


Bugs
----

To file bug reports or feature requests (other than topics listed in the
Caveats section above) please send email to:

<michel.fortin@michelf.com>

If the bug involves quotes being curled the wrong way, please send example
text to illustrate.


### Algorithmic Shortcomings ###

One situation in which quotes will get curled the wrong way is when
apostrophes are used at the start of leading contractions. For example:

	'Twas the night before Christmas.

In the case above, SmartyPants will turn the apostrophe into an opening
single-quote, when in fact it should be a closing one. I don't think
this problem can be solved in the general case -- every word processor
I've tried gets this wrong as well. In such cases, it's best to use the
proper HTML entity for closing single-quotes (`&#8217;`) by hand.


Copyright and License
---------------------

PHP SmartyPants & Typographer  
Copyright (c) 2004-2006 Michel Fortin  
<http://www.michelf.com>  
All rights reserved.

Original SmartyPants  
Copyright (c) 2003-2004 John Gruber  
<http://daringfireball.net/>  
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

*	Redistributions of source code must retain the above copyright
	notice, this list of conditions and the following disclaimer.

*	Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in the
	documentation and/or other materials provided with the distribution.

*	Neither the name "SmartyPants" nor the names of its contributors may
	be used to endorse or promote products derived from this software
	without specific prior written permission.

This software is provided by the copyright holders and contributors "as is"
and any express or implied warranties, including, but not limited to, the 
implied warranties of merchantability and fitness for a particular purpose 
are disclaimed. In no event shall the copyright owner or contributors be 
liable for any direct, indirect, incidental, special, exemplary, or 
consequential damages (including, but not limited to, procurement of 
substitute goods or services; loss of use, data, or profits; or business 
interruption) however caused and on any theory of liability, whether in 
contract, strict liability, or tort (including negligence or otherwise) 
arising in any way out of the use of this software, even if advised of the
possibility of such damage.

*/
?>