<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Shopware\SwagBrowserLanguage\Components;

use Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap;

class ShopFinder
{
    /**
     * @var Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap
     */
    private $pluginBootstrap;

    /**
     * @var array $subShops
     */
    private $subShops;

    /**
     * @var bool $isBackend
     */
    private $isBackend;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $models;

    /**
     * the constructor of this class
     *
     * @param Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap
     * @param \Shopware\Components\Model\ModelManager $models
     * @param bool $isBackend
     */
    public function __construct($pluginBootstrap, $models, $isBackend = false)
    {
        $this->pluginBootstrap = $pluginBootstrap;
        $this->isBackend = $isBackend;
        $this->models = $models;
        $this->subShops = $this->getLanguageShops();
    }

    /**
     * Helper function to get the SubshopId of the Shop in the prefered language
     *
     * @param $languages
     * @return mixed
     */
    public function getSubshopId($languages)
    {
        $assignedShops = $this->pluginBootstrap->get("config")->assignedShops;
        $assignedShops = array_values($assignedShops);

        if (!$assignedShops) {
            return $this->getDefaultShopId();
        }

        $subShopId = $this->getSubShopIdByFullBrowserLanguage($languages, $assignedShops);

        if (!$subShopId) {
            $subShopId = $this->getSubShopIdByBrowserLanguagePrefix($languages, $assignedShops);
        }
        if (!$subShopId) {
            $subShopId = $this->getDefaultShopId();
        }

        if (!in_array($subShopId, $assignedShops)) {
            return $this->getDefaultShopId();
        }

        return $subShopId;
    }

    /**
     * @return array
     */
    public function getSubShops()
    {
        return $this->subShops;
    }

    /**
     * @return mixed
     */
    public function getFirstSubshopId()
    {
        return $this->subShops[0]['id'];
    }

    /**
     * @param $subshopId
     * @return \Shopware\Models\Shop\Shop
     */
    public function getShopRepository($subshopId)
    {
        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = $this->models->getRepository('Shopware\Models\Shop\Shop');
        return $repository->getActiveById($subshopId);
    }

    /**
     * Helper method that creates an array of shop information that may be used in the modal box.
     * @param $subShopIds
     * @return array
     */
    public function getShopsForModal($subShopIds)
    {
        $resultArray = array();
        foreach ($subShopIds as $subShopId) {
            $model = $this->getShopRepository($subShopId);
            $resultArray[$subShopId] = $model->getName();
        }

        return $resultArray;
    }

    /**
     * HelperMethod for getSubShopId... get the default ShopId
     *
     * @return int
     */
    private function getDefaultShopId()
    {
        $default = $this->pluginBootstrap->get("config")->default;
        if (!is_int($default)) {
            $default = $this->getFirstSubshopId();
        }

        return $default;
    }

    /**
     * HelperMethod for getSubShopId... try to get the LanguageShop by the full BrowserLanguage like [de-DE] or [de-CH]
     *
     * @param $languages
     * @param $assignedShops
     * @return bool
     */
    private function getSubShopIdByFullBrowserLanguage($languages, $assignedShops)
    {
        foreach ($languages as $language) {
            $browserLanguage = strtolower($language);

            foreach ($this->subShops as $subshop) {
                $shopLocale = strtolower($subshop['locale']);

                if ($browserLanguage === $shopLocale && in_array($subshop['id'], $assignedShops)) {
                    return ($subshop['id']);
                }
            }
        }
        return false;
    }

    /**
     * HelperMethod for getSubShopId... try to get the LanguageShop by the BrowserLanguage "prefix" like [de] or [en]
     *
     * @param $languages
     * @param $assignedShops
     * @return bool
     */
    private function getSubShopIdByBrowserLanguagePrefix($languages, $assignedShops)
    {
        foreach ($languages as $language) {
            $browserLanguage = strtolower($language);
            $currentLanguageArray = explode('-', $browserLanguage);
            $browserLanguagePrefix = $currentLanguageArray[0];
            
            foreach ($this->subShops as $subshop) {
                $subshopLanguage = $subshop['language'];

                if ($browserLanguagePrefix === $subshopLanguage && in_array($subshop['id'], $assignedShops)) {
                    return ($subshop['id']);
                }
            }
        }
        return false;
    }

    /**
     * Helper function to get the needed data of all active language shops (optional: of a main shop)
     *
     * @return array
     */
    private function getLanguageShops()
    {
        $data = $this->getData();
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
     * Helper function that queries all sub shops (including language sub shops).
     * @return array
     */
    private function getData()
    {
        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = $this->models->getRepository('Shopware\Models\Shop\Shop');
        $builder = $repository->getActiveQueryBuilder();
        $builder->orderBy('shop.id');

        return $builder->getQuery()->getArrayResult();
    }
}
