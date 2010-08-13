<?php
/**
 * TorrentFile class.
 * @author Andrey Eroftiev <sparxxx.at@gmail.com>
 * @version $Id$
 */
class TorrentFile {
	/**
	 * Torrent file name
	 * @var string
	 */
	private $filename;
	/**
	 * Torrent file internal data
	 * @var array
	 */
	private $data;
	
	/**
	 * Creates and initializes new instance of TorrentFile
	 * 
	 * @param $filename string
	 */
	public function __construct($filename) {
		if (!$fp = fopen($filename, 'rb')) {
			// TODO: throw exception
		}
		$this->filename = $filename;
		
		$fc = fread($fp, filesize($filename));
		fclose($fp);
		
		$data = BEncoder::bdecode($fc);
	}
}
