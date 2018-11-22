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
 * Модуль для работы с избранным
 *
 * @package modules.favourite
 * @since 1.0
 */
class ModuleFavourite extends Module
{
    /**
     * Объект маппера
     *
     * @var ModuleFavourite_MapperFavourite
     */
    protected $oMapper;

    /**
     * Инициализация
     *
     */
    public function Init()
    {
        $this->oMapper = Engine::GetMapper(__CLASS__);
    }

    /**
     * Получает информацию о том, найден ли таргет в избранном или нет
     *
     * @param  int $sTargetId ID владельца
     * @param  string $sTargetType Тип владельца
     * @param  int $sUserId ID пользователя
     * @return ModuleFavourite_EntityFavourite|null
     */
    public function GetFavourite($sTargetId, $sTargetType, $sUserId)
    {
        if (!is_numeric($sTargetId) or !is_string($sTargetType)) {
            return null;
        }
        $data = $this->GetFavouritesByArray($sTargetId, $sTargetType, $sUserId);
        return (isset($data[$sTargetId]))
            ? $data[$sTargetId]
            : null;
    }

    /**
     * Получить список избранного по списку айдишников
     *
     * @param  array $aTargetId Список ID владельцев
     * @param  string $sTargetType Тип владельца
     * @param  int $sUserId ID пользователя
     * @return array
     */
    public function GetFavouritesByArray($aTargetId, $sTargetType, $sUserId)
    {
        if (!$aTargetId) {
            return array();
        }
        if (!is_array($aTargetId)) {
            $aTargetId = array($aTargetId);
        }
        $aTargetId = array_unique($aTargetId);
        $aFavourite = array();
        $aIdNotNeedQuery = array();
        /**
         * Делаем мульти-запрос к кешу
         */
        $aCacheKeys = func_build_cache_keys($aTargetId, "favourite_{$sTargetType}_", '_' . $sUserId);
        if (false !== ($data = $this->Cache_Get($aCacheKeys))) {
            /**
             * проверяем что досталось из кеша
             */
            foreach ($aCacheKeys as $sValue => $sKey) {
                if (array_key_exists($sKey, $data)) {
                    if ($data[$sKey]) {
                        $aFavourite[$data[$sKey]->getTargetId()] = $data[$sKey];
                    } else {
                        $aIdNotNeedQuery[] = $sValue;
                    }
                }
            }
        }
        /**
         * Смотрим чего не было в кеше и делаем запрос в БД
         */
        $aIdNeedQuery = array_diff($aTargetId, array_keys($aFavourite));
        $aIdNeedQuery = array_diff($aIdNeedQuery, $aIdNotNeedQuery);
        $aIdNeedStore = $aIdNeedQuery;
        if ($data = $this->oMapper->GetFavouritesByArray($aIdNeedQuery, $sTargetType, $sUserId)) {
            foreach ($data as $oFavourite) {
                /**
                 * Добавляем к результату и сохраняем в кеш
                 */
                $aFavourite[$oFavourite->getTargetId()] = $oFavourite;
                $this->Cache_Set(
                    $oFavourite,
                    "favourite_{$oFavourite->getTargetType()}_{$oFavourite->getTargetId()}_{$sUserId}",
                    []
                );
                $aIdNeedStore = array_diff($aIdNeedStore, array($oFavourite->getTargetId()));
            }
        }
        /**
         * Сохраняем в кеш запросы не вернувшие результата
         */
        foreach ($aIdNeedStore as $sId) {
            $this->Cache_Set(
                null,
                "favourite_{$sTargetType}_{$sId}_{$sUserId}",
                []
            );
        }
        /**
         * Сортируем результат согласно входящему массиву
         */
        $aFavourite = func_array_sort_by_keys($aFavourite, $aTargetId);
        return $aFavourite;
    }

    /**
     * Получает список таргетов из избранного
     *
     * @param  int $sUserId ID пользователя
     * @param  string $sTargetType Тип владельца
     * @param  int $iCurrPage Номер страницы
     * @param  int $iPerPage Количество элементов на страницу
     * @param  array $aExcludeTarget Список ID владельцев для исклчения
     * @return array
     */
    public function GetFavouritesByUserId($sUserId, $sTargetType, $iCurrPage, $iPerPage, $aExcludeTarget = array())
    {
        $s = serialize($aExcludeTarget);
        $sCacheKey = "{$sTargetType}_favourite_user_{$sUserId}_{$iCurrPage}_{$iPerPage}_{$s}";
        if (false === ($data = $this->Cache_Get($sCacheKey))) {
            $data = array(
                'collection' => $this->oMapper->GetFavouritesByUserId($sUserId, $sTargetType, $iCount, $iCurrPage, $iPerPage, $aExcludeTarget),
                'count' => $iCount
            );
            $this->Cache_Set(
                $data,
                $sCacheKey,
                [
                    "favourite_{$sTargetType}_change",
                    "favourite_{$sTargetType}_change_user_{$sUserId}"
                ]
            );
        }
        return $data;
    }

