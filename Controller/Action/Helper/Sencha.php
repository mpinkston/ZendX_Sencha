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

/** Zend_Controller_Action_Helper_Abstract */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

/** ZendX_Sencha_Direct_Api */
require_once 'ZendX/Sencha/Direct/Api.php';

/** Zend_Json */
require_once 'Zend/Json.php';

/**
 * ZendX_Sencha_Controller_Action_Helper_Sencha class.
 * 
 * @extends Zend_Controller_Action_Helper_Abstract
 */
class ZendX_Sencha_Controller_Action_Helper_Sencha extends Zend_Controller_Action_Helper_Abstract
{
	/**
	 * _api
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_api;

	/**
	 * _view
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_view;

	/**
	 * _scripts
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_scripts = array();

	/**
	 * _senchaLoaded
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access private
	 * @static
	 */
	private static $_senchaLoaded = false;

	/**
	 * _config
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_config = array(
		'scriptPath'	=> 'js',
		'library'		=> 'ext',
		'version'		=> '4.0.1',
		'theme'			=> 'default',
		'debug'			=> false
	);

	/**
	 * setOptions function.
	 * 
	 * @access public
	 * @param mixed $options
	 * @return void
	 */
	public function setOptions($options)
	{
		$this->_config = array_merge($this->_config, $options);
		return $this;
	}

	/**
	 * getApi function.
	 *
	 * @access public
	 * @return Sencha_Direct_Api
	 */
	public function getApi()
	{
		if ($this->_api === null){
			$this->_api = new ZendX_Sencha_Direct_Api();
		}
		return $this->_api;
	}

	/**
	 * setApi function.
	 *
	 * @access public
	 * @param mixed $api
	 * @return void
	 */
	public function setApi($api)
	{
		$this->_api = $api;
		return $this;
	}

    /**
     * setView function.
     *
     * @access public
     * @param mixed $view
     * @return void
     */
    public function setView($view)
    {
    	$this->_view = $view;
    	return $this;
    }

    /**
     * getView function.
     *
     * @access public
     * @return void
     */
    public function getView()
    {
    	if ($this->_view === null){
    		$action = $this->getActionController();
    		$this->_view = $action->view;
    	}
    	return $this->_view;
    }

	/**
	 * postDispatch function.
	 *
	 * @access public
	 * @return void
	 */
	public function postDispatch()
	{
		if (!self::$_senchaLoaded){
			return;
		}
	
		$api = $this->getApi();
		$view = $this->getView();

		$providers = $api->getProviders();
		if (count($providers)){		
			$script = "Ext.namespace('" . implode("', '", array_keys($providers)) . "');\n\n";
			foreach ($providers as $ns => $provider){
				$apiDesc = Zend_Json::encode($provider);
				$script .= "{$ns}.APIDesc = {$apiDesc};\n";
				$script .= "Ext.Direct.addProvider({$ns}.APIDesc);\n\n";
			}
			$view->headScript()->appendScript($script);
			$this->_patchRemotingProvider();
		}
		foreach ($this->_scripts as $script){
			$view->headScript()->appendFile($script);
		}
	}
	
	/**
	 * addScript function.
	 * 
	 * @access public
	 * @param mixed $script
	 * @return void
	 */
	public function addScript($script)
	{
		$scriptPath = trim($this->_config['scriptPath'], DIRECTORY_SEPARATOR);
		$this->_scripts[] = '/' . $scriptPath . '/' . $script;
		return $this;
	}

	/**
	 * _patchRemotingProvider function.
	 * Monkey-patch the namespace into the call data.
	 * This is necessary so we can load the api from the
	 * appropriate namespace when the rpc call comes in.
	 * (unfortunately, it looks like doForm can't be patched the same way)
	 *
	 * @access private
	 * @return void
	 */
	private function _patchRemotingProvider()
	{
		$majorVersion = substr($this->_config['version'], 0, 1);
	
		$libraryPath = APPLICATION_PATH . '/../library/ZendX/Sencha/';
		$fileName = "RemotingProviderPatch.ext{$majorVersion}x.js";
		$filePath = $libraryPath . 'Scripts/' . $fileName;
		if (is_readable($filePath)) {
			$script = file_get_contents($filePath);
			$this->getView()->headScript()->appendScript($script);
		}
	}

