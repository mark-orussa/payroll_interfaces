<?php

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 9/21/2016
 * Time: 12:54 PM
 */
namespace Embassy;

use Exception;

class Form {
	private $Debug;
	private $formName;
	private $ajax;

	public function __construct($Debug, $formName, $ajax = false) {
		$this->Debug = &$Debug;
		$this->formName = $formName;
		$this->ajax = $ajax;
		$this->Debug->newFile('includes/Embassy/Form.php');
	}

	public function input($type, $name, Array $options = null) {
		/**
		 * @param $type       string    The type of input (i.e. text, email, password, etc)
		 * @param $name       string    The name attribute of the input. According to HTML standards, this must be present or the input will not be passed in the request.
		 * @param $options    array|null  An associative array of additional attributes name => values.
		 *                    You can use standard attributes like value and class, and some special ones like label.
		 *                    Adding 'label' will automatically add a label tag for the related input.
		 *                    An example looks like array('value' => 'cheese', 'label', 'class' => 'blue bold')
		 * @return string
		 */
		$output = '';
		try{
			if( empty($type) ){
				throw new CustomException('', 'The input $type is empty.');
			}

			$simpleInputArray = array('text', 'number', 'date', 'hidden', 'tel', 'email', 'password', 'submit');
			// Open the input
			if( in_array($type, $simpleInputArray) ){
				$output .= '<input type="' . $type . '"';

			}elseif( $type == 'textarea' ){
				$output .= '<textarea';
			}

			// Name and Id.
			if( $name == 'mode' ){
				// This is a mode input. Do not nest it in a form group.
				$output .= ' name="' . $name . '" id="' . $name . '"';
			}else{
				$output .= ' name="' . $this->formName . '[' . $name . ']" id="' . $this->formName . '_' . $name . '"';
			}

			// Add the optional parameters
			if( is_array($options) && !empty($options) ){
				foreach( $options as $key => $value ){
					if( $key === 'label' ){
						$output = '<label style="margin: 0 .5em" for="' . $this->formName . '_' . $name . '">' . $value . '</label>' . $output;
					}elseif( $value == 'label' ){
						$text = ucwords(str_ireplace('_', ' ', $name));
						$output = '<label style="margin: 0 .5em" for="' . $this->formName . '_' . $name . '">' . $text . '</label>' . $output;
					}elseif( $value == 'required' ){
						$output .= ' required="1"';
					}else{
						if( $type == 'submit' ){
							//die($this->Debug->printArrayOutput($options) . ' ' . $key . '="' . $value . '"');
						}
						$output .= ' ' . $key . '="' . $value . '"';
					}
				}
			}

			// Close the input.
			if( $type == 'textarea' ){
				$output .= '></textarea>';
			}else{
				$output .= '>';
			}

		}catch( CustomException $exception ){
		}catch( Exception $exception ){
			$this->Debug->add($exception);
		}
		return $output;
	}

	public function open() {
		/**
		 * Create the opening form tag. An anti- CSRF hidden field is automatically created.
		 * If ajax is requested a custom attribute will be added here.
		 * @return string
		 */
		$output = '<form action="' . $_SERVER['PHP_SELF'] . '" name="' . $this->formName . '" method="post"';
		$output .= $this->ajax ? ' data-ajax="1">' : '>';

		// Generate a cross site request forgery (CSRF) prevention token.
		$_SESSION['CSRF'] = Secret::generateKey();

		$output .= '<input id="CSRF" name="' . $this->formName . '[CSRF]" type="hidden" value="' . $_SESSION['CSRF'] . '">';
		return $output;
	}

	public function close() {
		/**
		 * Simply adds the closing form tag.
		 * @return string
		 */
		return '</form>';
	}

	public static function verifyCsrf($csrf) {
		/**
		 * Compare the CSRF value against the SESSION value.
		 * @param $csrf string The CSRF value from REQUEST, GET, or POST.
		 * @return bool
		 */
		$output = false;
		if( $csrf == $_SESSION['CSRF'] ){
			$output = true;
		}
		return $output;
	}
}