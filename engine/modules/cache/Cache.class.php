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
 * Модуль кеширования с использованием Redis
 *
 * Пример использования:
 * <pre>
 *    // Получает пользователя по его логину
 *    public function GetUserByLogin($sLogin) {
 *        // Пытаемся получить значение из кеша
 *        if (false === ($oUser = $this->Cache_Get("user_login_{$sLogin}"))) {
 *            // Если значение из кеша получить не удалось, то обращаемся к базе данных
 *            $oUser = $this->oMapper->GetUserByLogin($sLogin);
 *            // Записываем значение в кеш
 *            $this->Cache_Set($oUser, "user_login_{$sLogin}", array(), 60*60*24*5);
 *        }
 *        return $oUser;
 *    }
 *
 *    // Обновляет пользовател в БД
 *    public function UpdateUser($oUser) {
 *        // Удаляем кеш конкретного пользователя
 *        $this->Cache_Delete("user_login_{$oUser->getLogin()}");
 *        // Удалем кеш со списком всех пользователей
 *        $this->Cache_Clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG,array('user_update'));
 *        // Обновлем пользовател в базе данных
 *        return $this->oMapper->UpdateUser($oUser);
 *    }
 *
 *    // Получает список всех пользователей
 *    public function GetUsers() {
 *        // Пытаемся получить значение из кеша
 *        if (false === ($aUserList = $this->Cache_Get("users"))) {
 *            // Если значение из кеша получить не удалось, то обращаемся к базе данных
 *            $aUserList = $this->oMapper->GetUsers();
 *            // Записываем значение в кеш
 *            $this->Cache_Set($aUserList, "users", array('user_update'), 60*60*24*5);
 *        }
 *        return $aUserList;
 *    }
 * </pre>
 *
 * @package engine.modules
 * @since 1.0
 */
class ModuleCache extends Module
{
    /**
     * Объект бэкенда кеширования
     *
     * @var Zend_Cache_Backend
     */
    protected $oBackendCache = null;
    /**
     * Используется кеширование или нет
     *
     * @var bool
     */
    protected $bUseCache;
    /**
     * Хранилище для кеша на время сессии
     * @see SetLife
     * @see GetLife
     *
     * @var array
     */
    protected $aStoreLife = [];

    /**
     * Инициализируем нужный тип кеша
     *
     */
    public function Init()
    {
        $this->bUseCache = Config::Get('sys.cache.use');
        if (!$this->bUseCache) {
            return false;
        }
        $this->oBackendCache = Zend_Cache::factory(
            new Zend_Cache_Core([
                'lifetime' => Config::Get('sys.cache.lifetime'),
                'automatic_serialization' => Config::Get('sys.cache.automatic_serialization')
            ]),
            new Extended_Cache_Backend_Redis([
                'servers' => Config::Get('sys.cache.servers'),
                'key_prefix' => Config::Get('sys.cache.prefix')
            ])
        );
        return true;
    }

    /**
     * Получить значение из кеша
     *
     * @param string $sName Имя ключа
     * @return mixed|bool
     */
    public function Get($sName)
    {
        if (!$this->bUseCache) {
            return false;
        }
        if (!is_array($sName)) {
            return $this->oBackendCache->load(md5($sName));
        } else {
            return $this->multiGet($sName);
        }
    }

    /**
     * Поддержка мульти-запросов к кешу
     *
     * @param  array $aName Имя ключа
     * @return bool|array
     */
    public function multiGet($aName)
    {
        if (count($aName) == 0) {
            return false;
        }
        $aData = array();
        foreach ($aName as $key => $sName) {
            if ((false !== ($data = $this->Get($sName)))) {
                $aData[$sName] = $data;
            }
        }
        if (count($aData) > 0) {
            return $aData;
        }
        return false;
    }

    /**
     * Записать значение в кеш
     *
     * @param  mixed $data Данные для хранения в кеше
     * @param  string $sName Имя ключа
     * @param  array $aTags Список тегов, для возможности удалять сразу несколько кешей по тегу
     * @param  int|bool $iTimeLife Время жизни кеша в секундах
     * @return bool
     */
    public function Set($data, $sName, $aTags = array(), $iTimeLife = false)
    {
        if (!$this->bUseCache) {
            return false;
        }
        return $this->oBackendCache->save($data, md5($sName), $aTags, $iTimeLife);
    }

    /**
     * Удаляет значение из кеша по ключу(имени)
     *
     * @param string $sName Имя ключа
     * @return bool
     */
    public function Delete($sName)
    {
        if (!$this->bUseCache) {
            return false;
        }
        return $this->oBackendCache->remove(md5($sName));
    }

    /**
     * Чистит кеши
     *
     * @param int|string $cMode Режим очистки кеша
     * @param array $aTags Список тегов, актуально для режима Zend_Cache::CLEANING_MODE_MATCHING_TAG
     * @return bool
     */
    public function Clean($cMode = Zend_Cache::CLEANING_MODE_ALL, $aTags = [])
    {
        if (!$this->bUseCache) {
            return false;
        }
        return $this->oBackendCache->clean($cMode, $aTags);
    }

    /**
     * Сохраняет значение в кеше на время исполнения скрипта(сессии), некий аналог Registry
     *
     * @param mixed $data Данные для сохранения в кеше
     * @param string $sName Имя ключа
     */
    public function SetLife($data, $sName)
    {
        $this->aStoreLife[$sName] = $data;
    }

    /**
     * Получает значение из текущего кеша сессии
     *
     * @param string $sName Имя ключа
     * @return mixed
     */
    public function GetLife($sName)
    {
        if (key_exists($sName, $this->aStoreLife)) {
            return $this->aStoreLife[$sName];
        }
        return false;
    }
}