	/**
	 * loadSenchaTouch function.
	 *
	 * @access public
	 * @param array $options. (default: array())
	 * @return void
	 */
	public function loadSencha($options = array())
	{
		if (self::$_senchaLoaded == true){
			return $this;
		}

		$options = array_merge($this->_config, $options);
		
		$view = $this->getView();

		$scriptPath		= trim($options['scriptPath'], DIRECTORY_SEPARATOR);
		$library		= $options['library'];
		$version		= $options['version'];
		$majorVersion	= substr($options['version'], 0, 1);
		$libraryPath	= '/' . $scriptPath . '/' . $library . '-' . $version;
		$cssPath		= $libraryPath . '/resources/css';
		$debug			= $options['debug']?true:false;
		$theme			= $options['theme']?$options['theme']:'default';

		switch ($options['library']){
			// The ExtJS library
			case 'ext':
				switch ($majorVersion) {

					// Version 4 of the ExtJS library
					case '4':
						$script = $libraryPath . '/ext-all' . ($debug?'-debug.js':'.js');
						$stylesheet = $cssPath . '/ext-all.css';

						$view->headScript()->prependFile($script);
						$view->headLink()->appendStylesheet($stylesheet);
						break;

					// Version 3 of the ExtJS library						
					case '3':
						$base = $libraryPath . '/adapter/ext/ext-base' . ($debug?'-debug.js':'.js');
						$script = $libraryPath . '/ext-all' . ($debug?'-debug.js':'.js');
						$stylesheet = $cssPath . '/ext-all.css';
						
						$view->headScript()->prependFile($script);
						$view->headScript()->prependFile($base);
						$view->headLink()->prependStylesheet($stylesheet);
						
						if ($theme !== 'default'){
							$stylesheet = $cssPath . '/xtheme-' . $theme . '.css';
							$view->headLink()->appendStylesheet($theme);
						}
						break;

					// I haven't used these libs, but they don't support ExtDirect
					case '2':
					case '1':
					default:
						throw new Zend_Controller_Action_Exception('ZendX_Sencha can only be used with ExtJS 3+');
						break;
				}
				break;
				
			// Ext Core library
			case 'ext-core':
				// no css for ext-core
				$script = $libraryPath . '/ext-core' . ($debug?'-debug.js':'.js');
				$view->headScript()->prependFile($script);
				break;

			// Sencha Touch
			case 'sencha-touch':
				$script = $libraryPath . '/sencha-touch' . ($debug?'-debug.js':'.js');		
//				$stylesheet = $cssPath . '/sencha-touch.css';
				$view->headScript()->prependFile($script);
				$view->headLink()->appendStylesheet($stylesheet);
				break;
				
			default:
				require_once 'Zend/Controller/Action/Exception.php';
				throw new Zend_Controller_Action_Exception('Unknown library specified');
				break;
		}
		self::$_senchaLoaded = true;
				
		return $this;
	}

