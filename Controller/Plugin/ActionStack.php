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
 * ZendX_Sencha_Controller_Plugin_ActionStack class.
 * 
 * @extends Zend_Controller_Plugin_ActionStack
 */
class ZendX_Sencha_Controller_Plugin_ActionStack extends Zend_Controller_Plugin_ActionStack
{
    /**
     * Forward request with next action
     * (while being sure to preserve type and tid
     *
     * @param  array $next
     * @return void
     */
    public function forward(Zend_Controller_Request_Abstract $next)
    {
        $request = $this->getRequest();
        if ($this->getClearRequestParams()) {
            $request->clearParams();
        }

        $request->setModuleName($next->getModuleName())
                ->setControllerName($next->getControllerName())
                ->setActionName($next->getActionName())
                ->setParams($next->getParams())
                ->setType($next->getType())
                ->setTid($next->getTid())
                ->setDispatched(false);
    }
}