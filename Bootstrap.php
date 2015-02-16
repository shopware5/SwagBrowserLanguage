<?php

/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package   Shopware_Plugins
 * @subpackage SwagBrowserLanguage
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 */
class Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Returns an array with the capabilities of the plugin.
     * @return array
     */
    public function getCapabilities()
    {
        return array(
            'install' => true,
            'enable' => true,
            'update' => true
        );
    }

    /**
     * Returns the name of the plugin.
     * @return string
     */
    public function getLabel()
    {
        return 'Automatische Sprachshop-Auswahl';
    }

    /**
     * Returns the current version of the plugin.
     *
     * @return mixed
     * @throws Exception
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Returns an array with some informations about the plugin.
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'description' => file_get_contents($this->Path() . 'info.txt'),
            'link' => 'http://www.shopware.de/'
        );
    }

    /**
     * Install function of the plugin bootstrap.
     *
     * Registers all necessary components and dependencies.
     *
     * @return bool
     */
    public function install()
    {
        $this->createConfiguration();
        $this->registerEvents();

        return true;
    }

    /**
     * Creates the configuration fields.
     * Selects first a row of the s_articles_attributes to get all possible article attributes.
     */
    private function createConfiguration()
    {
        $subshops = $this->getLanguageShops();

        $store = array();

        foreach ($subshops as $subshop) {
            $store[] = array($subshop['id'], $subshop['name']);
        }

        $form = $this->Form();

        $form->setElement(
            'select',
            'default',
            array(
                'label' => 'Fallback-Sprachshop',
                'store' => $store,
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
                'required' => true,
                'value' => $subshops[0]['id'],
                'description' => 'Auf diesen Shop wird weitergeleitet, wenn kein zu den Browsersprachen passender Shop existiert.'
            )
        );

        $form->setElement(
            'checkbox',
            'infobox',
            array(
                'label' => 'Hinweis anzeigen',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Wenn aktiviert, wird dem Kunden nach Weiterleitung eine kleine Infobox angezeigt mit der Option zum Hauptshop zurückzukehren.')
        );
    }

    /**
     * Registers the plugin controller event for the backend controller SwagUnlockMerchants
     */
    public function registerEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_SwagBrowserLanguage',
            'onGetFrontendController'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch',
            'onPreDispatch'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch',
            'onPostDispatchFrontend'
        );

        return true;
    }


    /**
     * Uninstall function of the plugin.
     * Fired from the plugin manager.
     * @return bool
     */
    public function uninstall()
    {
        $this->removeSnippets();

        return true;
    }

    /**
     * Event listener function of the Enlight_Controller_Action_PreDispatch event.
     * Redirect to language subshop if is not set in cookie
     * If the correct language subshop is the default shop, nothing happens
     * @param Enlight_Controller_EventArgs $args
     */
    public function onPreDispatch(Enlight_Controller_EventArgs $args)
    {
        $request = $args->getRequest();
        $response = $args->getResponse();

        if($this->checkForBot($request)){
            return;
        }

        if (!$this->allowRedirect($args->getSubject())) {
            return;
        }

        $subshopId = $request->getCookie('shop');

        if ($subshopId) {
            return;
        }

        $languages = $this->getBrowserLanguages($request);
        $subshops = $this->getLanguageShops(Shopware()->Shop()->getId());

        $subshopId = $this->getSubshopId($languages, $subshops);

        if ($subshopId == $subshops[0]['id']) {
            return;
        }

        $params = '';

        if ($this->Config()->get('infobox')) {
            $params = sprintf('?%s=%d', 'show_modal', 1);
        }

        $this->redirectToSubshop($subshopId, $request, $response, $params);
    }

    /**
     * Helper function to get all prefered browser languages
     * @param Enlight_Controller_Request_RequestHttp $request
     * @return array|mixed
     */
    private function getBrowserLanguages(Enlight_Controller_Request_RequestHttp $request)
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
     * Helper function to get the needed data of all active language shops (optional: of a main shop)
     * 
     * @param int $mainShopId
     * @return array
     */
    private function getLanguageShops($mainShopId = 0)
    {
        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $builder = $repository->getActiveQueryBuilder();
        $builder->orderBy('shop.id');

        if ($mainShopId > 0) {
            $builder->andWhere('shop.id = :mainShopId')
                ->orWhere('shop.mainId = :mainShopId')
                ->andWhere('shop.active = 1')
                ->setParameter('mainShopId', $mainShopId);
        }

        $data = $builder->getQuery()->getArrayResult();

        $subshops = array();

        foreach ($data as $subshop) {
            $subshop['locale'] = strtolower($subshop['locale']['locale']);
            $subshop['locale'] = str_replace('_', '-', $subshop['locale']);

            $subshop['language'] = explode('-', $subshop['locale']);
            $subshop['language'] = $subshop['language'][0];

            $subshops[] = array(
                'id' => $subshop['id'],
                'name' => $subshop['name'],
                'locale' => $subshop['locale'],
                'language' => $subshop['language']
            );
        }
        return $subshops;
    }

    /**
     * Helper function to get the SubshopId of the Shop in the prefered language
     * @param $languages
     * @param $subShops
     * @return mixed
     */
    private function getSubshopId($languages, $subShops)
    {
        $subShopId = $this->getSubShopIdByFullBrowserLanguage($languages, $subShops);
        if (!$subShopId) {
            $subShopId = $this->getSubShopIdByBrowserLanguagePrefix($languages, $subShops);
        }
        if (!$subShopId) {
            $subShopId = $this->getDefaultShopId($subShops);
        }
        return $subShopId;
    }

    /**
     * HelperMethod for getSubshopId... get the default ShopId
     *
     * @param $subShops
     * @return int
     */
    private function getDefaultShopId($subShops) {
        $default = $this->Config()->get('default');
        if (!is_int($default)) {
            $default = $subShops[0]['id'];
        }
        return ($default);
    }

    /**
     * HelperMethod for getSubshopId... try to get the LanguageShop by the full BrowserLanguage like [de-DE] or [de-CH]
     *
     * @param $languages
     * @param $subShops
     * @return bool
     */
    private function getSubShopIdByFullBrowserLanguage($languages, $subShops) {
        foreach ($languages as $language) {
            foreach ($subShops as $subshop) {
                $browserLanguage = strtolower($language);
                $shopLocale = strtolower($subshop['locale']);

                if ($browserLanguage === $shopLocale) {
                    return ($subshop['id']);
                }
            }
        }
        return false;
    }

    /**
     * HelperMethod for getSubshopId... try to get the LanguageShop by the BrowserLanguage "prefix" like [de] or [en]
     *
     * @param $languages
     * @param $subShops
     * @return bool
     */
    private function getSubShopIdByBrowserLanguagePrefix($languages, $subShops) {
        foreach ($languages as $language) {
            foreach ($subShops as $subshop) {
                $browserLanguage = strtolower($language);
                $currentLanguageArray = explode('-', $browserLanguage);
                $browserLanguagePrefix = $currentLanguageArray[0];
                $subshopLanguage = $subshop['language'];

                if ($browserLanguagePrefix === $subshopLanguage ) {
                    return ($subshop['id']);
                }
            }
        }
        return false;
    }

    /**
     * Helper function to create a redirect to a subshop
     * @param $subshopId
     * @param $request
     * @param $response
     */
    private function redirectToSubshop($subshopId, $request, $response, $params)
    {
        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $newShop = $repository->getActiveById($subshopId);
        $path = rtrim($newShop->getBasePath(), '/') . '/';
        $response->setCookie('shop', $subshopId, 0, $path);
        $url = sprintf(
            '%s://%s%s/%s',
            $request::SCHEME_HTTP,
            $newShop->getHost(),
            $newShop->getBaseUrl(),
            $params
        );

        $response->setRedirect($url);
    }

    /**
     * Event listener function of the Enlight_Controller_Action_PostDispatch event.
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

        //Check if there is a template and if an exception has occured
        if (!$request->isDispatched() || $response->isException() || !$view->hasTemplate() || $request->getModuleName() != "frontend"
        ) {
            return;
        }

        //Add our plugin template directory to load our slogan extension.
        $view->addTemplateDir($this->Path() . 'Views/');

        $view->extendsTemplate('frontend/plugins/swag_browser_language/index.tpl');

        $show_modal = (int)$request->getParam('show_modal');

        if ($show_modal) {
            $view->assign('show_modal', $request->getBasePath() . $request->getPathInfo());
        }
    }

    /**
     * Returns the path to the frontend controller.
     *
     * @return string
     */
    public function onGetFrontendController()
    {
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );

        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );

        return $this->Path() . 'Controllers/Frontend/SwagBrowserLanguage.php';
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

        $whitelist = array('detail', 'index', 'listing');

        // Only process frontend requests
        if ($module !== 'frontend') {
            return false;
        }

        // check whitelist
        if (!in_array($controllerName, $whitelist)) {
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


    /**
     * This method checks if the UserAgent is a known Bot.
     * If the UserAgent is a Bot, the method returns true .... else false
     *
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    private function checkForBot(Zend_Controller_Request_Http $request)
    {
        $userAgentName = $request->getServer('HTTP_USER_AGENT');
        foreach($this->getBotArray() as $bot) {
            if(strpos(strtolower($userAgentName), strtolower($bot)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the known bot list from the Shopware config
     *
     * @return array
     */
    private function getBotArray()
    {
        return explode(';', Shopware()->Config()->get('botBlackList'));
    }

}
