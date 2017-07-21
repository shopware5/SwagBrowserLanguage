<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBrowserLanguage\Components;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;

/**
 * Class ShopFinder
 * @package SwagBrowserLanguage\Components
 */
class ShopFinder
{
    /**
     * @var array $subShops
     */
    private $subShops;

    /**
     * @var ModelManager
     */
    private $models;

    /**
     * @var array
     */
    private $pluginConfig = [];

    /**
     * the constructor of this class
     *
     * @param ModelManager $models
     * @param CachedConfigReader $configReader
     */
    public function __construct(ModelManager $models, CachedConfigReader $configReader)
    {
        $this->models = $models;
        $this->subShops = $this->getLanguageShops();
        $this->pluginConfig = $configReader->getByPluginName('SwagBrowserLanguage');
    }

    /**
     * Helper function to get the SubshopId of the Shop in the prefered language
     *
     * @param $languages
     * @return mixed
     */
    public function getSubshopId($languages)
    {
        $assignedShops = array_values($this->pluginConfig['assignedShops']);

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
     * @return Shop
     */
    public function getShopRepository($subshopId)
    {
        /** @var Repository $repository */
        $repository = $this->models->getRepository(Shop::class);
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
        $default = $this->pluginConfig['default'];
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
        $subshops = [];

        foreach ($data as $subshop) {
            $subshop['locale'] = strtolower($subshop['locale']['locale']);
            $subshop['locale'] = str_replace('_', '-', $subshop['locale']);

            $subshop['language'] = explode('-', $subshop['locale']);
            $subshop['language'] = $subshop['language'][0];

            $subshops[] = [
                'id' => $subshop['id'],
                'name' => $subshop['name'],
                'locale' => $subshop['locale'],
                'language' => $subshop['language']
            ];
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
        $repository = $this->models->getRepository(Shop::class);
        $builder = $repository->getActiveQueryBuilder();
        $builder->orderBy('shop.id');

        return $builder->getQuery()->getArrayResult();
    }
}
