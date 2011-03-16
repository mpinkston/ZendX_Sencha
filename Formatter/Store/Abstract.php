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
 * Abstract ZendX_Sencha_Formatter_Store_Abstract class.
 * 
 * @abstract
 */
abstract class ZendX_Sencha_Formatter_Store_Abstract
{
	/**
	 * _data
	 * 
	 * @var mixed
	 * @access private
	 */
	private $_data;

	/**
	 * getData function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * format function.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function format($data)
	{
		$this->_data = $data;

		$metaData = array(
			'idProperty'      => 'id',
			'root'            => 'rows',
			'totalProperty'   => 'total',
			'successProperty' => 'success',
			'fields'          => $this->getFields()
		);

		$result = array(
			'metaData'	=> $metaData,
			'success'	=> true,
			'rows'		=> $this->getRows(),
			'total'		=> $this->getTotal()
		);
		
		return $result;
	}
	
	/**
	 * getFields function.
	 * 
	 * @access public
	 * @abstract
	 * @return void
	 */
	abstract public function getFields();
	
	/**
	 * getRows function.
	 * 
	 * @access public
	 * @abstract
	 * @return void
	 */
	abstract public function getRows();
	
	/**
	 * getTotal function.
	 * 
	 * @access public
	 * @abstract
	 * @return void
	 */
	abstract public function getTotal();

	/**
	* getDateFormat function.
	*
	* @access public
	* @param mixed $type
	* @return void
	*/
	public static function getDateFormat($type)
	{
		switch ($type) {
			case 'date':
				return 'Y-m-d';
			case 'time':
				return 'H:i:s';
			case 'datetime':
			case 'timestamp':
				return 'Y-m-d H:i:s';
			case 'year':
				return 'Y';
			default:
				return 'Y-m-d H:i:s';
		}
	}

	/**
	* getExtType function.
	*
	* @access public
	* @param mixed $type
	* @return void
	*/
	public static function getExtType($type)
	{
		switch ($type) {
			case 'date':
			case 'time':
			case 'datetime':
			case 'timestamp':
			case 'year':
				return 'date';

			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'integer':
			case 'bigint':
				return 'int';

			case 'double':
			case 'float':
			case 'decimal':
				return 'float';

			default:
				return 'auto';
		}
	}
}