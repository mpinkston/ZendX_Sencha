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
 * ZendX_Sencha_Formatter_Exception class.
 * I wonder if it's ok to have a file named Exception.php that doesn't define
 * a class that extends Exception..
 * 
 */
class ZendX_Sencha_Formatter_Exception implements ZendX_Sencha_Formatter_Interface
{
	/**
	 * format function.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function format($data)
	{
		if ($data instanceof ZendX_Sencha_Direct_Exception) {
			return array(
				'type'		=> 'exception',
				'tid'		=> $data->getTid(),
				'message'	=> $data->getMessage(),
				'where'		=> $data->getTrace()
			);
		} else if ($data instanceof Exception) {
			return array(
				'type'		=> 'exception',
				'success'	=> false, // ?
				'message'	=> $data->getMessage(),
				'where'		=> $data->getTrace()
			);
		}
	}
}