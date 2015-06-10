<?php

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
     * the constructor of this class
     *
     * @param Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap
     * @param bool $isBackend
     */
    public function __construct(Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $pluginBootstrap, $isBackend = false)
    {
        $this->pluginBootstrap = $pluginBootstrap;
        $this->isBackend = $isBackend;
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
        $subShopId = $this->getSubShopIdByFullBrowserLanguage($languages);
        if (!$subShopId) {
            $subShopId = $this->getSubShopIdByBrowserLanguagePrefix($languages);
        }
        if (!$subShopId) {
            $subShopId = $this->getDefaultShopId();
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
    public function getfirtstSubshop()
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
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        return $repository->getActiveById($subshopId);
    }

    /**
     * HelperMethod for getSubShopId... get the default ShopId
     *
     * @return int
     */
    private function getDefaultShopId() {
        $default = $this->pluginBootstrap->Config()->get('default');
        if (!is_int($default)) {
            $default = $this->getfirtstSubshop();
        }
        return ($default);
    }

    /**
     * HelperMethod for getSubShopId... try to get the LanguageShop by the full BrowserLanguage like [de-DE] or [de-CH]
     *
     * @param $languages
     * @return bool
     */
    private function getSubShopIdByFullBrowserLanguage($languages) {
        foreach ($languages as $language) {
            foreach ($this->subShops as $subshop) {
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
     * HelperMethod for getSubShopId... try to get the LanguageShop by the BrowserLanguage "prefix" like [de] or [en]
     *
     * @param $languages
     * @return bool
     */
    private function getSubShopIdByBrowserLanguagePrefix($languages) {
        foreach ($languages as $language) {
            foreach ($this->subShops as $subshop) {
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

    private function getData()
    {
        $mainShopId = $this->getMainShop();
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

        return $builder->getQuery()->getArrayResult();
    }

    private function getMainShop()
    {
        $sql = "SELECT id FROM s_core_shops WHERE main_id = NULL";
        return Shopware()->Container()->get('db')->fetchOne($sql);
    }
}