    /**
     * Возвращает число таргетов определенного типа в избранном по ID пользователя
     *
     * @param  int $sUserId ID пользователя
     * @param  string $sTargetType Тип владельца
     * @param  array $aExcludeTarget Список ID владельцев для исклчения
     * @return array
     */
    public function GetCountFavouritesByUserId($sUserId, $sTargetType, $aExcludeTarget = array())
    {
        $s = serialize($aExcludeTarget);
        $sCacheKey = "{$sTargetType}_count_favourite_user_{$sUserId}_{$s}";
        if (false === ($data = $this->Cache_Get($sCacheKey))) {
            $data = $this->oMapper->GetCountFavouritesByUserId($sUserId, $sTargetType, $aExcludeTarget);
            $this->Cache_Set(
                $data,
                $sCacheKey,
                [
                    "favourite_{$sTargetType}_change",
                    "favourite_{$sTargetType}_change_user_{$sUserId}"
                ]
            );
        }
        return $data;
    }

    /**
     * Получает список комментариев к записям открытых блогов
     * из избранного указанного пользователя
     *
     * @param  int $sUserId ID пользователя
     * @param  int $iCurrPage Номер страницы
     * @param  int $iPerPage Количество элементов на страницу
     * @return array
     */
    public function GetFavouriteOpenCommentsByUserId($sUserId, $iCurrPage, $iPerPage)
    {
        $sCacheKey = "comment_favourite_user_{$sUserId}_{$iCurrPage}_{$iPerPage}_open";
        if (false === ($data = $this->Cache_Get($sCacheKey))) {
            $data = array(
                'collection' => $this->oMapper->GetFavouriteOpenCommentsByUserId($sUserId, $iCount, $iCurrPage, $iPerPage),
                'count' => $iCount
            );
            $this->Cache_Set(
                $data,
                $sCacheKey,
                [
                    "favourite_comment_change",
                    "favourite_comment_change_user_{$sUserId}"
                ]
            );
        }
        return $data;
    }

    /**
     * Возвращает число комментариев к открытым блогам в избранном по ID пользователя
     *
     * @param  int $sUserId ID пользователя
     * @return array
     */
    public function GetCountFavouriteOpenCommentsByUserId($sUserId)
    {
        $sCacheKey = "comment_count_favourite_user_{$sUserId}_open";
        if (false === ($data = $this->Cache_Get($sCacheKey))) {
            $data = $this->oMapper->GetCountFavouriteOpenCommentsByUserId($sUserId);
            $this->Cache_Set(
                $data,
                $sCacheKey,
                [
                    "favourite_comment_change",
                    "favourite_comment_change_user_{$sUserId}"
                ]
            );
        }
        return $data;
    }

    /**
     * Получает список топиков из открытых блогов
     * из избранного указанного пользователя
     *
     * @param  int $sUserId ID пользователя
     * @param  int $iCurrPage Номер страницы
     * @param  int $iPerPage Количество элементов на страницу
     * @return array
     */
    public function GetFavouriteOpenTopicsByUserId($sUserId, $iCurrPage, $iPerPage)
    {
        $sCacheKey = "topic_favourite_user_{$sUserId}_{$iCurrPage}_{$iPerPage}_open";
        if (false === ($data = $this->Cache_Get($sCacheKey))) {
            $data = array(
                'collection' => $this->oMapper->GetFavouriteOpenTopicsByUserId($sUserId, $iCount, $iCurrPage, $iPerPage),
                'count' => $iCount
            );
            $this->Cache_Set(
                $data,
                "topic_favourite_user_{$sUserId}_{$iCurrPage}_{$iPerPage}_open",
                [
                    "favourite_topic_change",
                    "favourite_topic_change_user_{$sUserId}"
                ]
            );
        }
        return $data;
    }

    /**
     * Возвращает число топиков в открытых блогах из избранного по ID пользователя
     *
     * @param  string $sUserId ID пользователя
     * @return array
     */
    public function GetCountFavouriteOpenTopicsByUserId($sUserId)
    {
        $sCacheKey = "topic_count_favourite_user_{$sUserId}_open";
        if (false === ($data = $this->Cache_Get($sCacheKey))) {
            $data = $this->oMapper->GetCountFavouriteOpenTopicsByUserId($sUserId);
            $this->Cache_Set(
                $data,
                $sCacheKey,
                [
                    "favourite_topic_change",
                    "favourite_topic_change_user_{$sUserId}"
                ]
            );
        }
        return $data;
    }

