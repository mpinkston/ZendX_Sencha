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
 * ZendX_Sencha_Application_Resource_Frontcontroller class.
 * 
 * @extends Zend_Application_Resource_Frontcontroller
 */
class ZendX_Sencha_Application_Resource_Frontcontroller extends Zend_Application_Resource_Frontcontroller
{
    /**
     * Retrieve front controller instance
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
    	if (null === $this->_front){
    		$this->_front = Zend_Controller_Front::getInstance();
			$request = new ZendX_Sencha_Direct_Request();
    		if ($request->isXmlHttpRequest()){

    			// Configure the front controller
    			if (!($this->_front->getRequest() instanceof ZendX_Sencha_Direct_Request)){
    				$this->_front->setRequest($request);
    			}
    			if (!($this->_front->getRouter() instanceof ZendX_Sencha_Direct_Router)){
	    			$this->_front->setRouter(new ZendX_Sencha_Direct_Router());
    			}
    			if (!($this->_front->getResponse() instanceof ZendX_Sencha_Direct_Response)){
    				$this->_front->setResponse(new ZendX_Sencha_Direct_Response());
    			}
	            $this->_front->throwExceptions(false);
	            $this->_front->setParam('noErrorHandler', true);
    		}
    	}
    	return $this->_front;
    }
}
