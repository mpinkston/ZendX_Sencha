<?php

/**
 * ZendX_Sencha_Formatter_Form class.
 * 
 */
class ZendX_Sencha_Formatter_Form implements ZendX_Sencha_Formatter_Interface
{
	/**
	 * format function.
	 * 
	 * @access public
	 * @param mixed $form
	 * @return void
	 */
	public function format($form)
	{
		if (!($form instanceof Zend_Form)){
			throw new Zendx_Sencha_Formatter_Exception(__METHOD__ . ' expects an instance of Zend_Form as an argument');
		}
	
		$result = array(
			'success' => true
		);

		foreach ($form->getElements() as $name => $element) {
			$result['data'][$name] = $element->getValue();
		}

		if ($form->isErrors()){
			$result['success'] = false;
			foreach ($form->getElements() as $name => $element){
				$result['errors'][$name] = implode(', ', $element->getMessages());
			}
		}
		
		return $result;
	}
}