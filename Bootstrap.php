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
        $this->createConfiguration();
        $this->registerController();

        return true;
    }

    /**
     * Creates the configuration fields.
     * Selects first a row of the s_articles_attributes to get all possible article attributes.
     */
    private function createConfiguration()
    {
        $subshops = $this->getSubshops();

        $store = array();

        foreach ($subshops as $key => $subshop) {
            $store[] = array($subshop['id'], $subshop['name']);
        }

        $form = $this->Form();

        $form->setElement(
            'select',
            'default',
            array(
                'label' => 'Default-Subshop',
                'store' => $store,
                'required' => true,
                'value' => $subshops[0]['id']
            )
        );

        $form->setElement(
            'checkbox',
            'infobox',
            array('label' => 'Hinweis anzeigen')
        );
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
        /** @var $enlightController Enlight_Controller_Front */
        $enlightController = $arguments->getSubject();

        $response = $enlightController->Response();

        /** @var $response Enlight_Controller_Request_RequestHttp */
        $request = $enlightController->Request();

        $subshopId = $request->getCookie('shop');

        if (empty($subshopId)) {
            $languages = $this->getBrowserLanguages($request);
            $subshops = $this->getSubshops();

            $_COOKIE['shop'] = $this->getSubshopId($languages, $subshops);
        }
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
     * Helper function to get the needed data of all active subshops
     * @return array
     */
    private function getSubshops()
    {
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $builder = $repository->getActiveQueryBuilder();
        $builder->orderBy('shop.default', 'DESC');
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
     * @param $subshops
     * @return mixed
     */
    private function getSubshopId($languages, $subshops)
    {
        foreach ($languages as $language) {
            foreach ($subshops as $subshop) {
                if ($language === $subshop['locale']) {
                    return ($subshop['id']);
                }

                if ($language === $subshop['language']) {
                    return ($subshop['id']);
                }
            }
        }

        $default = $this->Config()->get('default');

        if(!is_int($default))
        {
            $default = $subshops[0]['id'];
        }

        return ($default);
    }
}
