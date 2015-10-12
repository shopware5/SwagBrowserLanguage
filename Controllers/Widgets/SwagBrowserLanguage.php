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
 * @package    Shopware_Controllers_Frontend_SwagBrowserLanguage
 * @copyright  Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */

use Shopware\SwagBrowserLanguage\Components\ShopFinder;
use Shopware\SwagBrowserLanguage\Components\Translator;
use Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap as Bootstrap;

class Shopware_Controllers_Widgets_SwagBrowserLanguage extends Enlight_Controller_Action
{
    /**
     * @var array $controllerWhiteList
     */
    private $controllerWhiteList = array('detail', 'index', 'listing');

    /**
     * @var ShopFinder $shopFinder
     */
    private $shopFinder = null;

    /**
     * @var Bootstrap $pluginBootstrap
     */
    private $pluginBootstrap = null;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session = null;

    /**
     * @var Translator $translator
     */
    private $translator = null;

    /**
     * @var \Shopware\Models\Shop\Shop null
     */
    private $shop = null;

    /**
     * This function will be called before the widget is being finalized
     */
    public function preDispatch()
    {
        $this->pluginBootstrap = $this->get('plugins')->Frontend()->SwagBrowserLanguage();
        $this->shopFinder = new ShopFinder($this->pluginBootstrap, $this->getModelManager());
        $this->translator = new Translator($this->pluginBootstrap, $this->getModelManager(), $this->get("snippets"), $this->get("db"));
        $this->session = $this->get("session");
        $this->shop = $this->get("shop");

        parent::preDispatch();
    }

    /**
     * Action to return the url for the redirection to javascript.
     * Won't return anything if there is no redirection needed.
     */
    public function redirectAction()
    {
        $this->get('Front')->Plugins()->ViewRenderer()->setNoRender();
        $request = $this->Request();

        if ($this->session->Bot) {
            print json_encode(array(
                'success' => false
            ));
            return;
        }

        $languages = $this->getBrowserLanguages($request);

        $currentLocale = $this->shop->getLocale()->getLocale();
        $currentLanguage = explode('_', $currentLocale);

        //Does this shop have the browser language already?
        if (in_array($currentLocale, $languages) || in_array($currentLanguage[0], $languages)) {
            print json_encode(array(
                'success' => false
            ));
            return;
        }

        if (!$this->allowRedirect($this->Request()->getPost())) {
            print json_encode(array(
                'success' => false
            ));
            return;
        }

        $subShopId = $this->shopFinder->getSubshopId($languages);

        //If the current shop is the destination shop do not redirect
        if ($this->shop->getId() == $subShopId) {
            print json_encode(array(
                'success' => false
            ));
            return;
        }

        print json_encode(array(
            'success' => true,
            'destinationId' => $subShopId,
        ));
    }

    /**
     * Helper function to get all preferred browser languages
     *
     * @param Enlight_Controller_Request_Request $request
     * @return array|mixed
     */
    private function getBrowserLanguages(Enlight_Controller_Request_Request $request)
    {
        $languages = $request->getServer('HTTP_ACCEPT_LANGUAGE');
        $languages = str_replace('-', '_', $languages);

        if (strpos($languages, ',') == true) {
            $languages = explode(',', $languages);
        } else {
            $languages = (array)$languages;
        }

        foreach ($languages as $key => $language) {
            $language = explode(';', $language);
            $languages[$key] = $language[0];
        }
        return (array)$languages;
    }

    /**
     * Make sure that only useful redirects are performed
     *
     * @param array $params
     * @return bool
     */
    private function allowRedirect($params)
    {
        $module = $params['moduleName'] ?: 'frontend';
        $controllerName = $params['controllerName'] ?: 'index';

        // Only process frontend requests
        if ($module !== 'frontend') {
            return false;
        }

        // check whitelist
        if (!in_array($controllerName, $this->controllerWhiteList)) {
            return false;
        }

        // don't redirect payment controllers
        if ($controllerName == 'payment') {
            return false;
        }

        return true;
    }

    /**
     * This action displays the content of the modal box
     */
    public function getModalAction()
    {
        $request = $this->Request();
        $languages = $this->getBrowserLanguages($request);
        $subShopId = $this->shopFinder->getSubshopId($languages);

        $assignedShops = $this->pluginBootstrap->Config()->get("assignedShops");
        $shopsToDisplay = $this->shopFinder->getShopsForModal($assignedShops);

        $snippets = $this->translator->getSnippets($languages);

        $this->View()->loadTemplate('responsive/frontend/plugins/swag_browser_language/modal.tpl');
        $this->View()->assign("snippets", $snippets);
        $this->View()->assign("shops", $shopsToDisplay);
        $this->View()->assign("destinationShop", $this->shopFinder->getShopRepository($subShopId)->getName());
        $this->View()->assign("destinationId", $subShopId);
    }
}
