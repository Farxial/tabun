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
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
chdir(dirname(__FILE__));

require_once("./settings/loader.php");
require_once 'vendor/autoload.php';
require_once(Config::Get('path.root.engine') . "/classes/Engine.class.php");

$lang = Config::Get('locale.lang');
putenv("LANG=" . $lang);
setlocale(LC_ALL, $lang);
date_default_timezone_set(Config::Get('locale.timezone'));

$oRouter = Router::getInstance();
$oRouter->Exec();