    /**
     * Добавляет таргет в избранное
     *
     * @param  ModuleFavourite_EntityFavourite $oFavourite Объект избранного
     * @return bool
     */
    public function AddFavourite(ModuleFavourite_EntityFavourite $oFavourite)
    {
        //Проверка приватности
        $tType = $oFavourite->getTargetType();
        $oUser = $this->User_GetUserCurrent();

        if ($tType == 'topic') {
            $oTopic = $this->Topic_GetTopicById($oFavourite->getTargetId());
            $oBlog = $this->Blog_getBlogById($oTopic->getBlogId());
            if ($oBlog->getType() == 'invite' || $oBlog->getType() == 'close') {
                if (is_null($this->Blog_GetBlogUserByBlogIdAndUserId($oBlog->getId(), $oUser->getId())) && $oUser->getId() !== $oBlog->getOwnerId() && !$oUser->isAdministrator())
                    return false;
            }
        }
        if ($tType == 'comment') {
            $oComment = $this->Comment_GetCommentById($oFavourite->getTargetId());

            if ($oComment->getTargetType() == 'topic') {
                $oTopic = $this->Topic_GetTopicById($oComment->getTargetId());
                $oBlog = $this->Blog_getBlogById($oTopic->getBlogId());
                if ($oBlog->getType() == 'invite' || $oBlog->getType() == 'close') {
                    if (is_null($this->Blog_GetBlogUserByBlogIdAndUserId($oBlog->getId(), $oUser->getId())) && $oUser->getId() !== $oBlog->getOwnerId() && !$oUser->isAdministrator())
                        return false;
                }
            }
            if ($oComment->getTargetType() == 'talk') {
                $oTalk = $this->Talk_GetTalkById($oFavourite->getTargetId());
                if (is_null($this->Talk_GetTalkUser($oTalk->getId(), $oUser->getId())))
                    return false;
            }
        }
        if ($tType == 'talk') {
            $oTalk = $this->Talk_GetTalkById($oFavourite->getTargetId());
            if (is_null($this->Talk_GetTalkUser($oTalk->getId(), $oUser->getId())))
                return false;
        }

        if (!$oFavourite->getTags()) {
            $oFavourite->setTags('');
        }
        $this->SetFavouriteTags($oFavourite);
        //чистим зависимые кеши
        $this->Cache_Clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            [
                "favourite_{$oFavourite->getTargetType()}_change_user_{$oFavourite->getUserId()}"
            ]
        );
        $this->Cache_Delete("favourite_{$oFavourite->getTargetType()}_{$oFavourite->getTargetId()}_{$oFavourite->getUserId()}");
        return $this->oMapper->AddFavourite($oFavourite);
    }

    /**
     * Устанавливает список тегов для избранного
     *
     * @param ModuleFavourite_EntityFavourite $oFavourite Объект избранного
     * @param bool $bAddNew Добавлять новые теги или нет
     */
    public function SetFavouriteTags($oFavourite, $bAddNew = true)
    {
        /**
         * Удаляем все теги
         */
        $this->oMapper->DeleteTags($oFavourite);
        /**
         * Добавляем новые
         */
        if ($bAddNew and $oFavourite->getTags()) {
            /**
             * Добавляем теги объекта избранного, если есть
             */
            if ($aTags = $this->GetTagsTarget($oFavourite->getTargetType(), $oFavourite->getTargetId())) {
                foreach ($aTags as $sTag) {
                    $oTag = Engine::GetEntity('ModuleFavourite_EntityTag', $oFavourite->_getData());
                    $oTag->setText(htmlspecialchars($sTag));
                    $oTag->setIsUser(0);
                    $this->oMapper->AddTag($oTag);
                }
            }
            /**
             * Добавляем пользовательские теги
             */
            foreach ($oFavourite->getTagsArray() as $sTag) {
                $oTag = Engine::GetEntity('ModuleFavourite_EntityTag', $oFavourite->_getData());
                $oTag->setText($sTag); // htmlspecialchars уже используется при установке тегов
                $oTag->setIsUser(1);
                $this->oMapper->AddTag($oTag);
            }
        }
    }

    /**
     * Возвращает список тегов для объекта избранного
     *
     * @param string $sTargetType Тип владельца
     * @param int $iTargetId ID владельца
     * @return bool|array
     */
    public function GetTagsTarget($sTargetType, $iTargetId)
    {
        $sMethod = 'GetTagsTarget' . func_camelize($sTargetType);
        if (method_exists($this, $sMethod)) {
            return $this->$sMethod($iTargetId);
        }
        return false;
    }

    /**
     * Обновляет запись об избранном
     *
     * @param ModuleFavourite_EntityFavourite $oFavourite Объект избранного
     * @return bool
     */
    public function UpdateFavourite(ModuleFavourite_EntityFavourite $oFavourite)
    {
        if (!$oFavourite->getTags()) {
            $oFavourite->setTags('');
        }
        $this->SetFavouriteTags($oFavourite);
        $this->Cache_Clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            [
                "favourite_{$oFavourite->getTargetType()}_change_user_{$oFavourite->getUserId()}"
            ]
        );
        $this->Cache_Delete("favourite_{$oFavourite->getTargetType()}_{$oFavourite->getTargetId()}_{$oFavourite->getUserId()}");
        return $this->oMapper->UpdateFavourite($oFavourite);
    }

    /**
     * Удаляет таргет из избранного
     *
     * @param  ModuleFavourite_EntityFavourite $oFavourite Объект избранного
     * @return bool
     */
    public function DeleteFavourite(ModuleFavourite_EntityFavourite $oFavourite)
    {
        $this->SetFavouriteTags($oFavourite, false);
        //чистим зависимые кеши
        $this->Cache_Clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            [
                "favourite_{$oFavourite->getTargetType()}_change_user_{$oFavourite->getUserId()}"
            ]
        );
        $this->Cache_Delete("favourite_{$oFavourite->getTargetType()}_{$oFavourite->getTargetId()}_{$oFavourite->getUserId()}");
        return $this->oMapper->DeleteFavourite($oFavourite);
    }

    /**
     * Меняет параметры публикации у таргета
     *
     * @param  array|int $aTargetId Список ID владельцев
     * @param  string $sTargetType Тип владельца
     * @param  int $iPublish Флаг публикации
     * @return bool
     */
    public function SetFavouriteTargetPublish($aTargetId, $sTargetType, $iPublish)
    {
        if (!is_array($aTargetId)) $aTargetId = array($aTargetId);

        $this->Cache_Clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            [
                "favourite_{$sTargetType}_change"
            ]
        );
        return $this->oMapper->SetFavouriteTargetPublish($aTargetId, $sTargetType, $iPublish);
    }

    /**
     * Удаляет избранное по списку идентификаторов таргетов
     *
     * @param  array|int $aTargetId Список ID владельцев
     * @param  string $sTargetType Тип владельца
     * @return bool
     */
    public function DeleteFavouriteByTargetId($aTargetId, $sTargetType)
    {
        if (!is_array($aTargetId)) $aTargetId = array($aTargetId);
        /**
         * Чистим зависимые кеши
         */
        $this->Cache_Clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            [
                "favourite_{$sTargetType}_change"
            ]
        );
        $this->DeleteTagByTarget($aTargetId, $sTargetType);
        return $this->oMapper->DeleteFavouriteByTargetId($aTargetId, $sTargetType);
    }

    /**
     * Удаление тегов по таргету
     *
     * @param array $aTargetId Список ID владельцев
     * @param string $sTargetType Тип владельца
     * @return bool
     */
    public function DeleteTagByTarget($aTargetId, $sTargetType)
    {
        return $this->oMapper->DeleteTagByTarget($aTargetId, $sTargetType);
    }

    /**
     * Возвращает наиболее часто используемые теги
     *
     * @param int $iUserId ID пользователя
     * @param string $sTargetType Тип владельца
     * @param bool $bIsUser Возвращает все теги ли только пользовательские
     * @param int $iLimit Количество элементов
     * @return array
     */
    public function GetGroupTags($iUserId, $sTargetType, $bIsUser, $iLimit)
    {
        return $this->oMapper->GetGroupTags($iUserId, $sTargetType, $bIsUser, $iLimit);
    }

    /**
     * Возвращает список тегов по фильтру
     *
     * @param array $aFilter Фильтр
     * @param array $aOrder Сортировка
     * @param int $iCurrPage Номер страницы
     * @param int $iPerPage Количество элементов на страницу
     * @return array('collection'=>array,'count'=>int)
     */
    public function GetTags($aFilter, $aOrder, $iCurrPage, $iPerPage)
    {
        return array('collection' => $this->oMapper->GetTags($aFilter, $aOrder, $iCount, $iCurrPage, $iPerPage), 'count' => $iCount);
    }

    /**
     * Возвращает список тегов для топика, название метода формируется автоматически из GetTagsTarget()
     * @see GetTagsTarget
     *
     * @param int $iTargetId ID владельца
     * @return bool|array
     */
    public function GetTagsTargetTopic($iTargetId)
    {
        if ($oTopic = $this->Topic_GetTopicById($iTargetId)) {
            return $oTopic->getTagsArray();
        }
        return false;
    }
}
