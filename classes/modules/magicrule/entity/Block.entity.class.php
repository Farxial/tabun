<?php

/**
 * Original module: LiveStreet
 * Reworked by SparklingFire
 */

class ModuleMagicrule_EntityBlock extends EntityORM
{

    public function setData($aData)
    {
        $this->_aData['data'] = serialize($aData);
    }

    public function getData($sKey = null)
    {
        $aData = $this->_getDataOne('data');
        $aReturn = @unserialize($aData);

        if (is_null($sKey)) {
            if ($aReturn) {
                return $aReturn;
            }
            return array();
        } else {
            if ($aReturn and array_key_exists($sKey, $aReturn)) {
                return $aReturn[$sKey];
            } else {
                return null;
            }
        }
    }

    protected function beforeSave()
    {
        if ($this->_isNew()) {
            $this->setDateCreate(date("Y-m-d H:i:s"));
        }
        return true;
    }
}