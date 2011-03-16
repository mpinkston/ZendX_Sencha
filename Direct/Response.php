<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.ics-llc.net/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@ics-llc.net so we can send you a copy immediately.
 *
 * @category   ZendX
 * @package    ZendX_Sencha
 * @copyright  Copyright (c) 2011 ICS Advanced Technologies, LLC.
 * @author     Matt Pinkston (mpinkston@ics-llc.net)
 * @license    http://www.ics-llc.net/license/new-bsd  New BSD License
 */

/**
 * ZendX_Sencha_Direct_Response class.
 * 
 * @extends Zend_Controller_Response_Http
 */
class ZendX_Sencha_Direct_Response extends Zend_Controller_Response_Http
{
	const DIRECT = 'direct';

    /**
     * Append content to the body content
     *
     * @param string $content
     * @param null|string $name
     * @return Zend_Controller_Response_Abstract
     */
    public function appendBody($content, $name = null)
    {
    	if (!isset($this->_body[self::DIRECT])){
    		$this->_body[self::DIRECT] = array();
    	}
    	$this->_body[self::DIRECT][] = $content;
        return $this;
    }
    
    /**
     * setException function.
     * 
     * @access public
     * @param mixed Exception $e
     * @return void
     */
    public function setException(Exception $e)
    {
    	if (!($e instanceof ZendX_Sencha_Direct_Exception)){
    		$request = Zend_Controller_Front::getInstance()->getRequest();
    		if ($request instanceof ZendX_Sencha_Direct_Request){
	    		$e = new ZendX_Sencha_Direct_Exception($request->getTid(), $e);
	    	}
    	}
    	parent::setException($e);
    }

	/**
	 * formatJson function.
	 * This code was swiped from: http://recursive-design.com/blog/2008/03/11/format-json-with-php/
	 * thanks!
	 *
	 * @author Recursive Design (http://recursive-design.com/)
	 * @access public
	 * @param mixed $json
	 * @return void
	 */
	public static function formatJson($json)
	{
	    $result      = '';
	    $pos         = 0;
	    $strLen      = strlen($json);
	    $indentStr   = '  ';
	    $newLine     = "\n";
	    $prevChar    = '';
	    $outOfQuotes = true;
	
	    for ($i=0; $i<=$strLen; $i++) {
	
	        // Grab the next character in the string.
	        $char = substr($json, $i, 1);
	
	        // Are we inside a quoted string?
	        if ($char == '"' && $prevChar != '\\') {
	            $outOfQuotes = !$outOfQuotes;
	        
	        // If this character is the end of an element, 
	        // output a new line and indent the next line.
	        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
	            $result .= $newLine;
	            $pos --;
	            for ($j=0; $j<$pos; $j++) {
	                $result .= $indentStr;
	            }
	        }
	        
	        // Add the character to the result string.
	        $result .= $char;
	
	        // If the last character was the beginning of an element, 
	        // output a new line and indent the next line.
	        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
	            $result .= $newLine;
	            if ($char == '{' || $char == '[') {
	                $pos ++;
	            }
	            
	            for ($j = 0; $j < $pos; $j++) {
	                $result .= $indentStr;
	            }
	        }
	        
	        $prevChar = $char;
	    }
	
	    return $result;
	}

    /**
     * sendResponse function.
     * 
     * @access public
     * @return void
     */
    public function sendResponse()
    {
    	$request = Zend_Controller_Front::getInstance()->getRequest();
        $this->sendHeaders();
        
		$data = array();
		// Add any exceptions to the response
		if ($this->isException()) { // more like 'has' exception..
			$formatter = ZendX_Sencha_Formatter::getFormatter('exception');
			foreach ($this->getException() as $e){
            	if ($e instanceof ZendX_Sencha_Direct_Exception){
					if ($request->isBatchRequest()) {
						array_push($data, $formatter->format($e));
					} else {
						$data = $formatter->format($e);
					}
				} else {
					throw $e;
				}
			}
		}

		// Add successful results to the response
		if (isset($this->_body[self::DIRECT]) && is_array($this->_body[self::DIRECT])) {
			foreach ($this->_body[self::DIRECT] as $resp) {
				if ($request->isBatchRequest()){
					array_push($data, $resp);
				} else {
					$data = $resp;
				}
			}
		}
		
		// Output the json string to the browser.
		$json = Zend_Json::encode($data);

        // TODO make this a config parameter
		if (true){
			echo self::formatJson($json);
		} else {
			echo $json;
		}
    }
}