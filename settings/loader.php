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
 * Operations with Config object
 */
require_once(dirname(dirname(__FILE__)) . "/engine/lib/internal/ConfigSimple/Config.class.php");
Config::LoadFromFile(dirname(__FILE__) . '/application.php');

/**
 * Инклудим все *.php файлы из каталога {path.root.engine}/include/ - это файлы ядра
 */
$sDirInclude = Config::get('path.root.engine') . '/include/';
if ($hDirInclude = opendir($sDirInclude)) {
    while (false !== ($sFileInclude = readdir($hDirInclude))) {
        $sFileIncludePathFull = $sDirInclude . $sFileInclude;
        if ($sFileInclude != '.' and $sFileInclude != '..' and is_file($sFileIncludePathFull)) {
            $aPathInfo = pathinfo($sFileIncludePathFull);
            if (isset($aPathInfo['extension']) and strtolower($aPathInfo['extension']) == 'php') {
                require_once($sDirInclude . $sFileInclude);
            }
        }
    }
    closedir($hDirInclude);
}

/**
 * Подгружаем файл конфигурации окружения
 */

$local_config = @$_SERVER["CONFIG"];
if (file_exists($local_config)) {
    Config::LoadFromFile($local_config, false);
}

