<?php

/**
 * LiveStreet CMS
 * Copyright © 2013 OOO "ЛС-СОФТ"
 *
 * ------------------------------------------------------
 *
 * Official site: www.livestreetcms.com
 * Contact e-mail: office@livestreetcms.com
 *
 * GNU General Public License, version 2:
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * ------------------------------------------------------
 *
 * @link http://www.livestreetcms.com
 * @copyright 2013 OOO "ЛС-СОФТ"
 * @author Maxim Mzhelskiy <rus.engine@gmail.com>
 *
 */
class ModulePage_EntityPage extends EntityORM
{

    /**
     * Правила валидации полей
     *
     * @var array
     */
    protected $aValidateRules = array(
        array('pid', 'parent_check'),
        array('title', 'string', 'allowEmpty' => false, 'min' => 1, 'max' => 250, 'label' => 'Название'),
        array('url', 'regexp', 'pattern' => '/^[\w\-_]+$/i', 'allowEmpty' => false, 'label' => 'URL'),
        array('text', 'string', 'allowEmpty' => true, 'min' => 1, 'max' => 50000, 'label' => 'Текст'),
        array('seo_keywords', 'string', 'allowEmpty' => true, 'min' => 1, 'max' => 500, 'label' => 'SEO Keywords'),
        array(
            'seo_description',
            'string',
            'allowEmpty' => true,
            'min' => 1,
            'max' => 500,
            'label' => 'SEO Description'
        ),
        array('sort', 'sort_check'),
        array('active', 'active_check'),
        array('main', 'main_check'),
    );

    /**
     * Связи с другими таблицами
     *
     * @var array
     */
    protected $aRelations = array(
        self::RELATION_TYPE_TREE,
    );

    public function _getTreeParentKey()
    {
        return 'pid';
    }

    /**
     * Проверка родительской страницы
     *
     * @param string $sValue Валидируемое значение
     * @param array $aParams Параметры
     * @return bool
     */
    public function ValidateParentCheck($sValue, $aParams)
    {
        if ($this->getPid()) {
            if ($oPage = $this->Page_GetPageById($this->getPid())) {
                if ($oPage->getId() == $this->getId()) {
                    return 'Попытка вложить страницу в саму себя';
                }
                $this->setUrlFull($oPage->getUrlFull() . '/' . $this->getUrl());
            } else {
                return 'Неверная страница';
            }
        } else {
            $this->setPid(null);
            $this->setUrlFull($this->getUrl());
        }
        return true;
    }

    /**
     * Установка дефолтной сортировки
     *
     * @param string $sValue Валидируемое значение
     * @param array $aParams Параметры
     * @return bool
     */
    public function ValidateSortCheck($sValue, $aParams)
    {
        if (!is_numeric($this->getSort())) {
            if (null !== ($iMin = $this->Page_GetMinSortFromPageByFilter())) {
                $this->setSort($iMin - 5);
            } else {
                $this->setSort(1000);
            }
        }
        return true;
    }

    /**
     * Проверка флага активности
     *
     * @param string $sValue Валидируемое значение
     * @param array $aParams Параметры
     * @return bool
     */
    public function ValidateActiveCheck($sValue, $aParams)
    {
        $this->setActive($this->getActive() ? 1 : 0);
        return true;
    }

    /**
     * Проверка флага показа на главной
     *
     * @param string $sValue Валидируемое значение
     * @param array $aParams Параметры
     * @return bool
     */
    public function ValidateMainCheck($sValue, $aParams)
    {
        $this->setMain($this->getMain() ? 1 : 0);
        return true;
    }

    /**
     * Возвращает полный URL до детального просмотра страницы
     *
     * @return string
     */
    public function getWebUrl()
    {
        return Router::GetPath('page') . $this->getUrlFull() . '/';
    }

    /**
     * Возвращает полный URL до детального просмотра страницы
     *
     * @return string
     */
    public function getAdminEditWebUrl()
    {
        return Router::GetPath('admin/plugin') . Plugin::GetPluginCode($this) . "/update/{$this->getId()}/";
    }

    /**
     * Метод автоматически выполняется перед сохранением объекта сущности (статьи)
     *
     * @return bool
     */
    protected function beforeSave()
    {
        /**
         * Если статья новая, то устанавливаем дату создания
         */
        if ($this->_isNew()) {
            $this->setDateAdd(date("Y-m-d H:i:s"));
        } else {
            $this->setDateEdit(date("Y-m-d H:i:s"));
        }
        return true;
    }

    /**
     * Выполняется перед удалением
     *
     * @return bool
     */
    protected function beforeDelete()
    {
        if ($bResult = parent::beforeDelete()) {
            /**
             * Запускаем удаление дочерних страниц
             */
            if ($aCildren = $this->getChildren()) {
                foreach ($aCildren as $oChildren) {
                    $oChildren->Delete();
                }
            }
        }
        return $bResult;
    }
}