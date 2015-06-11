<?php

namespace Shopware\SwagBrowserLanguage\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_Controller_EventArgs;
use Enlight_Controller_Request_Request;
use Enlight_Controller_Response_ResponseHttp;
use Enlight_Event_EventArgs;
use Enlight_View_Default;
use Shopware\Models\Shop\Shop;
use Shopware\SwagBrowserLanguage\Components\BotDetector;
use Shopware\SwagBrowserLanguage\Components\ShopFinder;
use Shopware_Controllers_Frontend_Index;
use Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap;
use Zend_Controller_Request_Http;
use Zend_Controller_Response_Http;

class Frontend implements SubscriberInterface
{
    /**
     * @var Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap
     */
    private $pluginBootstrap;

    /**
     * @var ShopFinder $shopFinder
     */
    private $shopFinder;

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
        $this->shopFinder = new ShopFinder($this->pluginBootstrap);
    }

    /**
     * method to register the eventHandler
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_SwagBrowserLanguage' => 'onGetFrontendController',
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
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
        $this->pluginBootstrap->Application()->Snippets()->addConfigDir(
            $this->pluginBootstrap->Path() . 'Snippets/'
        );

        $this->pluginBootstrap->Application()->Template()->addTemplateDir(
            $this->pluginBootstrap->Path() . 'Views/'
        );

        return $this->pluginBootstrap->Path() . 'Controllers/Frontend/SwagBrowserLanguage.php';
    }

    /**
     * Event listener function of the Enlight_Controller_Action_PreDispatch event.
     * Redirect to language subshop if is not set in cookie
     * If the correct language subshop is the default shop, nothing happens
     *
     * @param Enlight_Controller_EventArgs $args
     */
    public function onPreDispatch(Enlight_Controller_EventArgs $args)
    {
        $request = $args->getRequest();
        $response = $args->getResponse();

        if (!$this->allowRedirect($args->getSubject())) {
            return;
        }

        $subShopId = Shopware()->Session()->swagBrowserlanguageShopId;
        if ($subShopId) {
            return;
        }

        if(Shopware()->Session()->Bot) {
            return;
        }

        $languages = $this->getBrowserLanguages($request);
        $subShopId = $this->shopFinder->getSubshopId($languages);

        if ($subShopId == $this->shopFinder->getfirtstSubshopId()) {
            return;
        }

        $params = '';

        if ($this->pluginBootstrap->Config()->get('infobox')) {
            $params = sprintf('?%s=%d', 'show_modal', 1);
        }

        Shopware()->Session()->swagBrowserlanguageShopId = $subShopId;
        $this->redirectToSubShop($this->shopFinder->getShopRepository($subShopId), $request, $response, $params);
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

        /** @var $response Zend_Controller_Response_Http */
        $response = $controller->Response();

        /** @var $view Enlight_View_Default */
        $view = $controller->View();

        $version = Shopware()->Shop()->getTemplate()->getVersion();
        if ($version >= 3) {
            $view->addTemplateDir($this->pluginBootstrap->Path() . '/Views/responsive');
        } else {
            $view->addTemplateDir($this->pluginBootstrap->Path() . '/Views/emotion', 'swag_browser_language');
            $view->extendsTemplate('frontend/plugins/swag_browser_language/index.tpl');
        }

        $show_modal = $request->getParam('show_modal');

        if ($show_modal) {
            $view->assign('show_modal', $show_modal);
        }
    }

    /**
     * Helper function to get all prefered browser languages
     *
     * @param Enlight_Controller_Request_Request $request
     * @return array|mixed
     */
    private function getBrowserLanguages(Enlight_Controller_Request_Request $request)
    {
        $languages = $request->getServer('HTTP_ACCEPT_LANGUAGE');
        $languages = explode(',', $languages);

        foreach ($languages as $key => $language) {
            $language = explode(';', $language);
            $languages[$key] = $language[0];
        }
        return $languages;
    }

    /**
     * Helper function to create a redirect to a subshop
     *
     * @param Shop $newShop
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_Controller_Response_ResponseHttp $response
     * @param $params
     */
    private function redirectToSubShop(Shop $newShop, Enlight_Controller_Request_Request $request,  Enlight_Controller_Response_ResponseHttp $response, $params)
    {
        $url = sprintf(
            '%s://%s%s/%s',
            $request->getScheme(),
            $newShop->getHost(),
            $newShop->getBaseUrl(),
            $params
        );

        $response->setRedirect($url);
    }

    /**
     * Make sure that only useful redirects are performed
     *
     * @param Enlight_Controller_Action $controller
     * @return bool
     */
    private function allowRedirect(\Enlight_Controller_Action $controller)
    {
        $request = $controller->Request();
        $module = $request->getModuleName();
        $controllerName = $request->getControllerName() ?: 'index';

        // Only process frontend requests
        if ($module !== 'frontend') {
            return false;
        }

        // check whitelist
        if (!in_array($controllerName, $this->controllerWhiteList)) {
            return false;
        }

        // don't redirect ajax request
        if ($request->isXmlHttpRequest()) {
            return false;
        }

        // don't redirect payment controllers
        if ($controller instanceof \Shopware_Controllers_Frontend_Payment) {
            return false;
        }

        return true;
    }
}