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
 * @subpackage SwagUnlockMerchants
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
     * Returns the current version of the plugin.
     * @return string
     */
    public function getVersion()
    {
        return "1.0.0";
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
        $this->registerController();

        return true;
    }

    /**
     * Registers the plugin controller event for the backend controller SwagUnlockMerchants
     */
    public function registerController()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Front_StartDispatch',
            'onEnlightControllerFrontStartDispatch'
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
        return true;
    }

    /**
     * Event listener function of the Enlight_Controller_Front_StartDispatch event.
     * Set the subshop id in the cookie if it is unset.
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onEnlightControllerFrontStartDispatch(Enlight_Event_EventArgs $arguments)
    {
        if (empty($_COOKIE['shop']))
        {
            $languages = $this->getBrowserLanguages();
            $subshops = $this->getSubshops();

            $_COOKIE['shop'] = $this->getSubshopId($languages, $subshops);
        }
    }

    /**
     * Helper function to get all prefered browser languages
     * @return array
     */
    private function getBrowserLanguages()
    {
        $languages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = explode(',', $languages);

        foreach($languages as $key => $language)
        {
            $language = explode(';', $language);
            $languages[$key] = $language[0];
        }

        return($languages);
    }

    /**
     * Helper function to get the ids and languages of all active subshops
     * @return array
     */
    private function getSubshops()
    {
        $sql = "SELECT s_core_shops.id, s_core_locales.locale
                FROM   s_core_shops, s_core_locales
                WHERE  s_core_locales.id = s_core_shops.locale_id
                AND    s_core_shops.active = 1
                ORDER BY s_core_shops.default DESC";
        $subshops = Shopware()->Db()->fetchAll($sql);

        foreach($subshops as $key => $subshop)
        {
            $subshop['locale'] = strtolower($subshop['locale']);
            $subshop['locale'] = str_replace('_', '-', $subshop['locale']);
            $subshop['language'] = explode('-', $subshop['locale']);
            $subshop['language'] = $subshop['language'][0];

            $subshops[$key] = $subshop;
        }

        return($subshops);
    }

    /**
     * Helper function to get the SubshopId of the Shop in the prefered language
     * @param $languages
     * @param $subshops
     * @return mixed
     */
    private function getSubshopId($languages, $subshops)
    {
        foreach($languages as $language)
        {
            foreach($subshops as $subshop)
            {
                if($language === $subshop['locale'])
                {
                   return($subshop['id']);
                }

                if ($language === $subshop['language'])
                {
                    return($subshop['id']);
                }
            }
        }

        return($subshops[0]['id']);
    }
}
