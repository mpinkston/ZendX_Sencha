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

/** Zend_Form_SubForm */
require_once 'Zend/Form/SubForm.php';

/**
 * ZendX_Sencha_Form_SubForm class.
 * 
 * @extends Zend_Form_SubForm
 */
class ZendX_Sencha_Form_SubForm extends Zend_Form_SubForm
{
    /**
     * _senchaViewPathRegistered
     * 
     * (default value: false)
     * 
     * @var bool
     * @access protected
     */
    protected $_senchaViewPathRegistered = false;

    /**
     * __construct function.
     * 
     * @access public
     * @param mixed $options. (default: null)
     * @return void
     */
    public function __construct($options = null)
    {
        $this->addPrefixPath('ZendX_Sencha_Form_Decorator', 'ZendX/Sencha/Form/Decorator', 'decorator')
             ->addPrefixPath('ZendX_Sencha_Form_Element', 'ZendX/Sencha/Form/Element', 'element')
             ->addElementPrefixPath('ZendX_Sencha_Form_Decorator', 'ZendX/Sencha/Form/Decorator', 'decorator')
             ->addDisplayGroupPrefixPath('ZendX_Sencha_Form_Decorator', 'ZendX/Sencha/Form/Decorator')
             ->setDefaultDisplayGroupClass('ZendX_Sencha_Form_DisplayGroup');
        parent::__construct($options);
    }

    /**
     * Load the default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
    	// nothing for now..
		return;
    }

    /**
     * Get view
     *
     * @return Zend_View_Interface
     */
    public function getView()
    {
        $view = parent::getView();
        if (!$this->_senchaViewPathRegistered) {
            if (false === $view->getPluginLoader('helper')->getPaths('ZendX_Sencha_View_Helper')) {
                $view->addHelperPath('ZendX/Sencha/View/Helper', 'ZendX_Sencha_View_Helper');
            }
            $this->_senchaViewPathRegistered = true;
        }
        return $view;
    }
}
