<?php

namespace Shopware\SwagBrowserLanguage\Subscriber;

use Enlight\Event\SubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;

class Javascript implements SubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return array(
            'Theme_Compiler_Collect_Plugin_Javascript' => 'addJsFiles'
		);
	}

	/**
	 * Provide the needed javascript files
	 *
	 * @return Doctrine\Common\Collections\ArrayCollection
	 */
	public function addJsFiles()
	{
		$jsPath = array(
            dirname(__DIR__) . '/Views/responsive/frontend/_public/src/js/jquery.modal.js'
		);

		return new ArrayCollection($jsPath);
	}
}