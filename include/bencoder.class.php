<?php
/**
 * Encode and decode data in BitTorrent format
 *
 * Based on
 *   File_Bittorrent2 PEAR package by Markus Tacker <m@tacker.org>
 * Based on
 *   Original Python implementation by Petru Paler <petru@paler.net>
 *   PHP translation by Gerard Krijgsman <webmaster@animesuki.com>
 *   Gerard's regular expressions removed by Carl Ritson <critson@perlfu.co.uk>
 * BEncoding is a simple, easy to implement method of associating
 * data types with information in a file. The values in a torrent
 * file are bEncoded.
 * There are 4 different data types that can be bEncoded:
 * Integers, Strings, Lists and Dictionaries.
 * [http://www.monduna.com/bt/faq.html]
 *
 * @author Markus Tacker <m@tacker.org>
 * @author Andrey Eroftiev <sparxxx.at@gmail.com>
 * @version $Id$
 */
class BEncoder {
	/**
	 * Encode a var in BEncode format
	 * 
	 * @param mixed
	 * @return string
	 * @throws TODO: make it throw something
	 */
	public static function bencode($mixed) {
		switch (gettype($mixed)) {
			case is_null($mixed):
				return self::bencode_string('');
			case 'string':
				return self::bencode_string($mixed);
			case 'integer':
			case 'double':
				return self::bencode_integer(sprintf('.0f', round($mixed)));
			case 'array':
				return self::bencode_array($mixed);
			default:
				// TODO: throw exception
				return '';
		}
	}
	
	/**
	 * BEncodes a string
	 * 
	 * Strings are prefixed with their length followed by a colon.
	 * For example, "Monduna" would bEncode to 7:Monduna and "BitTorrents"
	 * would bEncode to 11:BitTorrents.
	 * 
	 * @param $str
	 * @return unknown_type
	 */
	private static function bencode_string($str) {
		return strlen($str) . ':' . $str;
	}
	
	/**
	 * BEncodes a integer
	 * 
	 * Integers are prefixed with an i and terminated by an e.
	 * For example, 123 would bEcode to i123e, -3272002 would bEncode
	 * to i-3272002e.
	 * 
	 * @param int
	 * @return string
	 */
	private static function bencode_integer($int) {
		return 'i' . $int . 'e';
	}
	
	/**
	 * BEncodes an array
	 * This code assumes arrays with purely integer indexes are lists,
	 * arrays which use string indexes assumed to be dictionaries.
	 *
	 * Dictionaries are prefixed with a d and terminated by an e. They
	 * are similar to list, except that items are in key value pairs. The
	 * dictionary {"key":"value", "Monduna":"com", "bit":"Torrents", "number":7}
	 * would bEncode to d3:key5:value7:Monduna3:com3:bit:8:Torrents6:numberi7ee
	 *
	 * Lists are prefixed with a l and terminated by an e. The list
	 * should contain a series of bEncoded elements. For example, the
	 * list of strings ["Monduna", "Bit", "Torrents"] would bEncode to
	 * l7:Monduna3:Bit8:Torrentse. The list [1, "Monduna", 3, ["Sub", "List"]]
	 * would bEncode to li1e7:Mondunai3el3:Sub4:Listee
	 *
	 * @param array
	 * @return string
	 */
	private static function bencode_array(array $array) {
		// check if we have string keys
		$is_list = true;
		foreach (array_keys($array) as $key) {
			if (!is_int($key)) {
				$is_list = false;
				break;
			}
		}
		
		$result = '';
		if ($is_list) {
			// build a list
			ksort($array, SORT_NUMERIC);
			$result = 'l';
			foreach ($array as $val) {
				$result .= self::bencode($val);
			}
			$result .= 'e';
		}
		else {
			// build a dictionary
			ksort($array, SORT_STRING);
            $result = 'd';
			foreach ($array as $key => $val) {
				$result .= self::bencode(strval($key));
				$result .= self::bencode($val);
			}
			$result .= 'e';
		}
		return $result;
	}
	
	/**
    * Decode a Bencoded string
    *
    * @param string
    * @return mixed
    * @throws TODO: make it throw smth
    */
	public static function bdecode($str, &$position = 0) {
		switch ($str[$position]) {
			case 'i':
				return self::bdecode_integer($str, $position);
			case 'l':
				return self::bdecode_list($str, $position);
			case 'd':
				return self::bdecode_dictionary($str, $position);
			default:
				return self::bdecode_string($str, $position);
		}
	}
	
	/**
	 * Decode a BEncoded integer
	 * 
	 * Integers are prefixed with an i and terminated by an e.
	 * For example, 123 would bEncode to i123e, -3272002 would bEncode
	 * to i-3272002e.
	 * 
	 * @param string
	 * @return int
	 * @throws TODO: make it throw smth on invalid input
	 */
	private static function bdecode_integer($str, &$position = 0) {
		if ($position >= strlen($str) ||
			$str[$position] != 'i' ||
			strpos($str, 'e', $position) === false) {
			// TODO: throw exception
		}
		$result = substr($str, $position + 1, strpos($str, 'e', $position) - 1) + 0;
		$position = strpos($str, 'e', $position) + 1;
		return $result;
	}
	
	/**
	 * Decode a BEncoded list
	 * 
	 * Lists are prefixed with a l and terminated by an e. The list
	 * should contain a series of bEncoded elements. For example, the
	 * list of strings ["Monduna", "Bit", "Torrents"] would bEncode to
	 * l7:Monduna3:Bit8:Torrentse. The list [1, "Monduna", 3, ["Sub", "List"]]
	 * would bEncode to li1e7:Mondunai3el3:Sub4:Listee
	 * 
	 * @param string
	 * @return array
	 * @throws TODO: make it throw smth on invalid input
	 */
	private static function bdecode_list($str, &$position = 0) {
		$result = array();
		if ($position >= strlen($str) ||
			$str[$position] != 'l' ||
			strpos($str, 'e', $position) === false) {
			// TODO: throw exception
		}
		for ($position++; $str[$position] != 'e' && $position < $strlen($str); ) {
			$value = self::bdecode($str, $position);
			$result[] = $value;
		}
		if ($position >= strlen($str) || $str[$position] != 'e') {
			// TODO: throw ecxeption
		}
		return $result;
	}
}
















