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
    /**
     * Append content to the body content
     *
     * @param string $content
     * @param null|string $name
     * @return Zend_Controller_Response_Abstract
     */
    public function appendBody($content, $name = null)
    {
    	if (!isset($this->_body['json'])){
    		$this->_body['json'] = array();
    	}
    	$this->_body['json'][] = $content;
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

        if ($this->isException()) {
            $exceptions = array();
            foreach ($this->getException() as $e) {
            	if ($e instanceof ZendX_Sencha_Direct_Exception){
					$exception = array(
						'type'		=> 'exception',
						'tid'		=> $e->getTid(),
						'message'	=> $e->getMessage(),
						'where'		=> $e->getTrace()
	            	);
		            if ($request->isBatchRequest()){
		            	array_push($data, $exception);
		            } else {
		            	$data = $exception;
		            	break;
		            }
	            } else {
	            	throw $e; // what else can I do?
	            }
            }
        }

		// Unline regular responses, the result can contain both exceptions
		// and good data.
		if (isset($this->_body['json']) && is_array($this->_body['json'])){
			foreach ($this->_body['json'] as $resp){
				if ($request->isBatchRequest()){
					array_push($data, $resp);
				} else {
					$data = $resp;
					break;
				}
			}
		}

		$json = Zend_Json::encode($data);
		// formatting this mainly for debugging..
		$formatter = new ZendX_Sencha_Direct_Response_Formatter_Indent();
		echo $formatter->format($json);    		
    }
}