<?php

namespace Shopware\SwagBrowserLanguage\Components;

use Enlight_Controller_Request_Request;

class BotDetector
{
    /**
     * This method checks if the UserAgent is a known Bot.
     * If the UserAgent is a Bot, the method returns true .... else false
     *
     * @param Enlight_Controller_Request_Request $request
     * @return bool
     */
    public function checkForBot(Enlight_Controller_Request_Request $request)
    {
        $userAgentName = $request->getServer('HTTP_USER_AGENT');
        foreach($this->getBotArray() as $bot) {
            if(strpos(strtolower($userAgentName), strtolower($bot)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the known bots as list from the Shopware config
     *
     * @return array
     */
    private function getBotArray()
    {
        return explode(';', Shopware()->Config()->get('botBlackList'));
    }
}