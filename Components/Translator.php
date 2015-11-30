<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Shopware\SwagBrowserLanguage\Components;

use Enlight_Components_Snippet_Namespace;
use Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap;

class Translator
{
    /**
     * @var Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap
     */
    private $pluginBootstrap;

    /**
     * Returns doctrine instance
     *
     * @var \Shopware\Components\Model\ModelManager
     */
    private $models;

    /**
     * Returns the instance of the snippet manager
     *
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippets;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db = null;

    /**
     * The constructor for this class.
     * @param Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $bootstrap
     * @param \Shopware\Components\Model\ModelManager $models
     * @param \Shopware_Components_Snippet_Manager $snippets
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $db
     */
    public function __construct($bootstrap, $models, $snippets, $db)
    {
        $this->pluginBootstrap = $bootstrap;
        $this->models = $models;
        $this->snippets = $snippets;
        $this->db = $db;
    }

    /**
     * This function returns the snippets for a specific locale.
     * For example for the locale "en_GB" or "en"
     * @param string $locale
     * @return array
     */
    public function getSnippets($locale)
    {
        $result = array();

        $localeId = $this->getLocaleId($locale[0]);

        $snippetNamespace = $this->getSnippetNamespace($localeId);

        if ($snippetNamespace->count() == 0) {
            $snippetNamespace = $this->getSnippetNamespace($this->getLocaleId("en_GB"));
        }

        $result["choose"] = $snippetNamespace->get("modal/choose");
        $result["close"] = $snippetNamespace->get("modal/close");
        $result["go"] = $snippetNamespace->get("modal/go");
        $result["title"] = $snippetNamespace->get("modal/main_title");
        $result["recommendation"] = $snippetNamespace->get("modal/recommendation");
        $result["text"] = $snippetNamespace->get("modal/text");

        return $result;
    }

    /**
     * A helper function that returns a snippet namespace by a specific locale id
     *
     * @param $localeId
     * @return Enlight_Components_Snippet_Namespace
     */
    private function getSnippetNamespace($localeId)
    {
        /** @var \Shopware\Models\Shop\Locale $locale */
        $locale = $this->models->getRepository('Shopware\Models\Shop\Locale')->find($localeId);

        return $this->snippets->setLocale($locale)->getNamespace('frontend/swag_browser_language/main');
    }

    /**
     * A helper function that returns the localeId of a locale string
     * @param $locale
     * @return int|null|string
     */
    private function getLocaleId($locale)
    {
        $localeId = null;

        switch (strlen($locale)) {
            case 2: //sometimes only en, de, or es will be transmitted
                //Select the first matching language from the database
                $localeId = $this->db->fetchOne("SELECT `id` FROM `s_core_locales` WHERE `locale` LIKE :locale ORDER BY `id` ASC",
                    array('locale' => $locale . '%'));
                break;
            case 5: // the standard format e.g en_GB
                $localeId = $this->db->fetchOne("SELECT `id` FROM `s_core_locales` WHERE `locale`=:locale",
                    array('locale' => $locale));
                break;
        }

        if (is_null($localeId)) {
            $fallbackLocaleCode = $this->pluginBootstrap->get("config")->fallbackLanguage;
            $localeId = $this->getLocaleId($fallbackLocaleCode);
        }

        //returns 2 (en_GB) if 1.) the browser language was not found and 2.) the fallback language was not found
        return is_null($localeId) ? 2 : $localeId;
    }
}
