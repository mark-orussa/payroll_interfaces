<?php

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 9/16/2016
 * Time: 1:10 PM
 */
namespace Embassy;

use ErrorException, Exception, Embassy\Ajax;

class Page {
	/**
	 * Build the page content.
	 *
	 * Build the body, javascript includes, meta, css includes, and other foundational html content.
	 *
	 * @author    Mark O'Russa    <mark@orussa.com>
	 * @param    array $javascriptIncludes The userId of the sender.
	 * @param    array $cssIncludes        The userId of the recipient.
	 *
	 */

	//Properties.
	private $Ajax;
	private $Auth;
	protected $Debug;
	protected $Message;

	protected $javascriptIncludes;
	protected $cssIncludes;
	protected $body;
	protected $title;
	protected $filename;

	public function __construct($Ajax, $Auth, $Debug, $Dbc, $Message) {
		$this->Ajax = $Ajax;
		$this->Auth = $Auth;
		$this->Debug = $Debug;
		$this->Message = $Message;
		$this->Debug->newFile('includes/Embassy/Page.php');

		$this->body = '';
		$this->javascriptIncludes = NULL;
		$this->cssIncludes = NULL;
		$this->title = '';
		$this->filename = '';
	}

	public function addBody($content) {
		$this->body .= $content;
	}

	public function addCss($fileName) {
		/**
		 * Include css files.
		 *
		 * Accepts either a single file or an array of file names in filename.extension format.
		 *
		 * @author      Mark O'Russa    <mark@orussa.com>
		 * @fileName    array|string    the file name(s) with the file extension(s). If the file is relative or lacking a FQDN an autolink will be created.
		 *
		 * @return    string    Valid html javascript include(s).
		 */
		if( is_array($fileName) ){
			foreach( $fileName as $key ){
				if( stripos($key, 'http://') === false && stripos($key, 'https://') === false ){
					$this->cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $key . '?' . date('H') . '" type="text/css" media="all">
';
				}else{
					$this->cssIncludes .= '<link rel="stylesheet" href="' . $key . '?' . date('H') . '" type="text/css" media="all">
';
				}
			}
		}else{
			if( stripos($fileName, 'http://') === false && stripos($fileName, 'https://') === false ){
				$this->cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $fileName . '?' . date('H') . '" type="text/css" media="all">
';
			}else{
				$this->cssIncludes .= '<link rel="stylesheet" href="' . $fileName . '?' . date('H') . '" type="text/css" media="all">
';
			}
		}
	}

	public function addIncludes($fileName, $throwError = '') {
		/**
		 * Include php files.
		 *
		 * Accepts either a single file or an array of file names in filename.extension format.
		 *
		 * @author      Mark O'Russa    <mark@orussa.com>
		 * @fileName    array|string    the file name(s) with the file extension(s). This script assumes the file is in the includes folder.
		 *
		 */
		if( !empty($throwError) ){
			throw new CustomException('You are trying to include more than one file, but you haven\'t put it in an array.');
		}
		if( is_array($fileName) ){
			foreach( $fileName as $key ){
				require_once($key);
			}
		}else{
			require_once($fileName);
		}
	}

	public function addJs($fileName, $extra = '') {
		/**
		 * Include javascript files.
		 *
		 * Accepts either a single file or an array of file names in filename.extension format.
		 *
		 * @author      Mark O'Russa    <mark@orussa.com>
		 * @fileName    array|string    the file name(s) with the file extension(s). If the file is relative or lacking a FQDN an autolink will be created.
		 *
		 * @return    string    Valid html javascript include(s).
		 */
		if( is_array($fileName) ){
			foreach( $fileName as $key ){
				if( stripos($key, 'http://') === false && stripos($key, 'https://') === false ){
					$this->javascriptIncludes .= '<script type="text/javascript" src="' . LINKJS . '/' . $key . '?' . date('H') . '"></script>
';
				}else{
					$this->javascriptIncludes .= '<script type="text/javascript" src="' . $key . '?' . date('H') . '"></script>
';
				}
			}
		}else{
			if( stripos($fileName, 'http://') === false && stripos($fileName, 'https://') === false ){
				$this->javascriptIncludes .= '<script type="text/javascript" src="' . LINKJS . '/' . $fileName . '?' . date('H') . '" ' . $extra . '></script>
';
			}else{
				$this->javascriptIncludes .= '<script type="text/javascript" src="' . $fileName . '?' . date('H') . '" ' . $extra . '></script>
';
			}
		}
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitleAndFilename($title, $filename) {
		/**
		 * @param string $title    The title of the page as it will appear in the html title tag.
		 * @param string $filename The name of the file for debugging purposes.
		 */
		$this->title = $title;
		$this->filename = $filename;
		$this->Debug->newFile($filename);
	}

	public function setTitle($title) {
		/**
		 * @param string $title The title of the page as it will appear in the html title tag.
		 */
		$this->title = $title;
	}

	public function setFilename($filename) {
		/**
		 * @param string $filename The name of the file for debugging purposes.
		 */
		$this->Debug->newFile($filename);
	}

	private function output() {
		$output = '';
		$head = '<!DOCTYPE HTML>
<html lang="en" xml:lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
';
		if( $this->title != '' ){
			$head .= '<title>' . NAME_OF_THE_SITE . ' - ' . $this->title . '</title>
';
		}else{
			$head .= '<title>' . NAME_OF_THE_SITE . '</title>
';
			$this->Debug->add('The $title array for this page was not found.<br>');
		}

		// CSS files.
		$head .= empty($this->cssIncludes) ? '' : $this->cssIncludes;
		$head .= '<link rel="stylesheet" href="' . LINKCSS . '/main.css?' . date('j') . '" media="all" type="text/css">
		<link rel="stylesheet" href="' . LINKCSS . '/font-awesome-4.7.0/css/font-awesome.min.css?' . date('j') . '" media="all" type="text/css">';

		// Javascript files
		//$head .= '<script type="text/javascript" src="' . LINKJS . '/jquery/jquery-3.1.0.js"></script>';
		//<script src="https://use.fonticons.com/f71366fc.js"></script>
		//$head .= '<script type="text/javascript" src="' . LINKJS . '/functions.js?' . date('j') . '"></script>';
		$head .= empty($this->javascriptIncludes) ? '' : $this->javascriptIncludes . '</head>';

		//Build the output. Spinners and floaters are for AJAX operations.
		$output .= $head . '	<body>
		<div id="cover"></div>
		<div id="spinner">
		<a href="' . AUTOLINK . $_SERVER['PHP_SELF'] . '"><img alt="" class="absolute" src="' . LINKIMAGES . '/spinner.png" style=""><p>Refresh</p></a>
		</div>
		<div class="red textCenter" style="margin:0">
			<noscript>(javascript required)</noscript>
		</div>
		<div id="closeButtonRepository" style="display:none"><div class="generalCancel"><i class="fa fa-close"></i> Close</div></div>
		<div id="floater" class="floater"></div>
		';
		if(1 == 0){
			$output .= $this->Message;
		}
		$output	.= $this->Auth->buildLogout() . $this->body . '
	</body >
</html > ';
		return $output;
	}

	public function __toString() {
		$this->Debug->writeToLog();
		return $this->output();
	}

	public function specialSauce() {
		// This is so writeToLog() isn't called on the viewLog page.
		return $this->output();
	}

}