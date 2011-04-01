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

/** Zend_Controller_Router_Abstract */
require_once 'Zend/Controller/Router/Abstract.php';

/**
 * ZendX_Sencha_Direct_Router class.
 * 
 * @extends Zend_Controller_Router_Abstract
 */
class ZendX_Sencha_Direct_Router extends Zend_Controller_Router_Abstract
{
	/**
	 * _request
	 * 
	 * @var mixed
	 * @access private
	 */
	private $_request;
	
	/**
	 * _initialRequestRouted
	 * If this is a batch request, track wether or not the
	 * first request has been routed. All subsequent batch
	 * requests will be added to the ActionStack.
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access private
	 */
	private $_initialRequestRouted = false;

	/**
	 * _actionStack
	 * Used for adding batch requests
	 * 
	 * @var mixed
	 * @access private
	 */
	private $_actionStack;
	
	/**
	 * _getActionStack function.
	 * 
	 * @access private
	 * @return void
	 */
	private function _getActionStack()
	{
        if (null === $this->_actionStack){
	        $front = $this->getFrontController();
	        if (!$front->hasPlugin('ZendX_Sencha_Controller_Plugin_ActionStack')){
	            require_once 'ZendX/Sencha/Controller/Plugin/ActionStack.php';
				$front->registerPlugin(new ZendX_Sencha_Controller_Plugin_ActionStack(), 97);
	        }
	        $this->_actionStack = $front->getPlugin('ZendX_Sencha_Controller_Plugin_ActionStack');
        }
        return $this->_actionStack;
	}

	/**
	 * addConfig function.
	 * 
	 * @access public
	 * @param mixed $config
	 * @return void
	 */
	public function addConfig($config)
	{
	}

    /**
     * assemble function.
     * 
     * @access public
     * @param mixed $userParams
     * @param mixed $name. (default: null)
     * @param bool $reset. (default: false)
     * @param bool $encode. (default: true)
     * @return void
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true)
	{
	}
	
    /**
     * route function.
     * 
     * @access public
     * @param mixed Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function route(Zend_Controller_Request_Abstract $request)
	{
		if (!$request->isDirectRequest()) {
			throw new Zend_Controller_Router_Exception('Invalid request');
		}
	
		$this->_request = $request;
		if ($request->isDirectSubmit()) {
			$this->_parseFormData($request->getPost());
		} else {
			$data = Zend_Json::decode($request->getRawBody(), Zend_Json::TYPE_ARRAY);
			$this->_parseArray($data);
		}
	}
	
	/**
	 * _parseArray function.
	 * 
	 * @access public
	 * @param mixed $json
	 * @return void
	 */
	public function _parseArray($data)
	{
		if (is_int(key($data))) {
			foreach ($data as $d) {
				$this->_parseArray($d);
			}
			return;
		}

		$tid = (int) $data['tid'];
		$type = $data['type'];
		$module = preg_replace('/' . ZendX_Sencha_Direct_Api::getNsSuffix() . '$/', '', $data['namespace']);
		$controller = $data['action'];
		$action = $data['method'];
		$params = is_array($data['data'])?$data['data']:array();
		if (count($params) && is_int(key($params))){
			$params = array_shift($params);
		}

		// Add the contextSwitch parameter
		if (true){ // from a config option?
			require_once 'Zend/Controller/Action/HelperBroker.php';
			$context = Zend_Controller_Action_HelperBroker::getStaticHelper('ContextSwitch');
			$contextParam = $context->getContextParam();
			if (!array_key_exists($contextParam, $params)){
				$params[$contextParam] = 'json';
			}
		}		

		if ($this->_request->isBatchRequest() && $this->_initialRequestRouted) {
	        $request = clone $this->_request;
	        $request->setTid($tid);
	        $request->setType($type);
	        $request->setModuleName($this->_formatName($module));
	        $request->setControllerName($this->_formatName($controller));
	        $request->setActionName($this->_formatName($action));
	        $request->setParams($params);

	        $stack = $this->_getActionStack();
	        $stack->pushStack($request);
		} else {
			$this->_request->setTid($tid);
			$this->_request->setType($type);
			$this->_request->setModuleName($this->_formatName($module));
			$this->_request->setControllerName($this->_formatName($controller));
			$this->_request->setActionName($this->_formatName($action));
			$this->_request->setParams($params);
			$this->_initialRequestRouted = true;
		}
	}
	
	/**
	 * _parseFormData function.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function _parseFormData($data)
	{
		$module = preg_replace('/' . ZendX_Sencha_Direct_Api::getNsSuffix() . '$/', '', $data['extNamespace']);
		$this->_request->setTid($data['extTID']);
		$this->_request->setType($data['extType']);
		$this->_request->setModuleName($this->_formatName($module));
		$this->_request->setControllerName($this->_formatName($data['extAction']));
		$this->_request->setActionName($this->_formatName($data['extMethod']));
		$this->_request->setParams($data);
	}
	
	/**
	 * _formatName function.
	 * This is to keep module/controller/action names consistent within the request object. 
	 *
	 * @access protected
	 * @param mixed $name
	 * @return void
	 */
	protected function _formatName($name)
	{
		if (null === $name){ 
			return null;
		}
		$name = preg_replace('/[^A-Za-z]/', '', $name);
		$name = preg_replace('/([A-Z])/', '-$1', $name);
		$name = ltrim($name, '-');
		return strtolower($name);
	}
}