<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace SwagBrowserLanguage;

use Shopware\Components\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class SwagBrowserLanguage
 * @package SwagBrowserLanguage
 */
class SwagBrowserLanguage extends Plugin
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('swag_browser_language.plugin_dir', $this->getPath());
        parent::build($container);
    }
}