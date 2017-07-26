<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

use Shopware\Components\CSRFWhitelistAware;
use SwagBrowserLanguage\Components\ShopFinder;
use SwagBrowserLanguage\Components\Translator;

class Shopware_Controllers_Widgets_SwagBrowserLanguage extends Enlight_Controller_Action implements CSRFWhitelistAware
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

    /** @var array */
    private $config = null;
    
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'redirect',
        ];
    }
    
    /**
     * This function will be called before the widget is being finalized
     */
    public function preDispatch()
    {
        $this->shopFinder = $this->get('swag_browser_language.components.shop_finder');
        $this->translator = $this->get('swag_browser_language.components.translator');
        $this->session = $this->get("session");
        $this->shop = $this->get("shop");

        $this->View()->addTemplateDir($this->container->getParameter('swag_browser_language.view_dir'));

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
            echo json_encode(array(
                'success' => false
            ));
            return;
        }

        $languages = $this->getBrowserLanguages($request);

        $currentLocale = $this->shop->getLocale()->getLocale();
        $currentLanguage = explode('_', $currentLocale);

        //Does this shop have the browser language already?
        if (in_array($currentLocale, $languages) || in_array($currentLanguage[0], $languages)) {
            echo json_encode(array(
                'success' => false
            ));
            return;
        }

        if (!$this->allowRedirect($this->Request()->getPost())) {
            echo json_encode(array(
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

        echo json_encode(array(
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

        if ($this->getPluginConfig()['forceBrowserMainLocale'] && count($languages) > 2) {
            $languages = [
                $languages[0],
                $languages[1],
            ];
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
     * @return array|mixed
     */
    private function getPluginConfig()
    {
        if ($this->config === null) {
            $this->config = $this->get('shopware.plugin.cached_config_reader')->getByPluginName('SwagBrowserLanguage');
        }

        return $this->config;
    }

    /**
     * This action displays the content of the modal box
     */
    public function getModalAction()
    {
        $this->get('Front')->Plugins()->ViewRenderer()->setNoRender();
        $request = $this->Request();
        $languages = $this->getBrowserLanguages($request);
        $subShopId = $this->shopFinder->getSubshopId($languages);

        $assignedShops = $this->getPluginConfig()["assignedShops"];
        $shopsToDisplay = $this->shopFinder->getShopsForModal($assignedShops);

        $snippets = $this->translator->getSnippets($languages);

        $this->View()->assign("snippets", $snippets);
        $this->View()->assign("shops", $shopsToDisplay);
        $this->View()->assign("destinationShop", $this->shopFinder->getShopRepository($subShopId)->getName());
        $this->View()->assign("destinationId", $subShopId);

        echo json_encode([
            'title' => $snippets['title'],
            'content' => $this->View()->fetch('widgets/swag_browser_language/get_modal.tpl')
        ]);
    }
}
