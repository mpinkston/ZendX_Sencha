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

require_once 'Zend/Form.php';

/**
 * ZendX_Sencha_Form class.
 * 
 * @extends Zend_Form
 */
class ZendX_Sencha_Form extends Zend_Form
{
    /**
     * setView function.
     * 
     * @access public
     * @param mixed Zend_View_Interface $view. (default: null)
     * @return void
     */
    public function setView(Zend_View_Interface $view = null)
    {
        if (null !== $view) {
            if (false === $view->getPluginLoader('helper')->getPaths('ZendX_Sencha_View_Helper')) {
                $view->addHelperPath('ZendX/Sencha/View/Helper', 'ZendX_Sencha_View_Helper');
            }
        }
        return parent::setView($view);
    }
}