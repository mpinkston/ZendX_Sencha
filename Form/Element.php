<?php

/**
 * ZendX_Sencha_Form_Element class.
 * 
 * @extends Zend_Form_Element
 */
class ZendX_Sencha_Form_Element extends Zend_Form_Element
{
	/**
	 * getXType function.
	 * 
	 * @access public
	 * @return void
	 */
	public function getXType()
	{
		return $this->xtype;
	}

	/**
	 * loadDefaultDecorators function.
	 * 
	 * @access public
	 * @return void
	 */
	public function loadDefaultDecorators()
	{
		return $this;
	}
}