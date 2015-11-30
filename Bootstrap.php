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

use Shopware\SwagBrowserLanguage\Components\ShopFinder;
use Shopware\SwagBrowserLanguage\Subscriber;

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
     * Returns an array with some information about the plugin.
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
        return array('success' => true, 'invalidateCache' => array('theme', 'template'));
    }

    /**
     * Method to always register the custom models and the namespace for the auto-loading
     */
    public function afterInit()
    {
        $this->Application()->Loader()->registerNamespace('Shopware\SwagBrowserLanguage', $this->Path());
        $this->Application()->Loader()->registerNamespace('Shopware\SwagBrowserLanguage\Components', $this->Path() . 'Components/');
    }

    /**
     * Creates the configuration fields.
     * Selects first a row of the s_articles_attributes to get all possible article attributes.
     */
    private function createConfiguration()
    {
        $shopFinder = new ShopFinder($this, $this->get("Models"));
        $subShops = $shopFinder->getSubShops();

        $store = array();
        foreach ($subShops as $subShop) {
            $store[] = array($subShop['id'], $subShop['name']);
        }

        $form = $this->Form();
        $form->setElement('select', 'default', array(
            'label' => 'Fallback-Sprachshop',
            'store' => 'base.ShopLanguage',
            'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
            'required' => true,
            'value' => null,
            'description' => 'Auf diesen Shop wird weitergeleitet, wenn kein zu den Browsersprachen passender Shop existiert.'
        ));

        $form->setElement('select', 'assignedShops', array(
            'label' => 'Zugehörige Shops',
            'store' => 'base.ShopLanguage',
            'scope' => Shopware\Models\Config\Element::SCOPE_SHOP,
            'required' => false,
            'value' => null,
            'description' => 'Auf diese Shops wird weitergeleitet, wenn die Browsersprache der Shopsprache entspricht.',
            'multiSelect' => true
        ));

        $form->setElement('text', 'fallbackLanguage', array(
            'label' => 'Fallback-Sprache für Modal',
            'value' => 'en_GB',
            'required' => true,
            'description' => 'Dies ist die locale für die Übersetzung, auf die, wenn keine passende Übersetzung für die vom Benutzer gewählte Sprache existiert, zurückgegriffen wird, um die Infobox im Frontend zu übersetzen.'
        ));

        $this->translateForm();
    }

    private function translateForm()
    {
        $translations = array(
            'en_GB' => array(
                'default' => array(
                    'label' => 'Fallback shop',
                    'description' => 'Forward to this shop if not found any shop languages matching browser language'
                ),
                'assignedShops' => array(
                    'label' => 'Related shops',
                    'description' => 'Forwards to these shops, if the browser language equals the shop language.',
                ),
                'fallbackLanguage' => array(
                    'label' => 'Fallback language for the modal',
                    'description' => 'This is the locale for the translation that will be used by default, if no matching translation was found for the user to be displayed in the infobox in the frontend.'
                )
            )
        );

        // In 4.2.2 we introduced a helper function for this, so we can skip the custom logic
        if ($this->assertMinimumVersion('4.2.2')) {
            $this->addFormTranslations($translations);
            return true;
        }

        // Translations
        $form = $this->Form();
        $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');

        //iterate the languages
        foreach ($translations as $locale => $snippets) {
            $localeModel = $shopRepository->findOneBy(array(
                'locale' => $locale
            ));

            //not found? continue with next language
            if ($localeModel === null) {
                continue;
            }

            if ($snippets['plugin_form']) {
                // Translation for form description
                foreach ($form->getTranslations() as $translation) {
                    if ($translation->getLocale()->getLocale() == $locale) {
                        $formTranslation = $translation;
                    }
                }
                // If none found create a new one
                if (!$formTranslation) {
                    $formTranslation = new \Shopware\Models\Config\FormTranslation();
                    $formTranslation->setLocale($localeModel);
                    //add the translation to the form
                    $form->addTranslation($formTranslation);
                }

                if ($snippets['plugin_form']['label']) {
                    $formTranslation->setLabel($snippets['plugin_form']['label']);
                }
                if ($snippets['plugin_form']['description']) {
                    $formTranslation->setDescription($snippets['plugin_form']['description']);
                }

                unset($snippets['plugin_form']);
            }

            //iterate all snippets of the current language
            foreach ($snippets as $element => $snippet) {
                $translationModel = null;

                //get the form element by name
                $elementModel = $form->getElement($element);

                //not found? continue with next snippet
                if ($elementModel === null) {
                    continue;
                }

                // Try to load existing translation
                foreach ($elementModel->getTranslations() as $translation) {
                    if ($translation->getLocale()->getLocale() == $locale) {
                        $translationModel = $translation;
                        break;
                    }
                }

                // If none found create a new one
                if (!$translationModel) {
                    $translationModel = new \Shopware\Models\Config\ElementTranslation();
                    $translationModel->setLocale($localeModel);
                    //add the translation to the form element
                    $elementModel->addTranslation($translationModel);
                }

                if ($snippet['label']) {
                    $translationModel->setLabel($snippet['label']);
                }
                if ($snippet['description']) {
                    $translationModel->setDescription($snippet['description']);
                }
            }
        }
    }

    /**
     * Registers the plugin controller event for the backend controller SwagUnlockMerchants
     */
    public function registerEvents()
    {
        $this->subscribeEvent('Enlight_Controller_Front_StartDispatch', 'onStartDispatch');

        return true;
    }

    /**
     * Main entry point for the bonus system: Registers various subscribers to hook into shopware
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $subscribers = array(
            new Subscriber\Frontend($this)
        );

        if ($this->assertMinimumVersion('5.0.0')) {
            $subscribers[] = new Subscriber\Javascript();
        }

        foreach ($subscribers as $subscriber) {
            $this->get('events')->addSubscriber($subscriber);
        }
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
}