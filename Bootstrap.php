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
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_SwagBrowserLanguage',
            'onGetFrontendController'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Front_RouteShutdown',
            'onRouteShutdown'
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
        return true;
    }

    /**
     * Event listener function of the Enlight_Controller_Front_RouteShutdown event.
     * Redirect to language subshop if is not set in cookie
     * @param Enlight_Controller_EventArgs $args
     */
    public function onRouteShutdown(Enlight_Controller_EventArgs $args)
    {
        $request = $args->getRequest();
        $response = $args->getResponse();

        $subshopId = $request->getCookie('shop');

        if (empty($subshopId)) {
            $languages = $this->getBrowserLanguages($request);
            $subshops = $this->getSubshops();

            $subshopId = $this->getSubshopId($languages, $subshops);
            $this->redirectToSubshop($subshopId, $request, $response);

            if ($this->Config()->get('infobox')) {
                $header = $response->getHeaders();
                $url = sprintf(
                    '%s?%s=%d',
                    $header['value'],
                    'show_modal',
                    1
                );

                $response->setRedirect($url);
            }
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

    /**
     * Helper function to create a redirect to a subshop
     * @param $subshopId
     * @param $request
     * @param $response
     */
    private function redirectToSubshop($subshopId, $request, $response)
    {
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $newShop = $repository->getActiveById($subshopId);
        $path = rtrim($newShop->getBasePath(), '/') . '/';
        $response->setCookie('shop', $subshopId, 0, $path);
        $url = sprintf('%s://%s%s%s',
            $request::SCHEME_HTTP,
            $newShop->getHost(),
            $newShop->getBaseUrl(),
            '/'
        );

        $response->setRedirect($url);
    }

    /**
     * Event listener function of the Enlight_Controller_Action_PostDispatch event.
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontend(Enlight_Event_EventArgs $arguments)
    {
        /**@var $controller Shopware_Controllers_Frontend_Index */
        $controller = $arguments->getSubject();

        /**
         * @var $request Zend_Controller_Request_Http
         */
        $request = $controller->Request();

        /**
         * @var $response Zend_Controller_Response_Http
         */
        $response = $controller->Response();

        /**
         * @var $view Enlight_View_Default
         */
        $view = $controller->View();

        //Check if there is a template and if an exception has occured
        if (!$request->isDispatched() || $response->isException() || !$view->hasTemplate() || $request->getModuleName(
            ) != "frontend"
        ) {
            return;
        }

        //Add our plugin template directory to load our slogan extension.
        $view->addTemplateDir($this->Path() . 'Views/');

        $view->extendsTemplate('frontend/plugins/swag_browser_language/index.tpl');

        $show_modal = (int)$request->getParam('show_modal');
        if($show_modal)
        {
            $view->assign('show_modal', $request->getBasePath().$request->getPathInfo());
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

        return $this->Path(). 'Controllers/Frontend/SwagBrowserLanguage.php';
    }
}