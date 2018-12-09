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

use Predis\Connection\ConnectionException;

/**
 * Модуль для отправки почты(e-mail) через Celery
 * <pre>
 * $this->Mail_SetAdress('claus@mail.ru','Claus');
 * $this->Mail_SetSubject('Hi!');
 * $this->Mail_SetBody('How are you?');
 * $this->Mail_setHTML();
 * $this->Mail_Send();
 * </pre>
 *
 * @package engine.modules
 * @since 1.0
 */
class ModuleMail extends Module
{
    /**
     * Основной объект почтовика
     *
     * @var Celery
     */
    protected $oCeleryClient;
    /**
     * Мыло от кого отправляется вся почта
     *
     * @var string
     */
    protected $sFromEmail;
    /**
     * Имя от кого отправляется вся почта
     *
     * @var string
     */
    protected $sFromName;
    /**
     * Тема письма
     *
     * @var string
     */
    protected $sSubject = '';
    /**
     * Текст письма
     *
     * @var string
     */
    protected $sBody = '';
    /**
     * Использовать ли HTML
     *
     */
    protected $bIsHtml = true;
    /**
     * Адреса получателей
     *
     */
    protected $aRecipients = [];

    /**
     * Инициализация модуля
     *
     */
    public function Init()
    {
        /**
         * Настройки Celery клиента для отправки писем
         */
        try {
            $this->oCeleryClient = new Celery(
                Config::Get('sys.celery.host'),
                Config::Get('sys.celery.login'),
                Config::Get('sys.celery.password'),
                Config::Get('sys.celery.db'),
                Config::Get('sys.celery.exchange'),
                Config::Get('sys.celery.binding'),
                Config::Get('sys.celery.port'),
                Config::Get('sys.celery.backend')
            );
        } catch (ConnectionException $exc) {
            error_log($exc->getMessage());
        }
        /**
         * Мыло от кого отправляется вся почта
         */
        $this->sFromEmail = Config::Get('sys.mail.from_email');
        /**
         * Имя от кого отправляется вся почта
         */
        $this->sFromName = Config::Get('sys.mail.from_name');
    }

    /**
     * Устанавливает тему сообщения
     *
     * @param string $sText Тема сообщения
     */
    public function SetSubject($sText)
    {
        $this->sSubject = $sText;
    }

    /**
     * Устанавливает текст сообщения
     *
     * @param string $sText Текст сообщения
     */
    public function SetBody($sText)
    {
        $this->sBody = $sText;
    }

    /**
     * Отправляет сообщение(мыло)
     *
     * @return bool
     */
    public function Send()
    {
        if ($this->oCeleryClient == null) {
            return;
        }
        $this->oCeleryClient->PostTask(
            'tasks.send_mail',
            [
                'from_email' => $this->sFromEmail,
                'from_name' => $this->sFromName,
                'recipients' => $this->aRecipients,
                'subject' => $this->sSubject,
                'body' => $this->sBody,
                'is_html' => $this->bIsHtml,
            ]
        );
    }

    /**
     * Устанавливает единственный адрес получателя
     *
     * @param string $sMail Емайл
     * @param string $sName Имя
     */
    public function SetAdress($sMail, $sName = null)
    {
        $this->ClearAddresses();
        $this->AddAdress($sMail, $sName);
    }

    /**
     * Очищает все адреса получателей
     *
     */
    public function ClearAddresses()
    {
        $this->aRecipients = [];
    }

    /**
     * Добавляем новый адрес получателя
     *
     * @param string $sMail Емайл
     * @param string $sName Имя
     */
    public function AddAdress($sMail, $sName = '')
    {
        array_push($this->aRecipients, [$sName, $sMail]);
    }

    /**
     * Устанавливает режим отправки письма как HTML
     *
     */
    public function setHTML()
    {
        $this->bIsHtml = true;
    }

    /**
     * Устанавливает режим отправки письма как Text(Plain)
     *
     */
    public function setPlain()
    {
        $this->bIsHtml = false;
    }

    /**
     * Возвращает строку последней ошибки
     *
     * @return string
     */
    public function GetError()
    {
        return '';
    }
}
