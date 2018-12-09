<?php

/**
 * Original module: LiveStreet
 * Reworked by SparklingFire
 */

class ModuleMagicrule_MapperMagicrule extends Mapper
{

    public function GetCountVote($iUserId, $sTargetType, $sDate)
    {
        $sql = "
			SELECT count(*) as c FROM " . Config::Get('db.table.vote') . "
			WHERE
				user_voter_id = ?
				AND
				target_type = ?
				AND
				vote_date >= ?
		";
        if ($aRow = $this->oDb->selectRow($sql, $iUserId, $sTargetType, $sDate)) {
            return $aRow['c'];
        }
        return 0;
    }

    public function GetSumRatingTopic($iUserId, $sDate = null)
    {
        $sql = "
			SELECT sum(topic_rating) as s FROM " . Config::Get('db.table.topic') . "
			WHERE
				user_id = ?
				AND
				topic_publish = 1
				{ AND topic_date_add >= ? }
		";
        if ($aRow = $this->oDb->selectRow($sql, $iUserId, $sDate ? $sDate : DBSIMPLE_SKIP)) {
            return $aRow['s'] ? $aRow['s'] : 0;
        }
        return 0;
    }

    public function GetSumRatingComment($iUserId, $sDate = null)
    {
        $sql = "
			SELECT sum(comment_rating) as s FROM " . Config::Get('db.table.comment') . "
			WHERE
				user_id = ?
				AND
				target_type = 'topic'
				AND
				comment_publish = 1
				AND
				comment_delete = 0
				{ AND comment_date >= ? }
		";
        if ($aRow = $this->oDb->selectRow($sql, $iUserId, $sDate ? $sDate : DBSIMPLE_SKIP)) {
            return $aRow['s'] ? $aRow['s'] : 0;
        }
        return 0;
    }
}