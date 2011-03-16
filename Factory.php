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
 * ZendX_Sencha_Factory class.
 * 
 */
class ZendX_Sencha_Factory
{
	/**
	 * getStore function.
	 * 
	 * @access public
	 * @static
	 * @param mixed Zend_Db_Table_Abstract $table
	 * @param mixed $options
	 * @return void
	 */
	public static function getStore(Zend_Db_Table_Abstract $table, $options=array())
	{
		$select = $table->select();
		
		// Querying
		if (array_key_exists('fields', $options) && array_key_exists('query', $options)) {
			$queryExpr = array();
			$fields = Zend_Json::decode($options['fields']);
			if (is_array($fields)){
				foreach ($fields as $field){
					if (in_array($field, $table->info('cols'))){
						$queryExpr[] = $table->getAdapter()->quoteInto($field.' LIKE ?', "%{$options['query']}%");
					}
				}
			}
			if (count($queryExpr) > 0){
				$select->where(implode(' OR ', $queryExpr));
			}
		}
		
		// Filtering
		if (array_key_exists('filter', $options)){
			$filters = Zend_Json::decode($options['filter']);
			foreach ($filters as $filter) {
				if (!isset($filter['value']) ||
					!isset($filter['field']) ||
					!isset($filter['type']) ||
					!in_array($filter['field'], $table->info('cols'))){
					continue;
				}
			
				switch ($filter['type']) {
					case 'boolean':
						// TODO..
						break;
						
					case 'date':
						// TODO..
						break;
						
					case 'list':
						$values = (array) $filter['value'];
						$queryExpr = array();
						foreach ($values as $value){
							$queryExpr[] = $table->getAdapter()->quoteInto("{$filter['field']} = ?", $value);
						}
						if (count($queryExpr) > 0){
							$select->where(implode(' OR ', $queryExpr));
						}
                        break;
						
					case 'numeric':
						// TODO..
						break;
						
					case 'string':
					default:
						// TODO..
						break;
				}
			}
		}
		
		// Sorting
		if (array_key_exists('sort', $options) && in_array($options['sort'], $table->info('cols'))){
			$sort	= $options['sort'];
			$dir	= (isset($options['dir'])&&strtolower($options['dir']) == 'desc')?'DESC':'ASC';
			$select->order("{$sort} {$dir}");
		}

		// Create the paginator
		$adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
		$paginator = new Zend_Paginator($adapter);
		$paginator->setDefaultItemCountPerPage(100);
		
		if (array_key_exists('limit', $options)){
			$paginator->setItemCountPerPage($options['limit']);
		}
		if (array_key_exists('start', $options)){
			$page = floor($options['start']/$paginator->getItemCountPerPage()) + 1;
			$paginator->setCurrentPageNumber($page);
		}

		$formatter = new ZendX_Sencha_Formatter_Store_Paginator();
		return $formatter->format($paginator);
	}
}