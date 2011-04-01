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

/** Zend_Controller_Front */
require_once 'Zend/Controller/Front.php';

/** Zend_Application_Resource_Frontcontroller */
require_once 'Zend/Application/Resource/Frontcontroller.php';

/**
 * ZendX_Sencha_Application_Resource_Sencha class.
 * 
 * @extends Zend_Application_Resource_Frontcontroller
 */
class ZendX_Sencha_Application_Resource_Sencha extends Zend_Application_Resource_Frontcontroller
{
	/**
	 * _sencha
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_sencha;

	/**
	 * _request
	 * 
	 * @var ZendX_Sencha_Direct_Request
	 * @access protected
	 * @static
	 */
	protected static $_request;

	/**
	 * getRequest function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getRequest()
	{
		if (null === self::$_request){
			require_once 'ZendX/Sencha/Direct/Request.php';
			self::$_request = new ZendX_Sencha_Direct_Request();
		}
		return self::$_request;
	}

	/**
	 * getSencha function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getSencha()
	{
		if (null === $this->_sencha){
			if (Zend_Controller_Action_HelperBroker::hasHelper('Sencha')){
				$this->_sencha = Zend_Controller_Action_HelperBroker::getStaticHelper('Sencha');
			} else {
				$this->_sencha = new ZendX_Sencha_Controller_Action_Helper_Sencha();
				Zend_Controller_Action_HelperBroker::addHelper($this->_sencha);
			}
		}
		$this->_sencha->setOptions($this->getOptions());
		return $this->_sencha;
	}

	/**
	 * init function.
	 * 
	 * @access public
	 * @return void
	 */
	public function init()
	{
		$this->configureFrontController();
		return $this->getSencha();
	}

    /**
     * configureFrontController function.
     * 
     * @access public
     * @return void
     */
    public function configureFrontController()
    {
		$front = Zend_Controller_Front::getInstance();
		$request = $this->getRequest();

		// Use this request object even if it's not a direct request
		if (!($front->getRequest() instanceof ZendX_Sencha_Direct_Request)){
			$front->setRequest($request);
		}
		
		if ($request->isDirectRequest()){
			// Configure the front controller
			if (!($front->getRouter() instanceof ZendX_Sencha_Direct_Router)){
				require_once 'ZendX/Sencha/Direct/Router.php';
    			$front->setRouter(new ZendX_Sencha_Direct_Router());
			}
			if (!($front->getResponse() instanceof ZendX_Sencha_Direct_Response)){
				require_once 'ZendX/Sencha/Direct/Response.php';
				$front->setResponse(new ZendX_Sencha_Direct_Response());
			}
            $front->throwExceptions(false);
            $front->setParam('noErrorHandler', true);
		}
    }
}