<?php
/**
 * Simple file class.
 * 
 * @author Andrey Eroftiev <sparxxx.at@gmail.com>
 * @version $Id$
 */
class File {
	/**
	 * File name
	 * @var string
	 */
	public $name;
	
	/**
	 * File size in bytes
	 * @var int
	 */
	public $size;
	
	/**
	 * Creates and initializes new instance of File
	 * 
	 * @param $filename string
	 * @param $filesize int
	 */
	public function __construct($filename = "", $filesize = 0) {
		$this->name = $filename;
		$this->size = $filesize;
	}
}
