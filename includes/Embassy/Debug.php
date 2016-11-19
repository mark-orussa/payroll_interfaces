<?php
namespace Embassy;

class Debug {
	//Properties
	private $Message;
	private $_debugInformation;

	public function __construct() {
		global $Message;
		$this->Message = &$Message;
		$this->_debugInformation = '';
	}

	private function backtrace($debug_backtrace) {//Currently not used.
		/*		$output = '';
				foreach($Debug_backtrace as $key => $value){
					$output .= '<div style="margin-left:2em;">
			<div style="color:"green">file: ';
					$output .= empty($value['file']) ? '' : $value['file'];
					$output .= '</div>
			<div style="color:blue">line: ';
					$output .= empty($value['line']) ? '' : $value['line'];
					$output .= '</div>
			<div style="color:purple">function: ';
					$output .= empty($value['function']) ? '' : $value['function'];
					$output .= '</div>
			<div style="color:purple">agrs: ';
					if(isset($value['args']) && is_array($value['args'])){
						foreach($value['args'] as $value2){
							$tempDebug .= $value2;
						}
					}
					$output .= '</div>
		</div>
		';
				}
				return $output;*/
	}

	public function add($debugMessage, $debug_backtrace = NULL) {
		/*
		Add detailed debug information to the debug class.
		$debugMessage = (string) a user message to help explain the debug.
		$debugInfo = debug_backtrace(false)
		*/
		$tempStuff = '';
		if( is_array($debug_backtrace) && !empty($debug_backtrace) ){
			$tempStuff .= $this->printArray($debug_backtrace);
		}
		if(is_array($debugMessage)){
			$tempStuff .= self::printArrayOutput($debugMessage);
		}else{
			$tempStuff .= empty($debugMessage) ? '' : '<div>' . $debugMessage . '</div>
';
		}
		$this->_debugInformation .= $tempStuff;
	}

	public function error($line = false, $publicMessage = false, $debugMessage = false) {
		/**
		 * Return error information
		 *
		 * Produces a publicly visible error message with a line number at the end.
		 *
		 * @author    Mark O'Russa    <mark@orussa.com>
		 * @param   string $line          The line the error occurred on by using __LINE__.
		 * @param   string $publicMessage A message to be sent to the user on-screen.
		 * @param   string $debugMessage  A message for debugging purposes.
		 * @return  string  Returns a message.
		 */
		if( empty($publicMessage) ){
			if( strstr($this->Message, 'encountered a technical problem') === false ){
				$this->Message .= 'We\'ve encountered a technical problem that is preventing information from being shown. Please try again in a few moments.<br>
If the problem persists please contact the IT Department.<br>';
			}
		}else{
			$this->Message .= $publicMessage . '<br>';
		}
		if( !empty($debugMessage) ){
			self::add($debugMessage);
		}
		return $this->Message;
	}

	public function newFile($fileName = NULL) {
		/*
		$fileName = (string) will default the the page's $title['fileName'] if not provided.
		*/
		global $fileInfo;
		if( empty($fileName) ){
			if( empty($fileInfo) ){
				$fileName = NULL;
			}else{
				$fileName = is_array($fileInfo) ? $fileInfo['fileName'] : NULL;
			}
		}
		//Specifically used when introducing a new document. All php files should use this at the top.
		self::add('<div style="font-weight:bold;border:1px dotted #333;">From ' . $fileName . '</div>');
	}

	public function printArray($array, $arrayName = '', $dump = false) {
		if( is_array($array) ){
			$printArrayOutput = '<div class="break bold">';
			$printArrayOutput .= $arrayName == '' ? '' : 'The array named: ' . $arrayName . ':';
			$printArrayOutput .= '</div>
<pre>';
			ob_start();
			if( $dump ){
				var_dump($array);//this will produce an array structure with extra information like variable type and value length
			}else{
				var_export($array);//this will produce a simple array structure
			}
			$printArrayOutput .= ob_get_contents();
			ob_end_clean();
			$printArrayOutput .= '
</pre>
';
		}else{
			$printArrayOutput = $arrayName ? "$arrayName is not an array. $arrayName: " . "$array<br>" : "The supplied variable is not an array: $array<br>
";
		}
		self::add($printArrayOutput);
	}

	public function printArrayOutput($array, $arrayName = '', $dump = false) {
		//Perform the printArray function and return the results. This includes any prior debug information.
		if( is_array($array) ){
			$printArrayOutput = '<div class="break bold">';
			$printArrayOutput .= $arrayName == '' ? '' : 'The array named: ' . $arrayName . ':';
			$printArrayOutput .= '</div>
<pre>';
			ob_start();
			if( $dump ){
				var_dump($array);//this will produce an array structure with extra information like variable type and value length
			}else{
				var_export($array);//this will produce a simple array structure
			}
			$printArrayOutput .= ob_get_contents();
			ob_end_clean();
			$printArrayOutput .= '
</pre>
';
		}else{
			$printArrayOutput = $arrayName ? "$arrayName is not an array. $arrayName: " . "$array<br>" : "The supplied variable is not an array: $array<br>
";
		}
		return $printArrayOutput;
	}

