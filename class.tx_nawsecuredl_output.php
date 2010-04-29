<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2007 Dietrich Heise (typo3-ext(at)naw.info)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * @author	Dietrich Heise <typo3-ext(at)naw.info>
 */


// *******************************
// Set error reporting
// *******************************
//error_reporting (E_ALL ^ E_NOTICE);


class tx_nawsecuredl_output {

	protected $arrExtConf = array();

	/**
	 * The init Function, to check the access rights
	 *
	 * @return void
	 */
	function init(){

		$this->arrExtConf = $this->GetExtConf();

		$this->u = intval(t3lib_div::_GP('u'));
		if (!$this->u){
			$this->u = 0;
		}

		$this->hash = t3lib_div::_GP('hash');
		$this->t = t3lib_div::_GP('t');
		$this->file = t3lib_div::_GP('file');
		$key = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];

		$this->data = $this->u.$this->file.$this->t;
		$this->checkhash = hash_hmac('md5', $this->data, $key);

		// Hook for init:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['init'])) {
			$_params = array('pObj' => &$this);
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['init'] as $_funcRef)   {
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		if ($this->checkhash != $this->hash){
			header('HTTP/1.1 403 Forbidden');
			exit ('Access denied!');
		}

		if (intval($this->t) < time()){
			header('HTTP/1.1 403 Forbidden');
			exit ('Access denied!');
		}

		$this->feUserObj = tslib_eidtools::initFeUser();
		tslib_eidtools::connectDB();

		if ($this->u != 0) {
			$feuser = $this->feUserObj->user['uid'];
			if ($this->u != $feuser){
				header('HTTP/1.1 403 Forbidden');
				exit ('Access denied!');
			}
		}
	}


	/**
	 * Output the requested file
	 *
	 * @param data $file
	 */
	function fileOutput($file){

		$file = PATH_site.'/'.$this->file;

		// Hook for pre-output:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['preOutput'])) {
			$_params = array('pObj' => &$this);
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['preOutput'] as $_funcRef)   {
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		if (file_exists($file)){

			if (!$this->arrExtConf['logOnlyIfComplete']) {
				$this->logDownload();
			}

			// files bigger than 32MB are now 'application/octet-stream' by default (getimagesize memory_limit problem)
			if (filesize($file)<1024*1024*32){
				$bildinfos=@getimagesize($file);
				$bildtypnr=$bildinfos[2];
			}

			$contenttype[1]='image/gif';
			$contenttype[2]='image/jpeg';
			$contenttype[3]='image/png';

			$contenttypedatei='';
			$contenttypedatei=$contenttype[$bildtypnr];

			if ($contenttypedatei=='') // d.h. wenn noch nicht gesetzt:
			/* try to get the filetype from the fileending */
			{
				$endigung=strtolower(strrchr($file,'.'));
				//alles ab dem letzten Punkt

				if ($this->arrExtConf['forcedownload'] == 1){
					$forcetypes = explode("|",$this->arrExtConf['forcedownloadtype']);
					if (is_array($forcetypes)){
						if (in_array(substr($endigung, 1),$forcetypes)) {
							$forcedownload = true;
						}
					}
				}

				switch(strtolower($endigung)){


					case '.pps':
						$contenttypedatei='application/vnd.ms-powerpoint';
						break;
						##### Microsoft Powerpoint Dateien
					case '.doc':
						$contenttypedatei='application/msword';
						break;
						##### Microsoft Word Dateien
					case '.xls':
						$contenttypedatei='application/vnd.ms-excel';
						break;
						##### Microsoft Excel Dateien

						//TODO: add MS-Office 2007 XML-filetypes

					case '.jpeg':
						$contenttypedatei='image/jpeg';
						break;
						##### JPEG-Dateien
					case '.jpg':
						$contenttypedatei='image/jpeg';
						break;
						##### JPEG-Dateien
					case '.jpe':
						$contenttypedatei='image/jpeg';
						break;
						##### JPEG-Dateien
					case '.mpeg':
						$contenttypedatei='video/mpeg';
						break;
						##### MPEG-Dateien
					case '.mpg':
						$contenttypedatei='video/mpeg';
						break;
						##### MPEG-Dateien
					case '.mpe':
						$contenttypedatei='video/mpeg';
						break;
						##### MPEG-Dateien
					case '.mov':
						$contenttypedatei='video/quicktime';
						break;
						##### Quicktime-Dateien
					case '.avi':
						$contenttypedatei='video/x-msvideo';
						break;
						##### Microsoft AVI-Dateien
					case '.pdf':
						$contenttypedatei='application/pdf';
						break;
					case '.svg':
						$contenttypedatei='image/svg+xml';
						break;
						### Flash Video Files
					case '.flv':
						$contenttypedatei='video/x-flv';
						break;
						### Shockwave / Flash
					case '.swf':
						$contenttypedatei='application/x-shockwave-flash';
						break;
					case '.htm':
					case '.html':
						$contenttypedatei = 'text/html';
						break;
					default:
						$contenttypedatei='application/octet-stream';
						break;
				}//end of switch Case structure
			}

			// Hook for output:
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['output'])) {
				$_params = array('pObj' => &$this);
				foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['output'] as $_funcRef)   {
					t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
			}


			header("Pragma: private");
			header("Expires: 0"); // set expiration time
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header('Content-Type: '.$contenttypedatei);
			if ($forcedownload == true){
				header('Content-Disposition: attachment; filename="'.basename($file).'"');
			}else{
				header('Content-Disposition: inline; filename="'.basename($file).'"');
			}

			switch ($this->arrExtConf['outputFunction']) {
				case 'readfile_chunked':
					$this->readfile_chunked($file);
				break;

				case 'fpassthru':
					$handle = fopen($file, 'rb');
					fpassthru($handle);
					fclose($handle);
				break;

				case 'readfile':
					//fallthrough, this is the default case
				default:
					readfile($file);
				break;
			}

			// make sure we can detect an aborted connection, call flush
			flush();
			if ($this->arrExtConf['logOnlyIfComplete'] && !connection_aborted()) {
				$this->logDownload();
			}


		} else {
			print "File does not exists!";
		}
	}

	/**
	 * Log the access of the file
	 *
	 * @return void
	 */
	function logDownload(){
		if (!$this->arrExtConf['log']){ // no logging
			return;
		}

		$insert_array = array (
			'tstamp' => time(),
			'file_name' => $this->file,
			'file_size' => @filesize($this->file),
			'user_id' => intval($this->feUserObj->user['uid']),
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nawsecuredl_counter',$insert_array);
	}


	protected function GetExtConf()
	{
		static $arrExtConf=array();

		if (!$arrExtConf) {
			$arrExtConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['naw_securedl']);
		}

		return $arrExtConf;
	}

	/**
	 * In some cases php needs the filesize as php_memory, so big files cannot
	 * be transferred. This function mitigates this problem.
	 *
	 * @param string $filename
	 */
	protected function readfile_chunked($filename) {
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$timeout = ini_get('max_execution_time');
		$buffer = '';
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			set_time_limit($timeout);
			$buffer = fread($handle, $chunksize);
			print $buffer;
			ob_flush();
			flush();
		}
		return fclose($handle);
	}

}

$securedl = new tx_nawsecuredl_output();
$securedl->init();
$securedl->fileOutput(rawurldecode($securedl->file));

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']);
}
?>