	/**
	 * loadApp function.
	 * 
	 * @access public
	 * @param mixed $app
	 * @return void
	 */
	public function loadApp($app)
	{
		if (!self::$_senchaLoaded){
			$this->loadSencha();
		}
	
		$view		= $this->getView();
		
		$publicPath = $_SERVER['DOCUMENT_ROOT'];
		$scriptPath	= '/' . trim($this->_config['scriptPath'], DIRECTORY_SEPARATOR);
		$appPath	= trim($app, DIRECTORY_SEPARATOR);
		$fullPath	= $publicPath . $scriptPath . '/' . $app;

		if (!is_dir($fullPath)){
			require_once 'Zend/Controller/Action/Exception.php';
			throw new Zend_Controller_Action_Exception(
				'Specified script path is not readable or does not exist'
			);
		}

		$stack = (array) $appPath;
		$scripts = array();
		$types = array('model', 'store', 'view', 'controller');
		$currType = 'default';
		
		while (count($stack)){
			$currDir = array_pop($stack);
			if ($currFiles = scandir($publicPath . $scriptPath . '/' . $currDir)) {
				foreach ($currFiles as $file){
					$relPath = $currDir . '/' . $file;
					$fullPath = $publicPath . $scriptPath . '/' . $relPath;
					if (is_dir($fullPath) && $file != '.' && $file != '..') {
						array_push($stack, $relPath);
					} else if (is_file($fullPath) && substr($file, -3) == '.js') {
						$parts = explode('/', $relPath);
						$currType = 'default';
						foreach ($parts as $part){
							if (in_array($part, $types)){
								$currType = $part;
								break;
							}
						}						
						$scripts[$currType][] = $relPath;
					}
				}
			}
		}

		if ($this->_config['library'] == "sencha-touch"){
			foreach ($scripts['default'] as $script){
				$this->addScript($script);
			}
		}
		foreach ($types as $type){
			if (array_key_exists($type, $scripts) && is_array($scripts[$type])){
				foreach ($scripts[$type] as $script){
					$this->addScript($script);
				}
			}
		}
		if ($this->_config['library'] != "sencha-touch"){
			foreach ($scripts['default'] as $script){
				$this->addScript($script);
			}
		}
		return $this;
	}

	/**
	 * addController function.
	 * 
	 * @access public
	 * @param mixed $controller
	 * @param mixed $module. (default: null)
	 * @return void
	 */
	public function addController($controller, $module=null)
	{
		if ('ext' != $this->_config['library']){
			// Only ext has Ext.Direct right now..
			return true;
		}
	
		$api = $this->getApi();
		$module = $module?$module:$this->getRequest()->getModuleName();

		if ($controller instanceof Zend_Controller_Action){
			$api->add($controller, $module);
			return $this;
		} else if (is_string($controller)) {
			$front = $this->getFrontController();
			$dispatcher = $front->getDispatcher();
	
			$request = clone $this->getRequest();
			$request->setControllerName($controller);
			$request->setModuleName($module);
			$className = $dispatcher->getControllerClass($request);
			$finalClass = $dispatcher->loadClass($className);
	
			$class = new $finalClass($this->getRequest(), $this->getResponse());
			$api->add($class, $module);
			return $this;
		} else {
			require_once 'ZendX/Sencha/Direct/Exception.php';
			throw new ZendX_Sencha_Direct_Exception('Invalid controller passed to ' . __METHOD__);
		}
	}
	
	/**
	 * reset function.
	 * 
	 * @access public
	 * @param mixed $namespace. (default: null)
	 * @return void
	 */
	public function reset($namespace=null)
	{
		$this->getApi()->reset($namespace);
		return $this;
	}
		
	/**
	 * direct function.
	 *
	 * @access public
	 * @param mixed $config
	 * @return void
	 */
	public function direct($config=array())
	{
		// prevent the library from loading
		self::$_senchaLoaded = true;
	
		if (!array_key_exists('resetApi', $config) || $config['resetApi'] == true){
			$this->reset();
		}

		$this->setOptions($config);
		$this->addController($this->getActionController());

		if (array_key_exists('controllers', $config)){
			$controllers = (array) $config['controllers'];
			foreach ($controllers as $controller) {
				call_user_func_array(array($this, 'addController'), (array) $controller);
			}
		}

		if (array_key_exists('scripts', $config)){
			$scripts = (array) $config['scripts'];
			foreach ($scripts as $script) {
				$this->addScript(ltrim($script, DIRECTORY_SEPARATOR));
			}
		}

		if (array_key_exists('app', $config)){
			$app = (string) $config['app'];
			$this->loadApp($app);
		}

		return $this;
	}
}