	public function output() {
		$output = '<div id="debug" class="debug">
	<div style="color:red;font-weight:bold;">BEGIN DEBUG</div>
AUTOLINK: ' . AUTOLINK . '<br>
COOKIEDOMAIN: ' . COOKIEDOMAIN . '<br>
COOKIEPATH: ' . COOKIEPATH . '<br>
LOCAL: ' . LOCAL . '<br>
HTTPS: ' . HTTPS . '<br>
FORCEHTTPS: ' . FORCEHTTPS . '<br>';
		if( isset($_SESSION['admin']) && $_SESSION['admin'] === true ){
			$output .= self::printArrayOutput($_SERVER, '$_SERVER');
		}
		if( isset($_COOKIE) ){
			$output .= self::printArrayOutput($_COOKIE, '$_COOKIE');
		}
		if( isset($_SESSION) ){
			$output .= self::printArrayOutput($_SESSION, '$_SESSION');
		}
		$output .= 'session_name: ' . session_name() . '<br>
session_id: ' . session_id() . '<br>
DATETIME: ' . DATETIME . '<br>
MICROTIME: ' . MICROTIME;
		self::add('<div style="color:red;font-weight:bold;border-top:1px dotted #333;">END DEBUG</div>
</div>');
		return $output . $this->_debugInformation;
	}

	public function readLog() {
		$handle = fopen(LOG_PATH, 'r');
		$filesize = filesize(LOG_PATH);
		return fread($handle, $filesize);
	}

	public function writeToLog() {
		/**
		 * Prepend debug data to the debug log file. This will automatically reduce the file size when it reaches 2 MB.
		 */
		try{
			$output = '<div id="debug" class="debug">
	<div class="debugTitle">BEGIN DEBUG <span>' . StaticDateTime::utcToLocal(DATETIME) . '</span></div>';
			if( isset($_COOKIE) ){
				$output .= '<div class="toggleButtonInline">Toggle $_COOKIE</div><div class="toggleMe">' . $this->printArrayOutput($_COOKIE, '$_COOKIE') . '</div>';
			}
			if( isset($_REQUEST) ){
				$output .= '<div class="toggleButtonInline">Toggle $_REQUEST</div><div class="toggleMe">' . $this->printArrayOutput($_REQUEST, '$_REQUEST') . '</div>';
			}
			if( isset($_SERVER) ){
				$output .= '<div class="toggleButtonInline">Toggle $_SERVER</div><div class="toggleMe">' . $this->printArrayOutput($_SERVER, '$_SERVER') . '</div>';
			}
			if( isset($_SESSION) ){
				$output .= '<div class="toggleButtonInline">Toggle $_SESSION</div><div class="toggleMe">' . $this->printArrayOutput($_SESSION, '$_SESSION') . '</div>';
			}
			$output .= '<br>session_name: ' . session_name() . '<br>
session_id: ' . session_id() . '<br>
AUTOLINK: ' . AUTOLINK . '<br>
DATETIME: ' . DATETIME . '<br>
MICROTIME: ' . MICROTIME;
			if( !file_exists(LOG_PATH) ){
				if( !is_writable(LOG_PATH) ){
					throw new CustomException('','The debug log file does not exist and the path is not writeable. Modify the permissions for this location to allow debug: ' . LOG_PATH);
				}else{
					throw new CustomException('','The debug log file does not exist at: ' . LOG_PATH);
				}
			}else{
				// Check the filesize. If it reaches a certain size we will remove old data.
				$handle = fopen(LOG_PATH, "r+");
				clearstatcache();
				$filesize = filesize(LOG_PATH);
				self::add('<div class="debugTitle">END DEBUG</div><hr>
</div>');
				if( $filesize > 2097152 ){// = 2 MB
					$this->add('The filesize is over 2 MB.<br>');
					ftruncate($handle, 524288);// Reduce the size to 512 KB or .5 MB, by chopping off the end. This works as we are prepending, so the newest data is on top.
				}
				// Prepend the data to the debug log file.
				$cache_new = $output . $this->debugInformation; // this gets prepended
				$len = strlen($cache_new);
				$final_len = $filesize + $len;
				$cache_old = fread($handle, $len);
				rewind($handle);
				$i = 1;
				while( ftell($handle) < $final_len ){
					fwrite($handle, $cache_new);
					$cache_new = $cache_old;
					$cache_old = fread($handle, $len);
					fseek($handle, $i * $len);
					$i++;
				}

				/*fopen(LOG_PATH, 'w');
				$debugFile = fopen(LOG_PATH, 'w');
				fwrite($debugFile, $debugData);*/
				fclose($handle);
			}
		}catch( CustomException $exception ){
		}catch(\Exception $exception){
			die('An error was thrown in ' . __CLASS__ . ': <pre>' . $exception . '</pre>');
		}
	}
}
