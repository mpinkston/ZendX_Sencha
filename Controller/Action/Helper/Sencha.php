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
 * ZendX_Sencha_Controller_Action_Helper_Sencha class.
 * 
 * @extends Zend_Controller_Action_Helper_Abstract
 */
class ZendX_Sencha_Controller_Action_Helper_Sencha extends Zend_Controller_Action_Helper_Abstract
{
	// The default version of the ExtJs library
	const EXTJS_VERSION	= '3.3.1';

//const EXTJS_VERSION = '4.0-pr5';

	// The default version of the SenchaTouch library
	// TODO configure sencha touch libs
	const SENCHATOUCH_VERSION = '';

	// The public path for javascript files.
	const SCRIPT_PATH	= '/js/';

	// Set to true to enforce strict case conventions in filenames.
	const STRICT_CASE	= false;

	/**
	 * _extLoaded
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access protected
	 * @static
	 */
	static protected $_extLoaded = false;

	/**
	 * _senchaTouchLoaded
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access protected
	 * @static
	 */
	static protected $_senchaTouchLoaded = false;

	/**
	 * _api
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_api;

	/**
	 * _scripts
	 * An array of scripts to be loaded after the Ext libs
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_scripts;

	/**
	 * _view
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_view;

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	public function init()
	{
		defined('PUBLIC_PATH')
		    || define('PUBLIC_PATH', realpath(APPLICATION_PATH . '/../public/'));
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
		if (!self::$_extLoaded && !self::$_senchaTouchLoaded) {
			$this->loadExt();
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
				$view->headScript()->appendFile($script);
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
		$script = <<<EOS
(function() {
	var originalGetCallData = Ext.direct.RemotingProvider.prototype.getCallData;
	Ext.override(Ext.direct.RemotingProvider, {
		getCallData: function(t) {
			var defaults = originalGetCallData.apply(this, arguments);
			return Ext.apply(defaults, {
				namespace: this.namespace.APIDesc.namespace
			});
		},

	    doForm : function(c, m, form, callback, scope){
	        var t = new Ext.Direct.Transaction({
	            provider: this,
	            action: c,
	            method: m.name,
	            args:[form, callback, scope],
	            cb: scope && Ext.isFunction(callback) ? callback.createDelegate(scope) : callback,
	            isForm: true
	        });

	        if(this.fireEvent('beforecall', this, t, m) !== false){
	            Ext.Direct.addTransaction(t);
	            var isUpload = String(form.getAttribute("enctype")).toLowerCase() == 'multipart/form-data',
	                params = {
	                    extTID: t.tid,
	                    extAction: c,
	                    extMethod: m.name,
	                    extNamespace: this.namespace.APIDesc.namespace,
	                    extType: 'rpc',
	                    extUpload: String(isUpload)
	                };

	            // change made from typeof callback check to callback.params
	            // to support addl param passing in DirectSubmit EAC 6/2
	            Ext.apply(t, {
	                form: Ext.getDom(form),
	                isUpload: isUpload,
	                params: callback && Ext.isObject(callback.params) ? Ext.apply(params, callback.params) : params
	            });
	            this.fireEvent('call', this, t, m);
	            this.processForm(t);
	        }
	    }
	})
})();

EOS;
		$this->getView()->headScript()->appendScript($script);
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
		return $moduleName?ucfirst($moduleName):'Default';
	}

	/**
	 * loadSenchaTouch function.
	 *
	 * @access public
	 * @param array $options. (default: array())
	 * @return void
	 */
	public function loadSenchaTouch($options = array())
	{
		if (self::$_extLoaded === true){
			throw new Zend_Controller_Action_Exception('ExtJS has already been loaded.');
		} else if (self::$_senchaTouchLoaded === true){
			return $this;
		}

		// do all the stuff to load sencha touch.

		self::$_senchaTouchLoaded	= true;
		return $this;
	}

	/**
	 * loadExt function.
	 * TODO This whole function needs to be figured out..
	 *
	 * @access public
	 * @param array $options. (default: array())
	 * @return void
	 */
	public function loadExt($options = array())
	{
		if (self::$_senchaTouchLoaded === true) {
			throw new Zend_Controller_Action_Exception('Sencha Touch has already been loaded.');
		} else if (self::$_extLoaded === true){
			return $this;
		}

		$view = $this->getView();

		$jsPath = self::SCRIPT_PATH;
		$version = self::EXTJS_VERSION;

		if (array_key_exists('version', $options)){
			$version = $options['version'];
		}

		$debug = '-debug';
		if (array_key_exists('debug', $options)){
			if ($options['debug'] == true){ $debug = '-debug'; }
		}

		$theme = '';//'access';
		if (array_key_exists('theme', $options)){
			$theme = $options['theme'];
		}

		$includeux = true;
		if (array_key_exists('includeux', $options)){
			$includeux = ($options['includeux']==true)?true:false;
		}
/*		
		$extPath = "{$jsPath}/ext-{$version}/";
		
		$view->headLink()->appendStylesheet("{$extPath}/resources/css/ext-all.css");
		$view->headScript()->appendFile("{$extPath}/bootstrap.js");
*/
		

		// CSS
		$view->headLink()->appendStylesheet("/css/icons.css");
		$view->headLink()->appendStylesheet("{$jsPath}/ext-{$version}/resources/css/ext-all.css");

		if ($theme){
	 		$view->headLink()->appendStylesheet("{$jsPath}/ext-{$version}/resources/css/xtheme-{$theme}.css");
	 	}

		// JS
		$view->headScript()->appendFile("{$jsPath}/ext-{$version}/adapter/ext/ext-base{$debug}.js");
		$view->headScript()->appendFile("{$jsPath}/ext-{$version}/ext-all-debug.js");

		if ($includeux){
			$view->headScript()->appendFile("{$jsPath}/ext-{$version}/examples/ux/ux-all{$debug}.js");
			$view->headLink()->appendStylesheet("{$jsPath}/ext-{$version}/examples/ux/css/ux-all.css");

			// I'm going to consider my ext-ux directory part of this too.
			$this->appendFileDir("{$jsPath}/ext-ux/");
		}

		self::$_extLoaded = true;
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

		$file = self::SCRIPT_PATH . "/{$namespace}/{$app}";
		if (is_readable(PUBLIC_PATH . $file)){
			$this->_scripts[] = $file;
			return $this;
		}

		if (self::STRICT_CASE) {
			// If it wasn't found, just leave without complaining.
			error_log('Failed to load file: ' . $file);
			return $this;
		}

		// Try to hunt the file down.. not ideal.
		$jsPath = PUBLIC_PATH . self::SCRIPT_PATH;
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
		foreach (scandir($jsPath . $namespaceDir) as $f){
			if (strtolower($app) == strtolower($f)){
				$jsFile = $f;
				break;
			}
		}

		$fullPath = "{$jsPath}/{$namespaceDir}/{$jsFile}";
		if (!$jsFile || !is_readable($fullPath)){
			error_log("Failed to load or read {$fullPath}");
			return $this;
		}

		$this->_scripts[] = self::SCRIPT_PATH . "/{$namespaceDir}/{$jsFile}";
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
		$path = PUBLIC_PATH . $dir;
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
	 * appendFileDir gets a list of files passed in by $dir and adds all
	 * javascript files to the head with headScript
	 *
	 * @param string $dir is the directory from the PUBLIC_PATH
	 * @returns void
	 */
	function appendFileDir($dir) {
		$files = $this->_readDir($dir);
		$view = $this->getView();

		foreach($files as $file) {
			$view->headScript()->appendFile($file);
		}
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