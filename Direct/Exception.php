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
 * ZendX_Sencha_Direct_Exception class.
 * 
 * @extends Zend_Exception
 */
class ZendX_Sencha_Direct_Exception extends Zend_Exception
{
	/**
	 * _tid
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_tid;

	/**
	 * setTid function.
	 * 
	 * @access public
	 * @param mixed $tid
	 * @return void
	 */
	public function setTid($tid)
	{
		$this->_tid = (int) $tid;
	}

	/**
	 * getTid function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getTid()
	{
		return $this->_tid;
	}

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $tid
	 * @param mixed $e
	 * @param mixed $code. (default: null)
	 * @return void
	 */
	public function __construct($tid, $e, $code=null)
	{
		if ($e instanceof Exception) {
			parent::__construct($e->getMessage(), $e->getCode(), $e);
		} else {
			parent::__construct($e, $code);
		}

		$this->setTid($tid);
	}
}