<?php
/**
 * PHP 5.3 or higher is required.
 *
 * base : http://d.hatena.ne.jp/amachang/20080502/1209732467
 * @license MIT License
 */

class Lexer
{
	const ENC = 'UTF-8';
	protected static $ignore = false;
	protected static $ignoreToken = array();

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
			array('num',    '[0-9]+|[0-9]*\.[0-9]+'),
			array('intnum', '[0-9]+'),
		),

		'rules' => array(
			array('INTEGER', '{intnum}'),
			array('FLOATTOKEN', '{num}'),
		)
	);

	/**
	 * create rules
	 *
	 * in :
	 * array(
	 * 	'defs'  => array(array('intnum', '[0-9]+')),
	 * 	'rules' => array(array('INTEGER', '{intnum}'))
	 * );
	 *
	 * out :
	 * array(
	 * 	array('INTEGER', '(?:[0-9]+)')
	 * );
	 *
	 * @return Array
	 */
	protected static function createRules()
	{
		$result = array();
		$defs  = static::$lex['defs'];
		$count = count($defs);

		for ($i = 0; $i < $count; $i++) {
			$tmp = $defs[$i][1];
			for ($j = 0; $j < $count; $j++) {
				$tmp = str_replace('{'.$defs[$j][0].'}', '(?:'.$defs[$j][1].')', $tmp);
			}
			$defs[$i][1] = $tmp;
		}

		foreach (static::$lex['rules'] as $n => $rule) {
			$tmp = $rule[1];
			foreach ($defs as $def) {
				$tmp = str_replace('{'.$def[0].'}', '(?:'.$def[1].')', $tmp);
			}
			$result[$n] = array(
				$rule[0],                          // token name
				$tmp,                              // regex
				isset($rule[2]) ? $rule[2] : null, // start state
				isset($rule[3]) ? $rule[3] : null, // end state
				isset($rule[4]) ? $rule[4] : null, // isLiteral
			);
		}
		return $result;
	}

	/**
	 * lexical analyze
	 *
	 * @param string $source source
	 *
	 * @return array($match, $yylval, $lexbuf)
	 */
	public static function lex($source)
	{
		static $rules,$state = 'INITIAL'; // $state : default : INITIAL
		if ($rules === null) $rules = self::createRules();

		$result = $_matches = array();

		foreach ($rules as $rule) {
			if ($rule[2] === null || ($rule[2] !== null && $state === $rule[2])) {
				if (preg_match('/^('.$rule[1].')/i', $source, $matches)) {
					//                token name, token strlen,                           token,       rule
					$_matches[] = array($rule[0], mb_strlen($matches[0], self::ENC), $matches[0], $rule);
				}
			}
		}

		$_state   = null;
		$_literal = false;
		$length   = 0;
		if (count($_matches) !== 0) {
			foreach ($_matches as $match) {
				if ($length < $match[1]) {
					$length   = $match[1];
					$result   = $match;
					$_state   = $match[3][3];
					$_literal = $match[3][4];
				}
			}
			if ($_state !== null) $state = $_state; // use next
			// ignore
			if (static::$ignore === true && in_array($result[0], static::$ignoreToken, true)) {
				$source = mb_substr($source, $result[1], mb_strlen($source, self::ENC) - 1, self::ENC);
				return array(0, $result[2], $source);
			}
			if ($_literal === true) {
				$char = mb_substr($source, 0, 1, self::ENC);
				$result = array(ord($char), 1, $char, null);
			}
		} else {
			$char = mb_substr($source, 0, 1, self::ENC);
			$result = array(ord($char), 1, $char, null);
		}

		$source = mb_substr($source, $result[1], mb_strlen($source, self::ENC) - 1, self::ENC);

		return array($result[0], $result[2], $source);
	}

	/**
	 * set static::$ignore. igonore tokenlist
	 *
	 * @param mix $isIgnore mix cast (boolean)
	 *
	 * @return boolean
	 */
	public static function setIgnore($isIgnore)
	{
		return static::$ignore = (boolean) $isIgnore;
	}

}