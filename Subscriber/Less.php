<?php

namespace Shopware\SwagBrowserLanguage\Subscriber;

use Enlight\Event\SubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;

class Less implements SubscriberInterface
{
	protected $bootstrap;

	public function __construct(\Shopware_Plugins_Frontend_SwagBrowserLanguage_Bootstrap $bootstrap)
	{
		$this->bootstrap = $bootstrap;
	}

	public static function getSubscribedEvents()
	{
		return array(
            'Theme_Compiler_Collect_Plugin_Less' => 'addLessFiles'
		);
	}

	/**
	 * Provide the needed less files
	 *
	 * @param \Enlight_Event_EventArgs $args
	 * @return Doctrine\Common\Collections\ArrayCollection
	 */
	public function addLessFiles(\Enlight_Event_EventArgs $args)
	{
		$less = new \Shopware\Components\Theme\LessDefinition(
		//configuration
			array(),

			//less files to compile
			array(
                dirname(__DIR__) . '/Views/responsive/frontend/_public/src/less/all.less'
			),

			//import directory
			dirname(__DIR__)
		);

		return new ArrayCollection(array($less));
	}
}