<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBrowserLanguage\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_View_Default;
use Shopware_Controllers_Frontend_Index;
use Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap;
use Zend_Controller_Request_Http;

/**
 * Class Frontend
 * @package SwagBrowserLanguage\Subscriber
 */
class Frontend implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDir;

    /**
     * @var array $controllerWhiteList
     */
    private $controllerWhiteList = array('detail', 'index', 'listing');

    /**
     * Frontend constructor.
     * @param string $pluginDir
     */
    public function __construct($pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    /**
     * method to register the eventHandler
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Widgets_SwagBrowserLanguage' => 'onGetFrontendController',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'onPostDispatchFrontend'
        ];
    }

    /**
     * Returns the path to the frontend controller.
     *
     * @return string
     */
    public function onGetFrontendController()
    {
        return $this->pluginDir . '/Controllers/Widgets/SwagBrowserLanguage.php';
    }

    /**
     * Event listener function of the Enlight_Controller_Action_PostDispatch event.
     *
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontend(Enlight_Event_EventArgs $arguments)
    {
        /** @var $controller Shopware_Controllers_Frontend_Index */
        $controller = $arguments->getSubject();

        /** @var \Enlight_Controller_Request_RequestHttp $request */
        $request = $controller->Request();

        if (!in_array($request->getControllerName(), $this->controllerWhiteList)) {
            return;
        }

        /** @var $view Enlight_View_Default */
        $view = $controller->View();

        $view->addTemplateDir($this->pluginDir . '/Resources/views');
    }
}
