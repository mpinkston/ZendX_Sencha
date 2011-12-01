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
	 * getForm function.
	 * 
	 * @access public
	 * @static
	 * @param mixed Zend_Form $form
	 * @param array $options. (default: array())
	 * @return void
	 */
	public static function getForm(Zend_Form $form, $options=array())
	{
		$formatter = new ZendX_Sencha_Formatter_Form();	
		return $formatter->format($form);
	}

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
			if (is_array($options['fields'])){
				$fields = $options['fields'];
			} else if (is_scalar($options['fields'])) {
				$fields = Zend_Json::decode($options['fields']);
			}

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
			$queryExpr = array();
			$options['filter'] = stripslashes($options['filter']);
			try {
				$options['filter'] = Zend_Json::decode($options['filter']);
			} catch (Exception $e){}
			
			if (is_array($options['filter'])){
				foreach ($options['filter'] as $filter){				
					if (!array_key_exists('property', $filter) || 
						!array_key_exists('value', $filter) ||
						!in_array($filter['property'], $table->info('cols'))){
						continue;
					}
					
					$exactMatch = (isset($filter['exactMatch'])&&$filter['exactMatch']===true)?true:false;
					$anyMatch = (isset($filter['anyMatch'])&&$filter['anyMatch']===true)?true:false;
					$caseSensitive = (isset($filter['caseSensitive'])&&$filter['caseSensitive']===true)?true:false;
					
					if ($anyMatch === true){
						$queryExpr[] = $table->getAdapter()->quoteInto(
							"{$filter['property']} REGEXP" . ($caseSensitive?" BINARY":"") . " ?", 
							$filter['value']
						);
					} else {
						$queryExpr[] = $table->getAdapter()->quoteInto(
							"{$filter['property']} REGEXP" . ($caseSensitive?" BINARY":"") . " ?", 
							'^' . $filter['value'] . ($exactMatch?'$':'')
						);
					}
				}
			}
			if (count($queryExpr) > 0){
				$select->where(implode(' AND ', $queryExpr));
			}
		}

		// Sorting
		if (array_key_exists('sort', $options)) {
		
			if (is_scalar($options['sort'])){
				$options['sort'] = stripslashes($options['sort']);
				try {
					$options['sort'] = Zend_Json::decode($options['sort']);
				} catch (Exception $e){}
			}

			if (is_array($options['sort'])) {
				$sortConfig = array();
				foreach ($options['sort'] as $sc){
					if (array_key_exists('property', $sc) && in_array($sc['property'], $table->info('cols'))){					
						$sort = $sc['property'];
						$dir = (isset($sc['direction'])&&strtolower($sc['direction'])=='desc')?'DESC':'ASC';
						$sortConfig[] = "{$sort} {$dir}";
					}
				}
				$select->order(implode(', ', $sortConfig));
			} else if (is_scalar($options['sort']) && in_array($options['sort'], $table->info('cols'))){		
				$sort	= $options['sort'];
				$dir	= (isset($options['dir'])&&strtolower($options['dir']) == 'desc')?'DESC':'ASC';
				$select->order("{$sort} {$dir}");
			}
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