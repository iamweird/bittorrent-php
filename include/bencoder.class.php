<?php
/**
 * Encode and decode data in BitTorrent format
 *
 * Partially based on
 *   File_Bittorrent2 PEAR package by Markus Tacker <m@tacker.org>
 * Partially based on
 *   OpenTracker by WhitSoft: http://www.whitsoftdev.com/opentracker/
 * BEncoding is a simple, easy to implement method of associating
 * data types with information in a file. The values in a torrent
 * file are bEncoded.
 * There are 4 different data types that can be bEncoded:
 * Integers, Strings, Lists and Dictionaries.
 * [http://www.monduna.com/bt/faq.html]
 *
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
     * @param string $str
     * @return mixed decoded data
     * @throws TODO: make it throw smth
     */
	public static function bdecode($str) {
		$pos = 0;
		return self::bdecode_r($str, $pos);
	}
	
	/**
	 * Decode a BEncoded string recursively
	 * 
	 * @param string $str string to decode
	 * @param int $pos current position
	 * @return mixed decoded data
	 */
	private static function bdecode_r($str, &$pos) {
		$strlen = strlen($str);
		if (($pos < 0) || ($pos >= $strlen)) {
			// TODO: throw exception instead of returning null
			return null;
		}
		else if ($str[$pos] == 'i') {
			$pos++;
			$numlen = strspn($str, '-0123456789', $pos);
			$spos = $pos;
			$pos += $numlen;
			if (($pos >= $strlen) || ($str[$pos] != 'e')) {
				// TODO: throw exception instead of returning null
				return null;
			}
			else {
				$pos++;
				return intval(substr($str, $spos, $numlen));
			}
		}
		else if ($str[$pos] == 'd') {
			$pos++;
			$ret = array();
			while ($pos < $strlen) {
				if ($str[$pos] == 'e') {
					$pos++;
					return $ret;
				}
				else {
					$key = self::bdecode_r($str, $pos);
					if (is_null($key)) {
						// TODO: throw exception instead of returning null
						return null;
					}
					else {
						$val = self::bdecode_r($str, $pos);
						if (is_null($val)) {
							// TODO: throw exception instead of returning null
							return null;
						}
						else if (!is_array($key)) {
							$ret[$key] = $val;
						}
					}
				}
			}
			// TODO: throw exception instead of returning null
			return null;
		}
		else if ($str[$pos] == 'l') {
			$pos++;
			$ret = array();
			while ($pos < $strlen) {
				if ($str[$pos] == 'e') {
					$pos++;
					return $ret;
				}
				else {
					$val = self::bdecode_r($str, $pos);
					if (is_null($val)) {
						// TODO: throw exception instead of returning null
						return null;
					}
					else {
						$ret[] = $val;
					}
				}
			}
			// TODO: throw exception instead of returning null
			return null;
		}
		else {
			$numlen = strspn($str, '0123456789', $pos);
			$spos = $pos;
			$pos += $numlen;
			if (($pos >= $strlen) || ($str[$pos] != ':')) {
				// TODO: throw exception instead of returning null
				return null;
			}
			else {
				$vallen = intval(substr($str, $spos, $numlen));
				$pos++;
				$val = substr($str, $pos, $vallen);
				if (strlen($val) != $vallen) {
					// TODO: throw exception instead of returning null
					return null;
				}
				else {
					$pos += $vallen;
					return $val;
				}
			}
		}
	}
}
















