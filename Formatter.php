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
 * ZendX_Sencha_Formatter class.
 * 
 */
class ZendX_Sencha_Formatter
{
	/**
	 * getFormatter function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $format
	 * @return void
	 */
	public static function getFormatter($format)
	{
		$formatter = 'ZendX_Sencha_Formatter_'.ucfirst(strtolower($format));
		
		if (class_exists($formatter)){
			return new $formatter();
		}

		throw new Zend_Exception('Could not find formatter');
	}
}