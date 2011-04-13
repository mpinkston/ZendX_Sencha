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
	 * _session
	 * 
	 * @var mixed
	 * @access private
	 * @static
	 */
	private static $_session;

	/**
	 * _namespace
	 * 
	 * (default value: 'Default')
	 * 
	 * @var string
	 * @access private
	 */
	private $_namespace = 'Default';
	
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
	 * getNamespace function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getNamespace()
	{
		return $this->_namespace;
	}

	/**
	 * setNamespace function.
	 * 
	 * @access public
	 * @param mixed $ns
	 * @return void
	 */
	public function setNamespace($ns)
	{
		$this->_namespace = $ns;
	}
	
	/**
	 * reset function.
	 * 
	 * @access public
	 * @param bool $allNamespaces. (default: false)
	 * @return void
	 */
	public function reset($allNamespaces=false)
	{
		if ($allNamespaces===true){
			self::$_session = null;
		} else {
			$ns = $this->getNamespace();
			self::$_session->$ns = null;
		}
	}

	/**
	 * getProvider function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getProvider()
	{
		$ns = $this->getNamespace();
		$provider = array(
			'type'		=> self::$_providerType,
			'url'		=> self::$_routerUrl,
			'namespace'	=> $ns . self::$_nsSuffix,
		);

		if (!isset(self::$_session->$ns)){
			return null;
		}

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
	 * getClass function.
	 * 
	 * @access public
	 * @param mixed $className
	 * @return void
	 */
	public function getClass($className)
	{
		$ns = $this->getNamespace();
		$api = self::$_session->$ns;
		if (isset($api[$className])){
			return $api[$className];
		}
		return null;
	}

	/**
	 * _cached function.
	 * 
	 * @access private
	 * @param mixed $className
	 * @return void
	 */
	private function _cached($className)
	{
		$ns = $this->getNamespace();
		$classes = self::$_session->$ns;
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
	 * _getParents function.
	 * 
	 * @access private
	 * @param mixed $rc
	 * @param mixed $relatedFiles
	 * @param mixed $mtime
	 * @return void
	 */
	private function _getParents($rc, &$mtime, &$relatedFiles=array()){
		$tdf = $rc->getDeclaringFile();
		if ($p = $rc->getParentClass()){
			$f = $p->getDeclaringFile();
			$filename = $f->getFileName();
			array_push($relatedFiles, $filename);
			$filemtime = filemtime($filename);
			if ($filemtime > $mtime){
				$mtime = $filemtime;
			}
			$this->_getParents($p, &$mtime, &$relatedFiles);
		}
		return $relatedFiles;
	}

	/**
	 * add function.
	 * Add one or more remotable classes.
	 * 
	 * @access public
	 * @param mixed $config
	 * @return void
	 */
	public function add($config)
	{
		if (is_array($config)){
			foreach ($config as $c){
				if (is_string($c) || is_object($c)){
					$this->add($c);
				}
			}
			return $this;
		}
	
		if (is_object($config)){
			$className = get_class($config);
		} else if (class_exists($config)) {
			$className = $config;
			$config = new $config();
		} else {
			throw new Sencha_Direct_Exception('Invalid config sent to ' . __METHOD__);
		}

		if ($this->_cached($className)){
			return $this;
		}

		// Analyze the class;
		$rc = new Zend_Reflection_Class($config);
		$cFile = $rc->getDeclaringFile();
		$cDoc  = $rc->getDocBlock();

		$cTag = $cDoc->getTag(self::$_nameAttribute);
		if ($cTag){
			$cName = trim($cTag->getDescription());
		} else if ($config instanceof Zend_Controller_Action) {
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
			'relFiles'	=> $this->_getParents($rc, &$mtime),
			'mtime'		=> $mtime,
			'methods'	=> array()
		);

		foreach($rc->getMethods() as $cMethod){
			$mDoc = $cMethod->getDocBlock();
			if (!$mDoc->hasTag('remotable')){ continue; }
			$mTag = $mDoc->getTag(self::$_nameAttribute);
			$pTags = $mDoc->getTags(self::$_paramAttribute);
			$mName = $mTag?trim($mTag->getDescription()):$cMethod->name;

			$classConfig['methods'][$mName] = array(
				'methodName'	=> $cMethod->name,
				'length'		=> count($pTags),
				'formHandler'	=> $mDoc->hasTag(self::$_formAttribute)
			);
		}
		
		$ns = $this->getNamespace();

		$tmpArr = array();
		if (isset(self::$_session->$ns)){
			$tmpArr = self::$_session->$ns;
		}
		$tmpArr[$cName] = $classConfig;
		self::$_session->$ns = $tmpArr;
		return $this;
	}
}