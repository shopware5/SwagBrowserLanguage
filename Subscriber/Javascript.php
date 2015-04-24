<?php

namespace Shopware\SwagBrowserLanguage\Subscriber;

use Enlight\Event\SubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;

class Javascript implements SubscriberInterface
{
	protected $bootstrap;

	public function __construct(\Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $bootstrap)
	{
		$this->bootstrap = $bootstrap;
	}

	public static function getSubscribedEvents()
	{
		return array(
            'Theme_Compiler_Collect_Plugin_Javascript' => 'addJsFiles'
		);
	}

	/**
	 * Provide the needed javascript files
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return Doctrine\Common\Collections\ArrayCollection
	 */
	public function addJsFiles(\Enlight_Event_EventArgs $args)
	{
		$jsPath = array(
            dirname(__DIR__) . '/Views/responsive/frontend/_public/src/js/jquery.modal.js'
		);

		return new ArrayCollection($jsPath);
	}
}