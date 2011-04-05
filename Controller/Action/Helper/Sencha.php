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

defined('PUBLIC_PATH')
    || define('PUBLIC_PATH', realpath(APPLICATION_PATH . '/../public/'));

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * ZendX_Sencha_Controller_Action_Helper_Sencha class.
 * 
 * @extends Zend_Controller_Action_Helper_Abstract
 */
class ZendX_Sencha_Controller_Action_Helper_Sencha extends Zend_Controller_Action_Helper_Abstract
{
	/**
	 * _senchaLoaded
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 * @static
	 */
	static protected $_senchaLoaded = false;

	/**
	 * _api
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_api;

	/**
	 * _scriptPath
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_scriptPath = '/js';

	/**
	 * _scripts
	 * An array of scripts to be loaded after the Ext libs
	 *
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_scripts = array();

	/**
	 * _view
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_view;

	/**
	 * _defaultConfig
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_defaultConfig = array(
		'library'		=> 'ext',
		'version'		=> '3.3.1',
		'theme'			=> 'default',
		'debug'			=> false
	);
	
	/**
	 * _config
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_config = array();


	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $options. (default: null)
	 * @return void
	 */
	public function __construct($options = null)
	{
		$this->_config = $this->_defaultConfig;
        if ($options instanceof Zend_Config) {
            $this->setConfig($options);
        } elseif (is_array($options)) {
            $this->setOptions($options);
        }
	}

	/**
	 * setScriptPath function.
	 * 
	 * @access public
	 * @param mixed $path
	 * @return void
	 */
	public function setScriptPath($path)
	{
		$path = trim($path, DS);
		if (is_readable(PUBLIC_PATH . DS . $path)){
			$this->_scriptPath = DS . $path;
		} else {
			require_once 'Zend/Controller/Action/Exception.php';
			throw new Zend_Controller_Action_Exception(
				'Specified script path is not readable or does not exist'
			);
		}
		return $this;
	}
	
	/**
	 * getScriptPath function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getScriptPath()
	{
		return $this->_scriptPath;
	}

    /**
     * setOptions function.
     * 
     * @access public
     * @param mixed array $options
     * @return void
     */
    public function setOptions(array $options)
    {
		if (array_key_exists('loadDir', $options)) {
			$dirs = (array)$options['loadDir'];
			foreach ($dirs as $dir){
				$this->appendFileDir($dir);
			}
		}
		
		if (array_key_exists('loadFile', $options)) {
			$dirs = (array)$options['loadFile'];
			foreach ($files as $file){
				$this->appendFile($dir);
			}
		}
		
		if (array_key_exists('scriptPath', $options)) {
			$this->setScriptPath($options['scriptPath']);
		}

		foreach ($this->_config as $key => &$value){
			if (array_key_exists($key, $options)){
				$value = $options[$key];
			}
		}
		return $this;
    }

    /**
     * setConfig function.
     * 
     * @access public
     * @param mixed Zend_Config $config
     * @return void
     */
    public function setConfig(Zend_Config $config)
    {
        return $this->setOptions($config->toArray());
    }

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	public function init()
	{
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
		$this->_api->setNamespace($this->getNamespace());
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
		if (!self::$_senchaLoaded) {
			$this->loadSencha($this->_config);
		}

		$api = $this->getApi();
		$view = $this->getView();

		if ($provider = $api->getProvider()){
			$apiDesc = Zend_Json::encode($provider);
			$script = "Ext.ns('{$provider['namespace']}');\n" .
					  "{$provider['namespace']}.APIDesc = {$apiDesc};\n" .
					  "Ext.Direct.addProvider({$provider['namespace']}.APIDesc);\n";
			$view->headScript()->appendScript($script);
			$this->_patchRemotingProvider();
		}

		if (is_array($this->_scripts)){
			foreach ($this->_scripts as $script){
				$view->headScript()->appendFile($this->_scriptPath . DS . $script);
			}
		}
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
		$options = $this->_config;
		
		if (!$options['library'] == 'ext'){
			return;
		}
		
		$majorVersion = substr($options['version'], 0, 1);
		$libraryPath = APPLICATION_PATH . '/../library/ZendX/Sencha/';
		$fileName = "RemotingProviderPatch.ext{$majorVersion}x.js";
		$filePath = $libraryPath . 'Scripts/' . $fileName;
		if (is_readable($filePath)) {
			$script = file_get_contents($filePath);
			$this->getView()->headScript()->appendScript($script);
		}
	}

