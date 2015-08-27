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

use Shopware\Models\Shop\Shop;
use Shopware\SwagBrowserLanguage\Components\ShopFinder;
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
     * @return ShopFinder
     */
    private function getShopFinder()
    {
        if ($this->shopFinder === null) {
            $this->shopFinder = new ShopFinder($this->getPluginBootstrap());
        }

        return $this->shopFinder;
    }

    private function getPluginBootstrap()
    {
        if ($this->pluginBootstrap === null) {
            $this->pluginBootstrap = $this->get('plugins')->Frontend()->SwagBrowserLanguage();
        }
        return $this->pluginBootstrap;
    }

    /**
     * Action to return the url for the redirection to javascript.
     * Won't return anything if there is no redirection needed.
     */
    public function redirectAction()
    {
        $this->get('Front')->Plugins()->ViewRenderer()->setNoRender();
        $request = $this->Request();

        if(Shopware()->Session()->Bot) {
            return;
        }

        $languages = $this->getBrowserLanguages($request);

        $currentLocale = Shopware()->Shop()->getLocale()->getLocale();
        $currentLanguage = explode('_', $currentLocale);

        //Does this shop have the browser language already?
        if(in_array($currentLocale, $languages) || in_array($currentLanguage[0], $languages)) {
            return;
        }

        if (!$this->allowRedirect($this->Request()->getPost())) {
            return;
        }

        $subShopId = $this->getShopFinder()->getSubshopId($languages);

        echo json_encode(
            array(
                'destinationId' => $subShopId,
            )
        );
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
        $languages = explode(',', $languages);

        foreach ($languages as $key => $language) {
            $language = explode(';', $language);
            $languages[$key] = $language[0];
        }
        return $languages;
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
        $subShopId = $this->getShopFinder()->getSubshopId($languages);

        $this->View()->loadTemplate('responsive/frontend/plugins/swag_browser_language/modal.tpl');

        $this->View()->assign("shops", $this->getShopFinder()->getShopsForModal(Shopware()->Config()->get("assignedShops")));
        $this->View()->assign("destinationShop", $this->getShopFinder()->getShopRepository($subShopId)->getName());
        $this->View()->assign("destinationId", $subShopId);
    }
}