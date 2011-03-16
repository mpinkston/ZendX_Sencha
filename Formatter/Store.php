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
 * ZendX_Sencha_Formatter_Store class.
 * 
 */
class ZendX_Sencha_Formatter_Store
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
		if ($data instanceof Zend_Paginator) {
			$reader = new ZendX_Sencha_Formatter_Store_Paginator();
		} else if ($data instanceof Zend_Db_Table_Rowset) {
			$reader = new ZendX_Sencha_Formatter_Store_Rowset();
		} else if (is_array($data)) {
			$reader = new ZendX_Sencha_Formatter_Store_Array();
		} else {
			throw new Zend_Exception('Could not find appropriate reader for data');
		}

		return $reader->format($data);
	}
}