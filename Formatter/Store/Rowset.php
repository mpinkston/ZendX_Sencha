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
 * ZendX_Sencha_Formatter_Store_Rowset class.
 * 
 * @extends ZendX_Sencha_Formatter_Store_Abstract
 */
class ZendX_Sencha_Formatter_Store_Rowset extends ZendX_Sencha_Formatter_Store_Abstract
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
		if (!($data instanceof Zend_Db_Table_Rowset)){
			throw new Zend_Exception('Data passed to ' . __CLASS__ . ' must be of type Zend_Paginator');
		}
		return parent::format($data);
	}
	
	/**
	 * getFields function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getFields()
	{
		$table = $this->getData()->getTable();
		return $this->_getFieldsFromTable($table);
	}

	/**
	 * _getFieldsFromTable function.
	 * 
	 * @access protected
	 * @param mixed Zend_Db_Table $table
	 * @return void
	 */
	protected function _getFieldsFromTable(Zend_Db_Table $table)
	{
		$fields = array();
		$meta = $table->info(Zend_Db_Table::METADATA);
		foreach ($table->info('cols') as $col){
			$type = $this->getExtType($meta[$col]['DATA_TYPE']);
			$field = array(
				'name'	=> $col,
				'type'	=> $type
			);
			if ('date' == $type){
				$field['dateFormat'] = self::getDateFormat($meta[$col]['DATA_TYPE']);
			}
			array_push($fields, $field);
			unset($type);
		}
		return $fields;
	}

	/**
	 * getRows function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getRows()
	{
		return $this->getData()->toArray();
	}
	
	/**
	 * getTotal function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getTotal()
	{
		return count($this->getData());
	}
}