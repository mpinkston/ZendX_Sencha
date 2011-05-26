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
 * ZendX_Sencha_Direct_Api class.
 * This class generates the configuration for Ext.Direct.addProvider
 * It exclusively uses the docbloc to configure the provider.
 * 
 */
class ZendX_Sencha_Direct_Api
{
	// The Zend_Session_Namespace name in which all api definitions
	// will be stored
	protected static $_sessionNamespace = 'ExtDirect_NS';

	// The default namespace to use for rpc functions
	protected static $_defaultNamespace = 'App';

	// The URL to which the rpc client should make requests
	protected static $_routerUrl		= '/index.php';

	// The type of provider (we haven't expiramented with polling yet)
	protected static $_providerType		= 'remoting';

	// This will expose the action as Namespace.rpc
	// on the server.
	protected static $_nsSuffix 		= '.rpc';

	// specifies whether or not the method will be
	// exposed as an rpc method	
	protected static $_remoteAttribute	= 'remotable';

	// specifies whether this method will be receiving values
	// from a form
	protected static $_formAttribute	= 'formHandler';

	// Can be used in the docblock of a class or a method to 
	// modify its name as it appears to the rpc client.
	protected static $_nameAttribute	= 'remoteName';
	
	// This is a standard docblock attribute.	
	protected static $_paramAttribute	= 'param';

	/**
	 * _session
	 * 
	 * @var mixed
	 * @access private
	 * @static
	 */
	private static $_session;
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
        if (!self::$_session instanceof Zend_Session_Namespace) {
			require_once 'Zend/Session/Namespace.php';
            self::$_session = new Zend_Session_Namespace(self::$_sessionNamespace);
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
		if ($namespace !== null){
			unset(self::$_session->$namespace);
		} else {
			self::$_session->unsetAll();
		}
	}
	
	/**
	 * getNsSuffix function.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function getNsSuffix()
	{
		return self::$_nsSuffix;
	}

	/**
	 * getNamespaces function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getProviders()
	{
		if (!self::$_session){
			return array();
		}
		$iterator = self::$_session->getIterator();
		$providers = array();
		foreach ($iterator as $ns => $provider) {
			$providers[$ns . self::$_nsSuffix] = $this->getProvider($ns);
		}
		return $providers;
	}

	/**
	 * getProvider function.
	 * 
	 * @access public
	 * @param mixed $namespace. (default: null)
	 * @return void
	 */
	public function getProvider($namespace=null)
	{
		$ns = $this->_formatNamespace($namespace);

		if (!isset(self::$_session->$ns)){
			return null;
		}

		$provider = array(
			'type'		=> self::$_providerType,
			'url'		=> self::$_routerUrl,
			'namespace'	=> $ns . self::$_nsSuffix
		);

		$classes = self::$_session->$ns;
		foreach ($classes as $class => $config) {
			foreach ($config['methods'] as $method => $attrs){
				$provider['actions'][$class][] = array(
					'name'			=> $method,
					'len'			=> $attrs['length'],
					'formHandler'	=> $attrs['formHandler']
				);
			}
		}
		return $provider;
	}

	/**
	 * _cached function.
	 * 
	 * @access private
	 * @param mixed $className
	 * @return void
	 */
	private function _cached($className, $namespace=null)
	{
		if (!isset(self::$_session->$namespace)){
			return false;
		}

		$classes = self::$_session->$namespace;
		if (is_array($classes)){
			foreach ($classes as $class => $config) {
				if (array_key_exists('className', (array) $config) && $config['className'] == $className){
					if (!array_key_exists('fullPath', $config) || !array_key_exists('relFiles', $config)){
						return false;
					}
					$files = array_merge((array) $config['fullPath'], (array) $config['relFiles']);
					foreach ($files as $file){
						if (is_readable($file) && $config['mtime'] < filemtime($file)){
							return false;
						}
					}
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * _getAncestors function.
	 * 
	 * @access private
	 * @param mixed $rc
	 * @param mixed $relatedFiles
	 * @param mixed $mtime
	 * @return void
	 */
	private function _getAncestors($rc, &$mtime, &$relatedFiles=array()){
		$tdf = $rc->getDeclaringFile();
		if ($p = $rc->getParentClass()){
			$f = $p->getDeclaringFile();
			$filename = $f->getFileName();
			array_push($relatedFiles, $filename);
			$filemtime = filemtime($filename);
			if ($filemtime > $mtime){
				$mtime = $filemtime;
			}
			$this->_getAncestors($p, $mtime, $relatedFiles);
		}
		return $relatedFiles;
	}

	/**
	 * _formatNamespace function.
	 * 
	 * @access private
	 * @param mixed $namespace. (default: null)
	 * @return void
	 */
	private function _formatNamespace($namespace=null)
	{
		$ns = $namespace?$namespace:self::$_defaultNamespace;
		$ns = preg_replace('/[^a-z]/i', '', $ns);
		return ucfirst(strtolower($ns));
	}

	/**
	 * add function.
	 * add a remotable class.
	 * $class must be a class instance or a string 
	 * representing an instantiable class. 
	 *
	 * @access public
	 * @param mixed $config
	 * @param mixed $namespace. (default: null)
	 * @return void
	 */
	public function add($class, $namespace=null)
	{
		$ns = $this->_formatNamespace($namespace);

		if (is_string($class) && class_exists($class)){
			$className = $class;
			$instance = new $class();
		} else if (is_object($class)) {
			$className = get_class($class);
			$instance = $class;
		} else {
			throw new Sencha_Direct_Exception('Invalid argument passed to ' . __METHOD__);
		}

		if ($this->_cached($className, $ns)){
			return $this;
		}

		// Analyze the class;
		$rc = new Zend_Reflection_Class($instance);
		$cFile = $rc->getDeclaringFile();
		$cDoc  = $rc->getDocBlock();

		$cTag = $cDoc->getTag(self::$_nameAttribute);
		if ($cTag){
			$cName = trim($cTag->getDescription());
		} else if ($instance instanceof Zend_Controller_Action) {
			$cName = preg_replace('/Controller$/', '', array_pop(explode('_', $rc->name)));
		} else {
			$cName = array_pop(explode('_', $rc->name));
		}
		
		$fileName = $cFile->getFileName();
		$mtime = filemtime($fileName);
		$relatedFiles = array();

		$classConfig = array(
			'className'	=> $rc->name,
			'fullPath'	=> $fileName,
			'relFiles'	=> $this->_getAncestors($rc, $mtime),
			'mtime'		=> $mtime,
			'methods'	=> array()
		);

		foreach($rc->getMethods() as $cMethod){
			$mDoc = $cMethod->getDocBlock();
			if (!$mDoc->hasTag('remotable')){ continue; }
			$mTag = $mDoc->getTag(self::$_nameAttribute);
			$pTags = $mDoc->getTags(self::$_paramAttribute);

			if ($mTag){
				$mName = trim($mTag->getDescription());
			} else {
				// Strip 'Action' from the end of method names by default.
				$mName = preg_replace('/Action$/', '', $cMethod->name);
			}

			$classConfig['methods'][$mName] = array(
				'methodName'	=> $cMethod->name,
				'length'		=> count($pTags),
				'formHandler'	=> $mDoc->hasTag(self::$_formAttribute)
			);
		}
		
		$tmpArr = array();
		if (isset(self::$_session->$ns)){
			$tmpArr = self::$_session->$ns;
		}
		$tmpArr[$cName] = $classConfig;
		self::$_session->$ns = $tmpArr;
		return $this;
	}
}