	/**
	 * getNamespace function.
	 *
	 * @access public
	 * @return void
	 */
	public function getNamespace()
	{
		$request = $this->getRequest();
		$moduleName = $request->getModuleName();
		if (!$moduleName){
			$moduleName = Zend_Controller_Front::getInstance()->getDefaultModule();
		}
		return ucfirst($moduleName);
	}

	/**
	 * getLibraryPath function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getLibraryPath()
	{
		$options = $this->_config;
	
		$libraryPath = $this->_scriptPath . DS . 
			$options['library'] . '-' . $options['version'];

		if (!is_readable(PUBLIC_PATH . $libraryPath)) {
			require_once 'Zend/Controller/Action/Exception.php';
			throw new Zend_Controller_Action_Exception(
				'Specified library path does not exist or is not readable (' .
				PUBLIC_PATH . $libraryPath . ')'
			);
		}
		return $libraryPath;
	}

	/**
	 * loadExt function.
	 * 
	 * @access public
	 * @param array $options. (default: array())
	 * @return void
	 */
	public function loadExt($options = array())
	{
		return $this->loadSencha($options);
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
		if (self::$_senchaLoaded){
			return $this;
		}
		$options = array_merge($this->_config, $options);
		
		$view = $this->getView();

		$libraryPath = $this->getLibraryPath();
		// css path is same for all libraries.
		$cssPath = $libraryPath . '/resources/css';
		$extension = $options['debug']?'-debug.js':'.js';

		switch ($options['library']){
			case 'ext':
				$view->headScript()->prependFile("{$libraryPath}/ext-all{$extension}");
				$view->headLink()->appendStylesheet("{$cssPath}/ext-all.css");
				if (substr($options['version'], 0, 1) < 4){
					$view->headScript()->prependFile("{$libraryPath}/adapter/ext/ext-base{$extension}");
					if ($options['theme'] !== 'default'){
						$view->headLink()->appendStylesheet("{$cssPath}/xtheme-{$options['theme']}.css");
					}
				}
				break;
				
			case 'ext-core':
				// no css for ext-core
				$view->headScript()->prependFile("{$libraryPath}/ext-core{$extension}");
				break;
				
			case 'sencha-touch':
				$view->headScript()->prependFile("{$libraryPath}/sencha-touch{$extension}");
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
	 * loadExample function.
	 * Convenient access to the examples dir
	 * 
	 * @access public
	 * @param mixed $file
	 * @return void
	 */
	public function loadExample($file)
	{
		$file = trim($file, DS);
		$options = $this->_config;
		$libraryPath = $this->getLibraryPath();
		$filePath = $libraryPath . DS . 'examples' . DS . $file;
		if (is_readable(PUBLIC_PATH . $filePath)){
			if (substr($file, -2) == 'js') {
				$this->getView()->headScript()->appendFile($filePath);
			} else if (substr($file, -3) == 'css') {
				$this->getView()->headLink()->appendStylesheet($filePath);
			}
		}
		return $this;
	}

	/**
	 * add function.
	 *
	 * @access public
	 * @param mixed $config
	 * @return Sencha_Controller_Action_Helper_Sencha
	 */
	public function add($config)
	{
		$api = $this->getApi();
		$api->add($config);
		return $this;
	}

	/**
	 * load function.
	 * Attempts to find/load a js app in the current namespace
	 *
	 * @access public
	 * @param mixed $appName
	 * @return void
	 */
	public function load($appName)
	{
		$namespace = strtolower($this->getNamespace());
		$app = preg_match('/\.js$/', $appName)?$appName:"{$appName}.js";
		$view = $this->getView();

		$file = $this->_scriptPath . DS . $namespace . DS . $app;
		if (is_readable(PUBLIC_PATH . $file)){
			$this->addScript($namespace . DS . $app);
			return $this;
		}

		// Try to hunt the file down.. not ideal.
		$jsPath = PUBLIC_PATH . DS . $this->_scriptPath;
		$namespaceDir = '';

		foreach (scandir($jsPath) as $nsDir) {
			if (strtolower($namespace) == strtolower($nsDir)){
				$namespaceDir = $nsDir;
				break;
			}
		}

		if (!$namespaceDir) {
			error_log("Could not find namespace directory ({$namespace}) while attempting to load {$appName}");
			return $this;
		}

		$jsFile = '';
		foreach (scandir($jsPath . DS . $namespaceDir) as $f){
			if (strtolower($app) == strtolower($f)){
				$jsFile = $f;
				break;
			}
		}

		$fullPath = $jsPath . DS . $namespaceDir . DS . $jsFile;
		if (!$jsFile || !is_readable($fullPath)){
			error_log("Failed to load or read {$fullPath}");
			return $this;
		}

		$this->addScript($namespaceDir . DS . $jsFile);
		return $this;
	}

	/**
	 * reset function.
	 * Clears out the current api namespaces
	 * if allNamespaces is set to true, clears all
	 * api namespaces.
	 *
	 * @access public
	 * @param bool $allNamespaces. (default: false)
	 * @return void
	 */
	public function reset($allNamespaces=false)
	{
		$api = $this->getApi();
		$api->reset($allNamespaces);
		return $this;
	}

	/**
	 * _readDir recursively searches directories for javascript files and adds
	 * them to an array to return to the caller.
	 *
	 * Javascript files can be given an index on the first line of code with a
	 * comment:
	 * // 0
	 * Would force that specific file to be included before all others in that
	 * directory
	 *
	 * @param string $dir is a directory with the root being PUBLIC_PATH
	 * @returns array of strings of the javascript files for the specified directory and it's subdirectories or and empty array
	 */
	private function _readDir($dir) {
		$files = array();
		$holdback = array();
		$subdirfiles = array();
		$path = PUBLIC_PATH . DS . $this->_scriptPath . DS . $dir;

		if (!is_readable($path)){
			return null;
		}

		$dirHnd = @opendir($path);

		if ($dirHnd) {
			while (($file = readdir($dirHnd)) !== false) {
				if ($file !== '.' && $file !== '..' && substr($file, -1) !== '~' &&
				substr($file, -7) !== '.ignore') {
					// directory + file
					$fdir = $dir . DS . $file;
					// full path + file
					$fpath = $path . DS . $file;

					// if the file is a directory we need to recurse
					if (is_dir($fpath)) {
						$subdirfiles[] = $this->_readDir($fdir);
					}
					// Right now, we only care about files that are *.js
					else if (preg_match("/\.js$/", $file)) {
						// Check the file's priority
						$fileContents = file_get_contents($fpath);
						if (preg_match(",^// [0-9]+,", $fileContents, $match)) {
							// this will be our index
							$idx =  str_replace('// ', '', $match[0]);
							// Put the file in a special array that will be used
							// for inserting the files into their correct
							// indexed location.
							$holdback[$idx] = $fdir;
						}
						else {
							// if the file doesn't have a priority, add it to
							// the end of the array
							array_push($files, $fdir);
						}
					}
				}
			}

			// Close the directory when finished
			closedir($dirHnd);

			// Check if we have specially indexed files. If so, we need to
			// insert them into the files array at the correct location
			if(!empty($holdback)) {
				// Just to make sure the array is correctly sorted by its keys
				// since it may not be
				ksort($holdback);
				foreach($holdback as $idx => $file) {
					// this effectively inserts the file at the correct idx
					array_splice($files, $idx, 0, array($file));
				}
			}
		}
		else {
			error_log('unable to open: ' . $dir);
		}

		// if we have subdirectories, we'll need to go through each one, which
		// is an array
		if (!empty($subdirfiles)) {
			foreach($subdirfiles as $subdir) {
				// sort by keys
				ksort($subdir);
				foreach($subdir as $file){
					array_push($files, $file);
				}
			}
		}

		return $files;
	}

	/**
	 * appendFileDir function.
	 * 
	 * @access public
	 * @param mixed $dir
	 * @return void
	 */
	public function appendFileDir($dir) {
		$files = $this->_readDir($dir);
		if (is_array($files)){
			foreach($files as $file) {
				$this->addScript($file);
			}
		}
		return $this;
	}
	
	/**
	 * appendFile function.
	 * 
	 * @access public
	 * @param mixed $file
	 * @return void
	 */
	public function appendFile($file) {
		$path = PUBLIC_PATH . DS . 
			$this->_config['scriptPath'] . DS . $file;
		if (is_readable($path)){
			$this->addScript($file);
		}
		return $this;
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
		if (!in_array($script, $this->_scripts)) {
			array_push($this->_scripts, $script);
		}
		return $this;
	}

	/**
	 * direct function.
	 *
	 * @access public
	 * @param mixed $config
	 * @return void
	 */
	public function direct($config=null)
	{
		$this->add($this->getActionController());

		if ($config !== null){
			$this->load($config);
		}
		return $this;
	}
}