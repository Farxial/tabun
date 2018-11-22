<?php

class HookServices extends Hook
{
    public function RegisterHook()
    {
        $this->AddHook('template_quotes', 'Quotes', __CLASS__, -100);
        $this->AddHook('template_donations', 'Donations', __CLASS__, -100);
        $this->AddHook('template_banners', 'Banners', __CLASS__, -100);
    }

    public function Quotes()
    {
        $sNotFound = "¯\\_(ツ)_/¯";

        $aDecoded = $this->getJSON("twicher");

        if (array_key_exists('text', $aDecoded)) {
            return $aDecoded['text'];
        } else {
            return $sNotFound;
        }

    }

    private function getJSON($sServiceName)
    {
        $ch = curl_init();

        $sServiceURL = Config::Get('misc.services')[$sServiceName];

        // Url
        curl_setopt($ch, CURLOPT_URL, $sServiceURL);
        // 1s timeout time for cURL connection (assume that connection is fast enough)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        // Return data to variable
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return false;
        }

        return json_decode($data, true);
    }

    public function Donations()
    {
        $aDecoded = $this->getJSON("donations");

        $callback = function ($sUser) {
            if ($oUser = $this->User_GetUserByLogin($sUser)) {
                return [
                    'login' => $oUser->getLogin(),
                    'url' => $oUser->getUserWebPath(),
                    'avatar_url' => $oUser->getProfileAvatarPath(24),
                ];
            } else {
                return [
                    'login' => htmlspecialchars($sUser),
                    'url' => "#",
                    'avatar_url' => false
                ];
            }

        };
        $aDonaters = array_map($callback, $aDecoded);

        $this->Viewer_Assign('aDonaters', $aDonaters);

        return $this->Viewer_Fetch('donation_list.tpl');
    }

    public function Banners()
    {
        $aBanners = $this->getJSON("banners");

        $this->Viewer_Assign('aBanners', $aBanners);

        return $this->Viewer_Fetch('banners_list.tpl');
    }
}
