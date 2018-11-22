<?php

/**
 * Объект элемента истории изменений комментария
 *
 * @package modules.comment
 * @since 1.2
 */
class ModuleComment_EntityCommentHistoryItem extends Entity
{
    public function setComment(ModuleComment_EntityComment $oComment)
    {
        $this->_aData['flags'] = 0;
        $this->_aData['comment'] = $oComment->getId();
        $text = $oComment->getText();
        $this->_aData['text'] = &$text;
        $this->_aData['text_crc32'] = crc32($text);
        if ($oComment->isFlagRaised(ModuleComment_EntityComment::FLAG_MODIFIED)) {
            $this->_aData['validFrom'] = $oComment->getLastModifyDate();
            $this->_aData['validator'] = $oComment->getLastModifyUserId();
        } else {
            $this->_aData['validFrom'] = $oComment->getDate();
            $this->_aData['validator'] = $oComment->getUserId();
        }
    }

    public function setLastModifyInfo($date, $userId, $userTypeId = 0)
    {
        $this->_aData['invalidFrom'] = $date;
        $this->_aData['invalidator'] = $userId;
        $this->_aData['invalidatorType'] = $userTypeId;
    }

    public function getData()
    {
        return $this->_aData;
    }
}
