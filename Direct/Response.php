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

/** Zend_Controller_Response_Http */
require_once 'Zend/Controller/Response/Http.php';

/** Zend_Controller_Action_HelperBroker */
require_once 'Zend/Controller/Action/HelperBroker.php';

/**
 * ZendX_Sencha_Direct_Response class.
 * 
 * @extends Zend_Controller_Response_Http
 */
class ZendX_Sencha_Direct_Response extends Zend_Controller_Response_Http
{
	/**
	 * _transactions
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_transactions = array();

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		$this->setHeader('Content-Type', 'application/json');
	
		// Configure the Json helper to accommodate batch requests.
		$jsonHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Json');
		$jsonHelper->suppressExit = true;
	}

	/**
	 * _getTid function.
	 * Returns the id for the current transaction.
	 * 
	 * @access protected
	 * @return int
	 */
	protected function _getTid()
	{
		$request = Zend_Controller_Front::getInstance()->getRequest();
		return (int) $request->getTid();
	}

	/**
	 * _getControllerName function.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function _getControllerName()
	{
		$request = Zend_Controller_Front::getInstance()->getRequest();
		return $request->getControllerName();
	}
	
	/**
	 * _getActionName function.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function _getActionName()
	{
		$request = Zend_Controller_Front::getInstance()->getRequest();
		return $request->getActionName();
	}

    /**
     * setBody function.
     * 
     * @access public
     * @param mixed $content
     * @param mixed $name. (default: null)
     * @param mixed $controllerName. (default: null)
     * @param mixed $actionName. (default: null)
     * @return void
     */
    public function setBody($content, $name = null, $controllerName = null, $actionName = null)
    {
		$tid = is_int($name)?$name:$this->_getTid();

		if ($controllerName === null){
			$controllerName = $this->_getControllerName();
		}

		if ($actionName === null){
			$actionName = $this->_getActionName();
		}

		try {
			$data = Zend_Json::decode($content);
		} catch (Zend_Json_Exception $e){
			parent::setBody($content, $name);
			$data = $content;
		}

		$this->_transactions[$tid] = array(
			'type'		=> 'rpc',
			'tid'		=> $tid,
			'action'	=> $controllerName,
			'method'	=> $actionName,
			'result'	=> $data
		);
		
		return $this;
    }

    /**
     * appendBody function.
     * 
     * @access public
     * @param mixed $content
     * @param mixed $name. (default: null)
     * @return void
     */
    public function appendBody($content, $name = null)
    {
		$tid = is_int($name)?$name:$this->_getTid();

		if (!isset($this->_transactions[$tid])){
			return $this->setBody($content, $tid);
		}

		try {
			$data = Zend_Json::decode($content);
		} catch (Zend_Json_Exception $e){
			parent::appendBody($content, $name);
			$data = $content;
		}

		if ($data){
			if (is_array($this->_transactions[$tid]['result'])){
				$this->_transactions[$tid]['result'][] = $data;
			} else if (is_string($this->_transactions[$tid]['result'])) {
				$this->_transactions[$tid]['result'] .= (string) $data;
			}
		}

		return $this;
    }

    /**
     * setException function.
     * 
     * @access public
     * @param mixed Exception $e
     * @return void
     */
    public function setException(Exception $e, $tid = null)
    {
		$tid = (int) (null===$tid)?$this->_getTid():$tid;
		$this->_transactions[$tid] = array(
			'type'		=> 'exception',
			'tid'		=> $tid,
			'message'	=> $e->getMessage(),
			'where'		=> $e->getTrace()
		);
		// just to track whether the response has an exception..
    	parent::setException($e);
    	return $this;
    }

    /**
     * Echo the body segments
     *
     * @return void
     */
    public function outputBody()
    {    
    	$output = array();
    	if (count($this->_transactions) > 1){
	    	foreach ($this->_transactions as $trx) {
	    		$output[] = $trx;
	    	}
	    } else {
	    	$output = array_shift($this->_transactions);
	    }
	    $json = Zend_Json::encode($output);
	    echo $this->formatJson($json);
    }

    /**
     * Send the response, including all headers.
     *
     * @return void
     */
    public function sendResponse()
    {
        $this->sendHeaders();
        $this->outputBody();
    }

	/**
	 * formatJson function.
	 * This code was swiped from: http://recursive-design.com/blog/2008/03/11/format-json-with-php/
	 * thanks!
	 *
	 * This is just to make debugging easier for now. I feel I'll eventually
	 * have to move it someplace more appropriate.
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
}