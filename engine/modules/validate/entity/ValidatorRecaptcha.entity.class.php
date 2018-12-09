<?php
/*-------------------------------------------------------
*
*   LiveStreet Engine Social Networking
*   Copyright © 2008 Mzhelskiy Maxim
*
*--------------------------------------------------------
*
*   Official site: www.livestreet.ru
*   Contact e-mail: rus.engine@gmail.com
*
*   GNU General Public License, version 2:
*   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
---------------------------------------------------------
*/

/**
 * Валидатор гугл-рекаптчи
 *
 * @package engine.modules.validate
 * @since 1.0
 */
class ModuleValidate_EntityValidatorRecaptcha extends ModuleValidate_EntityValidator
{
    /**
     * Запуск валидации
     *
     * @param mixed $sValue Данные для валидации
     *
     * @return bool|string
     */
    public function validate($sValue)
    {
        $secret = Config::Get('recaptcha.secret');
        $url = Config::Get('recaptcha.url');
        $q = "?secret=" . $secret . "&response=" . $sValue;
        $result = gettext('recaptcha_error');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . $q);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (!curl_errno($ch)) {
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);


            $json = json_decode($response, true);

            if ($httpcode == 200) {
                if ($json['success'] == true) {
                    $result = true;
                }
            }
        }
        return $result;
    }
}
