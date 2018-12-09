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
 * Экшен обработки поиска по сайту
 *
 * @package actions
 * @since 1.0
 */
class ActionSearch extends Action
{
    /**
     * Версия параметров поиска (для сохранения обратной совместимости)
     *
     * @var array
     */
    const PARAM_VERSION = 1;
    /**
     * Зарегистрированные параметры поиска
     *
     * @var array
     */
    protected $aParams = [];
    /**
     * Параметры поиска, зарегистрированные как кодированные
     *
     * @var array
     */
    protected $aCodedParams = [];

    /**
     * Стандартная строка кодированных параметров
     *
     * @var int
     */
    protected $sCodedDefault = 'tsdttt';

    /**
     * Инициализация
     */
    public function Init()
    {
        $this->SetDefaultEvent('index');
        $this->Viewer_AddHtmlTitle($this->Lang_Get('search'));
    }

    /**
     * Отображение формы поиска
     */
    function EventIndex()
    {
        $this->SetTemplateAction('index');

        /*
         * Регистрируем поисковые параметры
         */
        $sCoded = getRequestStr('c');
        if ($sCoded == '') $sCoded = $this->sCodedDefault;

        $this->RegisterCodedQueryParam($sCoded, 0, 'type', ['t' => 'topic', 'c' => 'comment']);
        $this->RegisterCodedQueryParam($sCoded, 1, 'sort_by', ['d' => 'date', 's' => 'score', 'r' => 'rating']);
        $this->RegisterCodedQueryParam($sCoded, 2, 'sort_dir', ['a' => 'asc', 'd' => 'desc']);
        $this->RegisterCodedQueryParam($sCoded, 3, 'topic_type_title', ['t' => true, 'f' => false]);
        $this->RegisterCodedQueryParam($sCoded, 4, 'topic_type_text', ['t' => true, 'f' => false]);
        $this->RegisterCodedQueryParam($sCoded, 5, 'topic_type_tags', ['t' => true, 'f' => false]);

        $this->Viewer_AssignJS('sCoded', $sCoded);
        $this->Viewer_AssignJS('sParamVersion', self::PARAM_VERSION);

        $sQuery = getRequestStr('q');
        if ($sQuery !== '') {
            $iPage = intval(preg_replace('#^page([1-9]\d{0,5})$#', '\1', Router::GetActionEvent()));
            if ($iPage == 0) $iPage = 1;

            /**
             * Проверяем, чтоб длина запроса была в допустимых пределах
             */
            if (!func_check($sQuery, 'text', 2, 255)) {
                $this->Message_AddErrorSingle($this->Lang_Get('search_error_length'), $this->Lang_Get('error'));
                return false;
            }

            $this->Viewer_AssignJS('sQuery', $sQuery);
            $this->Viewer_AddHtmlTitle($sQuery);

            /**
             * Направляем запрос в ElasticSearch, получаем результаты
             */
            $aResults = $this->Search_RunQuery(array_merge($this->aParams, $this->aCodedParams), $sQuery, $iPage - 1);
            if ($aResults === false) {
                /**
                 * Произошла ошибка при поиске
                 */
                $this->Message_AddErrorSingle($this->Lang_Get('search_error'), $this->Lang_Get('error'));
                return false;
            }

            /*
             * Устанавливаем количество найденых результатов
             */
            $this->Viewer_Assign('iResCount', $aResults['total']);

            if ($aResults['total'] > 0) {
                /*
                * Конфигурируем парсер
                */
                $this->Text_LoadJevixConfig('search');

                if ($this->aCodedParams['type'] == 'topic') {
                    $aTopics = $this->Topic_GetTopicsAdditionalData(array_column($aResults['hits'], '_id'));
                    foreach ($aTopics AS $oTopic) {
                        $oTopic->setTextShort($this->Text_JevixParser($oTopic->getText()));
                    }
                    $this->Viewer_Assign('aTopics', $aTopics);
                } else {
                    $aComments = $this->Comment_GetCommentsAdditionalData(array_column($aResults['hits'], '_id'));
                    foreach ($aComments AS $oComment) {
                        $oComment->setText($this->Text_JevixParser(htmlspecialchars($oComment->getText())));
                    }
                    $this->Viewer_Assign('aComments', $aComments);
                }

                /**
                 * Конфигурируем пагинацию
                 */
                $aPaging = $this->Viewer_MakePaging(
                    $aResults['total'],
                    $iPage,
                    Config::Get('module.search.per_page'),
                    Config::Get('pagination.pages.count'),
                    Router::GetPath('search'),
                    array_merge(
                        [
                            'q' => $sQuery,
                            'c' => $sCoded,
                            'v' => self::PARAM_VERSION
                        ],
                        $this->aParams
                    )
                );
                $this->Viewer_Assign('aPaging', $aPaging);
            }
        }
    }

    private function RegisterCodedQueryParam($str, $pos, $name, array $values)
    {
        if (strlen($str) !== strlen($this->sCodedDefault)) {
            $this->Message_AddErrorSingle($this->Lang_Get('search_error'), $this->Lang_Get('error'));
        }

        $val = $str{$pos};
        if (!in_array($val, array_keys($values))) {
            $this->Message_AddErrorSingle($this->Lang_Get('search_error'), $this->Lang_Get('error'));
        }

        $this->aCodedParams[$name] = $values[$val]; // Сохраняем переменную в глобальный список

    }

    /**
     * Обработка стандарта для браузеров Open Search
     */
    function EventOpenSearch()
    {
        Router::SetIsShowStats(false);
        $this->Viewer_Assign('sAdminMail', Config::Get('sys.mail.from_email'));
    }

    /**
     * Регистрация евентов
     */
    protected function RegisterEvent()
    {
        $this->AddEvent('index', 'EventIndex');
        $this->AddEventPreg('/^(page([1-9]\d{0,5}))?$/i', 'EventIndex');
        $this->AddEvent('opensearch', 'EventOpenSearch');
    }

}