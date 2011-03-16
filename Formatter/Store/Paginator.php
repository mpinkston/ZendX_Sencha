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
 * ZendX_Sencha_Formatter_Store_Paginator_SelectExtractor class.
 * grants access to the protected _select property in the DbSelect adapter
 * 
 * @extends Zend_Paginator_Adapter_DbSelect
 */
class ZendX_Sencha_Formatter_Store_Paginator_SelectExtractor extends Zend_Paginator_Adapter_DbSelect
{
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed Zend_Paginator_Adapter_DbSelect $adapter
	 * @return void
	 */
	public function __construct(Zend_Paginator_Adapter_DbSelect $adapter)
	{
		$this->_select = $adapter->_select;
	}
	
	/**
	 * getSelect function.
	 * 
	 * @access public
	 * @return Zend_Db_Select
	 */
	public function getSelect()
	{
		return $this->_select;
	}
}

/**
 * ZendX_Sencha_Formatter_Store_Paginator class.
 * 
 * @extends ZendX_Sencha_Formatter_Store_Abstract
 */
class ZendX_Sencha_Formatter_Store_Paginator extends ZendX_Sencha_Formatter_Store_Abstract
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
		if (!($data instanceof Zend_Paginator)){
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
		$adapter = $this->getData()->getAdapter();
		if ($adapter instanceof Zend_Paginator_Adapter_DbTableSelect){
			$se = new ZendX_Sencha_Formatter_Store_Paginator_SelectExtractor($adapter);
			$select = $se->getSelect();
			$table = $select->getTable();
			return $this->_getFieldsFromTable($table);
		}
		return array();
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
		$currentItems = $this->getData()->getCurrentItems();
        if ($currentItems instanceof Zend_Db_Table_Rowset_Abstract) {
            return $currentItems->toArray();
        } else {
            return $currentItems;
        }
	}
	
	/**
	 * getTotal function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getTotal()
	{
		return $this->getData()->getTotalItemCount();
	}
}