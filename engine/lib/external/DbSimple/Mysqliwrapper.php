<?php
/**
 * DbSimple_Mysql: MySQL database.
 * (C) Dk Lab, http://en.dklab.ru
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * See http://www.gnu.org/copyleft/lesser.html
 *
 * Placeholders end blobs are emulated.
 *
 * @author Dmitry Koterov, http://forum.dklab.ru/users/DmitryKoterov/
 * @author Konstantin Zhinko, http://forum.dklab.ru/users/KonstantinGinkoTit/
 *
 */
require_once dirname(__FILE__) . '/Mysqli.php';

/**
 * Database class for MySQL.
 */
class DbSimple_MysqliWrapper extends DbSimple_Mysqli
{
    function _performQuery($queryMain)
    {
        $this->_lastQuery = $queryMain;
        $this->_expandPlaceholders($queryMain, false);
        $result = @mysqli_query($queryMain[0], $this->link);
        if ($result === false) return $this->_setDbError($queryMain[0]);
        if (!is_resource($result)) {
            if (preg_match('/^\s* INSERT \s+/six', $queryMain[0])) {
                // INSERT queries return generated ID.
                return @mysqli_insert_id($this->link);
            }
            // Non-SELECT queries return number of affected rows, SELECT - resource.
            return @mysqli_affected_rows($this->link);
        }
        return $result;
    }
}
