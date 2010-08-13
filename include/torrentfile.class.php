<?php
/**
 * TorrentFile class.
 * @author Andrey Eroftiev <sparxxx.at@gmail.com>
 * @version $Id$
 */
class TorrentFile extends File {	
	/**
	 * Torrent file parsed data
	 * @var array
	 */
	private $data;
	
	/**
	 * Original torrent file content
	 * @var string
	 */
	private $content;
	
	/**
	 * Indicates if torrent data was changed or not
	 * @var bool
	 */
	private $changed;
	
	/**
	 * Creates and initializes new instance of TorrentFile
	 * 
	 * @param $filename string
	 */
	public function __construct($filename) {
		if (!$fp = fopen($filename, 'rb')) {
			// TODO: throw exception
		}
		$this->name = $filename;
		
		$this->content = fread($fp, filesize($filename));
		fclose($fp);
		
		$data = BEncoder::bdecode($this->content);
		
		$this->changed = false;
	}
	
	/**
	 * Returns torrent file content
	 * 
	 * @return string
	 */
	public function getDataString() {
		return ($this->changed) ? BEncoder::bencode($this->data) : $this->data;
	}
	
	/**
	 * Returns announce URLs
	 * 
	 * @return list
	 */
	public function getAnnounce() {
		return $this->data['announce'];
	}
	
	/**
	 * Appends announce URLs to exsisting set
	 * 
	 * Method checks whether any of new URLs
	 * are already in the list and appends
	 * unique URLs only
	 * 
	 * @param $urls list
	 */
	public function appendAnnounce($urls) {
		foreach ($urls as $newurl) {
			$orig = true;
			foreach ($this->data['announce'] as $url) {
				if ($newurl == $url) {
					$orig = false;
					break;
				}
			}
			if ($orig) {
				$this->data['announce'][] = $newurl;
				$this->changed = true;
			}
		}
	}
	
	/**
	 * Sets announce URLs (completely rewrites
	 * existing set)
	 * 
	 * @param $urls list
	 */
	public function setAnnounce($urls) {
		$this->data['announce'] = $urls;
		$this->changed = true;
	}
	
	/**
	 * Returns list of Files
	 * 
	 * @return list
	 */
	public function getFileList() {
		$info = $this->data['info'];
		if (isset($info['length'])) {
			return array(new File($info['name'], $info['length']));
		}
		else {
			$files = array();
			foreach ($info['files'] as $file) {
				$path = implode("/", $file['path']);
				$files[] = new File($path, $file['length']);
			}
			return $files;
		}
	}
}
