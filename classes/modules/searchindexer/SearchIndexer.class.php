<?php

use Predis\Connection\ConnectionException;

/**
 * Модуль для индексирования записей
 *
 * @package modules.searchindexer
 * @since 1.0
 */
class ModuleSearchIndexer extends Module
{
    /**
     * Экземпляр Celery
     *
     * @var Celery
     */
    protected $oCeleryClient;

    /**
     * Название общего индекса в Elasticsearch
     */
    protected $sIndex;

    /**
     * Тип записи топика
     */
    protected $sTopic;

    /**
     * Тип записи комментария
     */
    protected $sComment;


    /**
     * Инициализация модуля
     *
     */
    public function Init()
    {
        /**
         * Настройки Celery клиента для взаимодействия с Elasticsearch
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

        $this->sIndex = Config::Get('module.search.index');
        $this->sTopic = Config::Get('module.search.topic_key');
        $this->sComment = Config::Get('module.search.comment_key');
    }

    /**
     * Индексирует топик
     *
     * @param ModuleTopic_EntityTopic $oTopic сущность топика с данными
     */
    public function TopicIndex(ModuleTopic_EntityTopic $oTopic)
    {
        if ($this->oCeleryClient == null) {
            return;
        }
        $this->oCeleryClient->PostTask(
            'tasks.topic_index',
            [
                'index' => $this->sIndex,
                'key' => $this->sTopic,
                'topic_id' => $oTopic->getId(),
                'topic_blog_id' => $oTopic->getBlogId(),
                'topic_user_id' => $oTopic->getUserId(),
                'topic_type' => $oTopic->getType(),
                'topic_title' => $oTopic->getTitle(),
                'topic_text' => $oTopic->getText(),
                'topic_tags' => $oTopic->getTags(),
                'topic_date' => $oTopic->getDateAdd(),
                'topic_publish' => $oTopic->getPublish()
            ]
        );
    }

    /**
     * Удаляет топик
     *
     * @param ModuleTopic_EntityTopic $oTopic сущность топика с данными
     */
    public function TopicDelete(ModuleTopic_EntityTopic $oTopic)
    {
        if ($this->oCeleryClient == null) {
            return;
        }
        $this->oCeleryClient->PostTask(
            'tasks.topic_delete',
            [
                'index' => $this->sIndex,
                'key' => $this->sTopic,
                'topic_id' => $oTopic->getId()
            ]
        );
    }

    /**
     * Индексирует комментарий
     *
     * @param ModuleComment_EntityComment $oComment сущность комментария с данными
     */
    public function CommentIndex(ModuleComment_EntityComment $oComment)
    {
        if ($this->oCeleryClient == null) {
            return;
        }
        $this->oCeleryClient->PostTask(
            'tasks.comment_index',
            [
                'index' => $this->sIndex,
                'key' => $this->sComment,
                'comment_id' => $oComment->getId(),
                'comment_target_id' => $oComment->getTargetId(),
                'comment_target_type' => $oComment->getTargetType(),
                'comment_user_id' => $oComment->getUserId(),
                'comment_text' => $oComment->getText(),
                'comment_date' => $oComment->getDate(),
                'comment_publish' => $oComment->getPublish()
            ]
        );
    }

    /**
     * Удаляет комментарий
     *
     * @param ModuleComment_EntityComment $oComment сущность комментария с данными
     */
    public function CommentDelete(ModuleComment_EntityComment $oComment)
    {
        if ($this->oCeleryClient == null) {
            return;
        }
        $this->oCeleryClient->PostTask(
            'tasks.comment_delete',
            [
                'index' => $this->sIndex,
                'key' => $this->sComment,
                'comment_id' => $oComment->getId()
            ]
        );
    }
}
