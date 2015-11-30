<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Shopware\SwagBrowserLanguage\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_View_Default;
use Shopware\SwagBrowserLanguage\Components\BotDetector;
use Shopware_Controllers_Frontend_Index;
use Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap;
use Zend_Controller_Request_Http;

class Frontend implements SubscriberInterface
{
    /**
     * @var Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap
     */
    private $pluginBootstrap;

    /**
     * @var array $controllerWhiteList
     */
    private $controllerWhiteList = array('detail', 'index', 'listing');

    /**
     * the constructor of the frontendSubscriber
     *
     * @param Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap
     */
    public function __construct(Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap)
    {
        $this->pluginBootstrap = $pluginBootstrap;
    }

    /**
     * method to register the eventHandler
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Dispatcher_ControllerPath_Widgets_SwagBrowserLanguage' => 'onGetFrontendController',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend' => 'onPostDispatchFrontend'
        );
    }

    /**
     * Returns the path to the frontend controller.
     *
     * @return string
     */
    public function onGetFrontendController()
    {
        $this->pluginBootstrap->Application()->Snippets()->addConfigDir($this->pluginBootstrap->Path() . 'Snippets/');

        $this->pluginBootstrap->Application()->Template()->addTemplateDir($this->pluginBootstrap->Path() . 'Views/');

        return $this->pluginBootstrap->Path() . 'Controllers/Widgets/SwagBrowserLanguage.php';
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

        /** @var $request Zend_Controller_Request_Http */
        $request = $controller->Request();

        if (!in_array($request->getControllerName(), $this->controllerWhiteList)) {
            return;
        }

        $assignedShops = $this->pluginBootstrap->Config()->get("assignedShops");

        if (empty($assignedShops)) {
            return;
        }

        /** @var $view Enlight_View_Default */
        $view = $controller->View();

        $version = Shopware()->Shop()->getTemplate()->getVersion();
        if ($version >= 3) {
            $view->addTemplateDir($this->pluginBootstrap->Path() . '/Views/responsive');
        } else {
            $view->addTemplateDir($this->pluginBootstrap->Path() . '/Views/emotion', 'swag_browser_language');
            $view->extendsTemplate('frontend/plugins/swag_browser_language/index.tpl');
        }
    }
}
