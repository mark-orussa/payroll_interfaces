<?php
namespace Embassy;

class Debug {
	//Properties
	private $Message;
	private $collector;

	public function __construct($Message) {
		$this->Message = $Message;
		$this->collector = '';
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
		if( is_array($debug_backtrace) && !empty($debug_backtrace) ){
			$this->collector .= $this->printArray($debug_backtrace);
		}
		if( is_array($debugMessage) ){
			$this->collector .= self::printArrayOutput($debugMessage);
		}else{
			$this->collector .= empty($debugMessage) ? '' : '<div>' . $debugMessage . '</div>
';
		}
	}

	public function error($line = false, $publicMessage = '', $debugMessage = false) {
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
		$temp = self::printArrayOutput($_SESSION, '$_SESSION');
		$temp .= self::printArrayOutput($_REQUEST, '$_REQUEST');
		$this->Message->add($publicMessage);

		self::add($debugMessage);
		return $this->Message;
	}

	public function newFile($fileName = '') {
		/**
		 * @param mixed $fileName The name of the file for debugging purposes.
		 */
		global $fileInfo;
		if( empty($fileName) ){
			if( empty($fileInfo) ){
				$fileName = '';
			}else{
				$fileName = is_array($fileInfo) ? $fileInfo['fileName'] : '';
			}
		}
		//Specifically used when introducing a new document. All php files should use this at the top.
		self::add('<div class="newPage">From ' . $fileName . '</div>');
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

	private function output() {
		self::newFile('includes/Embassy/viewLog.php');
		return '<div id = "debug" class="debug">
	<div><span class="debugTitle" > BEGIN DEBUG <span>' . StaticDateTime::utcToLocal(DATETIME) . ' </span ></div>'
			. $this->collector . '
	AUTOLINK: ' . AUTOLINK . '<br>
	ENVIRONMENT: ' . ENVIRONMENT . '<br>
	HTTPS: ' . HTTPS . '<br>
	DATETIME: ' . DATETIME . '<br>
	MICROTIME: ' . MICROTIME . '<br>
<div class="toggleButtonInline">session_get_cookie_params</div><div class="toggleMe">' . self::printArrayOutput(session_get_cookie_params(), 'cookie params') . '</div>
<div class="toggleButtonInline">$_COOKIE</div><div class="toggleMe">' . self::printArrayOutput($_COOKIE, '$_COOKIE') . '</div>
<div class="toggleButtonInline">$_FILES</div><div class="toggleMe">' . self::printArrayOutput($_FILES, '$_FILES') . '</div>
<div class="toggleButtonInline">$_REQUEST</div><div class="toggleMe">' . self::printArrayOutput($_REQUEST, '$_REQUEST') . '</div>
<div class="toggleButtonInline">$_SERVER</div><div class="toggleMe">' . self::printArrayOutput($_SERVER, '$_SERVER') . '</div>
<div class="toggleButtonInline">$_SESSION</div><div class="toggleMe">' . self::printArrayOutput($_SESSION, '$_SESSION') . '</div>
<div style="color:red;font-weight:bold;border-top:1px dotted #333;">END DEBUG</div>
</div>';
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
			if( !file_exists(LOG_PATH) ){
				throw new CustomException('', 'The log file does not exist at: ' . LOG_PATH);
			}
			if( !is_writable(LOG_PATH) ){
				throw new CustomException('', 'The log file is not writeable. Modify the permissions for this location: ' . LOG_PATH);
			}
			if( !is_readable(LOG_PATH) ){
				throw new CustomException('', 'The log file is not readable. Modify the permissions for this location: ' . LOG_PATH);
			}
			// Check the filesize. If it reaches a certain size we will remove old data.
			$handle = fopen(LOG_PATH, "r+");
			clearstatcache();
			$filesize = filesize(LOG_PATH);
			$memory = 2097152;
			if( $filesize > $memory ){// = 2 MB
				$this->add('The filesize is over 2 MB.<br>');
				ftruncate($handle, 524288);// Reduce the size to 512 KB or .5 MB, by chopping off the end. This works as we are prepending, so the newest data is on top.
			}
			// Prepend the data to the debug log file.
			$cache_new = self::output(); // this gets prepended
			$len = strlen($cache_new);
			$final_len = $filesize + $len;
			$cache_old = fread($handle, $len);
			rewind($handle);
			$i = 1;
			while( ftell($handle) < $final_len ){
				// If the php configuration has a low memory setting this section may overwhelm it and cause the script to stop. It will use up too much memory.
				fwrite($handle, $cache_new);
				$cache_new = $cache_old;
				$cache_old = fread($handle, $len);
				fseek($handle, $i * $len);
				$i++;
			}

			/*fopen(LOG_PATH, 'w');
			$debugFile = fopen(LOG_PATH, 'w');
			fwrite($handle, self::output());*/
//				die(self::output());
			fclose($handle);
		}catch( CustomException $exception ){
			die($this->output());
		}catch( \Exception $exception ){
			die('An error was thrown in ' . __CLASS__ . ': <pre>' . $exception . '</pre>');
		}
	}
}
