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
 * ZendX_Sencha_Direct_Request class.
 * 
 * @extends Zend_Controller_Request_Http
 */
class ZendX_Sencha_Direct_Request extends Zend_Controller_Request_Http
{
	/**
	 * _tid
	 * The transaction id for this request.
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_tid;
	
	/**
	 * _tidKey
	 * 
	 * (default value: 'tid')
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_tidKey = 'tid';
	
	/**
	 * _type
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_type;

	/**
	 * _typeKey
	 * 
	 * (default value: 'type')
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_typeKey = 'type';

	/**
	 * _isDirectRequest
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 * @static
	 */
	protected static $_isDirectRequest = false;

	/**
	 * _isDirectSubmit
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 * @static
	 */
	protected static $_isDirectSubmit = false;

	/**
	 * _isBatchRequest
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 * @static
	 */
	protected static $_isBatchRequest = false;

	/**
	 * isDirectRequest function.
	 * 
	 * @access public
	 * @return void
	 */
	public function isDirectRequest()
	{
		return self::$_isDirectRequest;		
	}

	/**
	 * isDirectSubmit function.
	 * 
	 * @access public
	 * @return void
	 */
	public function isDirectSubmit()
	{
		return self::$_isDirectSubmit;
	}

	/**
	 * isBatchRequest function.
	 * 
	 * @access public
	 * @return void
	 */
	public function isBatchRequest()
	{
		return self::$_isBatchRequest;
	}

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $uri. (default: null)
	 * @return void
	 */
	public function __construct($uri = null)
	{
		parent::__construct($uri);
		$this->_checkDirect();
	}

	/**
	 * _checkDirect function.
	 * 
	 * @access private
	 * @return void
	 */
	private function _checkDirect()
	{
		if ($this->isXmlHttpRequest()){
			if (strpos($this->getHeader('Content-Type'), 'application/json') !== false) {
				try {
					$data = Zend_Json::decode($this->getRawBody());
					if ($data){
						if (is_int(key($data))){
							self::$_isBatchRequest = true;
							$data = array_shift($data);
						}
						
						if (array_key_exists('tid', $data) &&
							array_key_exists('type', $data) &&
							$data['type'] == 'rpc'){
							self::$_isDirectRequest = true;
						}
					}
				} catch (Zend_Json_Exception $e){}
			} else {
				$data = $this->getPost();
				if (array_key_exists('extTID', $data) &&
					array_key_exists('extType', $data) &&
					$data['extType'] == 'rpc'){
					self::$_isDirectRequest = true;
					self::$_isDirectSubmit = true;
				}
			}
		}
	}

    /**
     * getTid function.
     * 
     * @access public
     * @return void
     */
    public function getTid()
    {
        if (null === $this->_tid) {
            $this->_tid = $this->getParam($this->getTidKey());
        }

        return $this->_tid;
    }

    /**
     * setTid function.
     * 
     * @access public
     * @param mixed $value
     * @return void
     */
    public function setTid($value)
    {
        $this->_tid = $value;
        return $this;
    }

    /**
     * getTid function.
     * 
     * @access public
     * @return void
     */
    public function getType()
    {
        if (null === $this->_type) {
            $this->_type = $this->getParam($this->getTypeKey());
        }

        return $this->_type;
    }

    /**
     * setType function.
     * 
     * @access public
     * @param mixed $value
     * @return void
     */
    public function setType($value)
    {
        $this->_type = $value;
        return $this;
    }
    
    /**
     * getTidKey function.
     * 
     * @access public
     * @return void
     */
    public function getTidKey()
    {
        return $this->_tidKey;
    }

    /**
     * setTidKey function.
     * 
     * @access public
     * @param mixed $key
     * @return void
     */
    public function setTidKey($key)
    {
        $this->_tidKey = (string) $key;
        return $this;
    }
    
    /**
     * getTypeKey function.
     * 
     * @access public
     * @return void
     */
    public function getTypeKey()
    {
    	return $this->_typeKey;
    }

	/**
	 * setTypeKey function.
	 * 
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function setTypeKey($key)
	{
        $this->_typeKey = (string) $key;
        return $this;
	}
}