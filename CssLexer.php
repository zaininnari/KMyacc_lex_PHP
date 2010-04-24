<?php
/**
 * PHP 5.3 or higher is required.
 *
 * base : http://d.hatena.ne.jp/amachang/20080502/1209732467
 * @license MIT License
 */
require_once dirname(__FILE__) . '/Lexer.php';

class CssLexer extends Lexer
{
	protected static $ignore = true;
	protected static $ignoreToken = array('COMMENT');

	protected static $lex = array(
		/**
		 * def :
		 * array(
		 * 	<string>, // token name
		 * 	<string>  // regex
		 * );
		 *
		 * rule :
		 * array(
		 * 	<string>,           // token name
		 * 	<string>,           // regex
		 * 	<string> or <null>, // start state : <STRING> the STRING start condition
		 * 	<string> or <null>, // end state   : %s Start Conditions (default is 'INITIAL')
		 * 	<boolean> or <null> // isLiteral
		 * )
		 */
		'defs' => array(
			array('h',        '[0-9a-fA-F]'),
			array('nonascii', '[\200-\377]'),
			array('unicode',  '\\\[0-9a-fA-F]{1,6}[ \t\r\n\f]?'), // \\{h}{1,6}[ \t\r\n\f]?
			array('escape',   '{unicode}|\\\[ -~\200-\377]'), // {unicode}|\\[ -~\200-\377]
			array('nmstart',  '[_a-zA-Z]|{nonascii}|{escape}'),
			array('nmchar',   '[_a-zA-Z0-9-]|{nonascii}|{escape}'),
			array('string1',  '"([\t !#$%&(-~]|\\\\{nl}|\\\'|{nonascii}|{escape})*"'), // string1         \"([\t !#$%&(-~]|\\{nl}|\'|{nonascii}|{escape})*\"
			array('string2',  '\\\'([\t !#$%&(-~]|\\\\{nl}|"|{nonascii}|{escape})*\\\''), // string2         \'([\t !#$%&(-~]|\\{nl}|\"|{nonascii}|{escape})*\'
			array('hexcolor', '{h}{3}|{h}{6}'),

			array('ident',   '-?{nmstart}{nmchar}*'),
			array('name',   '{nmchar}+'),
			array('num',    '[0-9]+|[0-9]*\.[0-9]+'),
			array('intnum', '[0-9]+'),
			array('string', '{string1}|{string2}'),
			array('url',    '([!#$%&*-~]|{nonascii}|{escape})*'),
			array('w',      '[ \t\r\n\f]*'),
			array('nl',     '\n|\r\n|\r|\f'),
			array('range',  '\?{1,6}|{h}(\?{0,5}|{h}(\?{0,4}|{h}(\?{0,3}|{h}(\?{0,2}|{h}(\??|{h})))))'), // range           \?{1,6}|{h}(\?{0,5}|{h}(\?{0,4}|{h}(\?{0,3}|{h}(\?{0,2}|{h}(\??|{h})))))
			array('nth',    '[\+-]?{intnum}*n([\+-]{intnum})?'),

		),

		'rules' => array(
			array('COMMENT',       '\/\*[^*]*\*+([^\/*][^\*]*\*+)*\/'), // \/\*[^*]*\*+([^/*][^*]*\*+)*\/
			array('WHITESPACE',    '[ \t\r\n\f]+'),

			array('SGML_CD',       '<!--|-->'),
			array('INCLUDES',      '~='),
			array('DASHMATCH',     '\\|='), // '|='
			array('BEGINSWITH',    '^='),
			array('ENDSWITH',      '$='),
			array('CONTAINS',      '\\*='), // '*='

			array('MEDIA_NOT',     'not',  'mediaquery'),
			array('MEDIA_ONLY',    'only', 'mediaquery'),
			array('MEDIA_AND',     'and',  'mediaquery'),
			array('VARIABLES_FOR', 'for',  null, 'forkeyword'),

			array('STRING',        '{string}'),
			array('IDENT',         '{ident}'),
			array('NTH',           '{nth}'),

			array('HEX', '#{hexcolor}'),
			array('IDSEL', '#{ident}'),
			array('IMPORT_SYM', '@import' , null, 'mediaquery'),
			array('PAGE_SYM', '@page'),
			array('MEDIA_SYM', '@media', null, 'mediaquery'),
			array('FONT_FACE_SYM', '@font-face'),
			array('CHARSET_SYM', '@charset'),
			array('NAMESPACE_SYM', '@namespace'),
			array('WEBKIT_RULE_SYM', '@-webkit-rule'),
			array('WEBKIT_DECLS_SYM', '@-webkit-decls'),
			array('WEBKIT_VALUE_SYM', '@-webkit-value'),
			array('WEBKIT_MEDIAQUERY_SYM', '@-webkit-mediaquery', null, 'mediaquery'),
			array('WEBKIT_SELECTOR_SYM', '@-webkit-selector'),
			array('WEBKIT_VARIABLES_SYM', '@-webkit-variables', null, 'mediaquery'),
			array('WEBKIT_DEFINE_SYM', '@-webkit-define', null, 'forkeyword'),
			array('WEBKIT_VARIABLES_DECLS_SYM', '@-webkit-variables-decls'),
			array('WEBKIT_KEYFRAMES_SYM', '@-webkit-keyframes'),
			array('WEBKIT_KEYFRAME_RULE_SYM', '@-webkit-keyframe-rule'),

			array('ATKEYWORD', '@{ident}'),

			array('IMPORTANT_SYM', '!{w}important'),

			array('EMS', '{num}em'),
			array('REMS', '{num}rem '),
			array('QEMS', '{num}__qem'),
			array('EXS', '{num}ex'),
			array('PXS', '{num}px'),
			array('CMS', '{num}cm'),
			array('MMS', '{num}mm'),
			array('INS', '{num}in'),
			array('PTS', '{num}pt'),
			array('PCS', '{num}pc'),
			array('DEGS', '{num}deg'),
			array('RADS', '{num}rad'),
			array('GRADS', '{num}grad'),
			array('TURNS', '{num}turn'),
			array('MSECS', '{num}ms'),
			array('SECS', '{num}s'),
			array('HERZ', '{num}Hz'),
			array('KHERZ', '{num}kHz'),
			array('DIMEN', '{num}{ident}'),
			array('PERCENTAGE', '{num}%+'),
			array('INTEGER', '{intnum}'),
			array('FLOATTOKEN', '{num}'),

			array('NOTFUNCTION',  'not\('),
			array('URI',          'url\({w}{string}{w}\)|url\({w}{url}{w}\)'),
			array('VARCALL',      '-webkit-var\({w}{ident}{w}\)'),

			////////////////////////////////////////////////////////
			// original 'FUNCTION'.
			// but,
			// > class test{const FUNCTION = 1;}
			// Parse error: syntax error, unexpected T_FUNCTION
			//
			// replace 'FUNCTION' -> 'T_FUNCTION'
			////////////////////////////////////////////////////////
			array('T_FUNCTION',     '{ident}\('),
			array('UNICODERANGE', 'U\+{range}|U\+{h}{1,6}-{h}{1,6}'),

			array('{', '{', 'mediaquery', 'INITIAL', true),
			array(';', ';', 'mediaquery', 'INITIAL', true),

			array('.', '\.', null, null, true),
		)
	);

}