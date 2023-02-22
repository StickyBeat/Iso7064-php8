<?php
/**
 * This file is part of Soundways\Iso7064
 *
 * (c) Soundways <team@soundways.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code
 */

namespace Soundways\Iso7064;

use Soundways\Iso7064\Iso7064FormattingException;

/**
 * This is the Mod class.
 * To implement a new Modulus, extend this class
 * and override generateCheckChar().
 *
 * @author Bridget Macfarlane <bridget@soundways.com>
 */
abstract class Mod
{
	/**
	 * The string to be encoded/checked
	 *
	 * @var string
	 */
	protected $code;
	
	/**
	 * The numerical values assigned to valid alphanumeric
	 * characters according to ISO 7064
	 *
	 * @var array
	 */
	protected static $char_table = [
		'0', '1', '2', '3', '4', '5', 
		'6', '7', '8', '9', 'A', 'B',
		'C', 'D', 'E', 'F', 'G', 'H',
		'I', 'J', 'K', 'L', 'M', 'N',
		'O', 'P', 'Q', 'R', 'S', 'T',
		'U', 'V', 'W', 'X', 'Y', 'Z',
		'0' => 0,  '1' => 1,  '2' => 2,
		'3' => 3,  '4' => 4,  '5' => 5,
		'6' => 6,  '7' => 7,  '8' => 8,
		'9' => 9,  'A' => 10, 'B' => 11,
		'C' => 12, 'D' => 13, 'E' => 14,
		'F' => 15, 'G' => 16, 'H' => 17,
		'I' => 18, 'J' => 19, 'K' => 20,
		'L' => 21, 'M' => 22, 'N' => 23,
		'O' => 24, 'P' => 25, 'Q' => 26,
		'R' => 27, 'S' => 28, 'T' => 29,
		'U' => 30, 'V' => 31, 'W' => 32,
		'X' => 33, 'Y' => 34, 'Z' => 35,
	];

	/**
	 * Create a new Mod instance.
	 *
	 * @param string $code
	 *
	 * @return void
	 */
	public function __construct(string $code) {
    $this->validateInput($code);
		$this->code = self::parseCode($code);
	}
	
	/**
	 * Generate a check character for the current code,
	 * then append it to and return the current code.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string Code with newly generated check character
	 */
	public function encode(): string {
		$this->code .= $this->generateCheckChar();
		return $this->code;
	}
	
	/**
	 * This must be implemented by the child Mod
	 *
	 * @return string Generated check character
	 */
	abstract public function generateCheckChar(): string;
	
	/**
	 * Assuming the last character in the given code is 
	 * a check character, validates the check character
	 * for the given code.  Will use the class's code if
	 * one is not passed as an argument.
	 *
	 * @return bool True if valid, false if invalid
	 */
	public function validateCheckChar(): bool {
		$check_char = $this->getCheckChar();
		$valid_check_char = $this->generateCheckChar(substr($this->code, 0, -1));
		return ($check_char == $valid_check_char);
	}

  private function validateInput(string $code): void {
    preg_match_all('/[^0-9A-Za-z]/', $code, $matches);
    if (!empty($matches[0])) {
      throw new Iso7064FormattingException('Invalid characters in code: '
                                . implode(', ', $matches[0]));
    }
  }
	
	/**
	 * Setter for $this->code.
	 *
	 * @param string $code
	 *
	 * @return void
	 */
	public function setCode(string $code): void {
    $this->validateInput($code);
		$this->code = self::parseCode($code);
	}
	
	/**
	 * Getter for $this->code.
	 *
	 * @return string 
	 */
	public function getCode(): string {
		return $this->code;
	}

	/**
	 * Format the code with given sequence lengths and delimiter
	 * 
	 * @param  array  $lengths    The lengths of each sequence
	 * @param  string $delimiter
	 *
	 * @throws Iso7064FormattingException 
	 * 
	 * @return string
	 */
	public function format(array $lengths, string $delimiter): string {
		if (strpos($delimiter, "\\") || strpos($delimiter, "\\") === 0) {
			throw new Iso7064FormattingException('Do not use backslashes or ' 
			                                    .'escaped characters as the '
			                                    .'delimiter.');
		}
		if (array_sum($lengths) != mb_strlen($this->code)) {
			$err = 'The sum of the given sequence lengths ('
			     . (string) array_sum($lengths)
			     . ') is not equal to the length of the code ('
			     . (string) mb_strlen($this->code)
			     . ').';
			throw new Iso7064FormattingException($err);
		}

		$pattern = '/';
		$replace = '';

		for($capture = 1; $capture <= count($lengths); $capture++) {
			$pattern .= "([0-9A-Z]{{$lengths[$capture-1]}})";
			$replace .= "\\$capture$delimiter";
		}

		$pattern .= '/';
		$replace = rtrim($replace, $delimiter);

		$formatted_code = preg_filter($pattern, $replace, $this->code);

		return $formatted_code;
	}
	
	/**
	 * Returns the last character in the given string
	 * or the instance's code, assuming that character
	 * is a check character.
	 *
	 * @return string Check character
	 */
	public function getCheckChar(): string {
		return substr($this->code, -1);
	}
	
	/**
	 * Converts an integer value to it's respective character
	 * or vice versa as per the ISO 7064 character table.
	 * 
	 * @param  string|int $lookup Character or integer to convert
	 * 
	 * @return int|string The converted value or character of $lookup
	 */
	protected static function convertCharVal($lookup) {
		return self::$char_table[$lookup];
	}
	
	/**
	 * Converts code for use in calculation.
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	protected static function parseCode(string $code): string {
		$code = preg_replace('/[^0-9A-Za-z]/', '', $code);
		return strtoupper($code);
	}
}