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
 * ZendX_Sencha_Direct_Dispatcher class.
 * 
 * @extends Zend_Controller_Dispatcher_Abstract
 */
class ZendX_Sencha_Direct_Dispatcher extends Zend_Controller_Dispatcher_Standard
{
	/**
	 * _api
	 * 
	 * @var ZendX_Sencha_Direct_Api
	 * @access private
	 */
	private $_api;

	/**
	 * getApi function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getApi()
	{
		if (null === $this->_api){
			$this->_api = new ZendX_Sencha_Direct_Api();
		}
		return $this->_api;
	}

    /**
     * dispatch function.
     * 
     * @access public
     * @param mixed Zend_Controller_Request_Abstract $request
     * @param mixed Zend_Controller_Response_Abstract $response
     * @return void
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
		$moduleName = ucfirst($request->getModuleName());
		$controllerName = $this->_formatName($request->getControllerName(), true);
		$actionName = $this->_formatName($request->getActionName(), true);
		// lcfirst not available in php<5.3
		$actionName{0} = strtolower($actionName{0});
		
		$api = $this->getApi();
		$api->setNamespace($moduleName);
		$classConfig = $api->getClass($controllerName);
		
		if (!is_array($classConfig)){
			throw new ZendX_Sencha_Direct_Exception($request->getTid(), 'Invalid namespace requested');
		}			
		
		$className  = $classConfig['className'];
		$fullPath   = $classConfig['fullPath'];
        if (Zend_Loader::isReadable($classConfig['fullPath'])) {
            include_once $classConfig['fullPath'];
        } else {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new ZendX_Sencha_Direct_Exception($request->getTid(), 'Cannot load controller class "' . $classConfig['className'] . '" from file "' . $classConfig['fullPath'] . "'");
        }
        if (!class_exists($className)){
        	throw new ZendX_Sencha_Direct_Exception($request->getTid(), 'Class does not appear to exist');
        }
		if (!array_key_exists($actionName, $classConfig['methods'])){
			throw new ZendX_Sencha_Direct_Exception($request->getTid(), "Method not found in class config");
		}
		
		$methodConfig = $classConfig['methods'][$actionName];
		$methodName = $methodConfig['methodName'];
					
		// Reflect the class to make sure the method actually is remotable.
		$instance = new $className($request, $response);

		$rc = new Zend_Reflection_Class($instance);
		$rMethod = $rc->getMethod($methodName);
		$mDoc = $rMethod->getDocblock();
		if (!$mDoc->hasTag(ZendX_Sencha_Direct_Api::REMOTE_ATTR)){
			throw new ZendX_Sencha_Direct_Exception($request->getTid(), 'Method is not remotable');
		}

		$data = call_user_func_array(array($instance, $methodName), $request->getParams());

		$resp = array(
			'type'		=> 'rpc',
			'tid'		=> $request->getTid(),
			'action'	=> $controllerName,
			'method'	=> $actionName,
			'result'	=> $data
		);

		$response->appendBody($resp);
    }
}