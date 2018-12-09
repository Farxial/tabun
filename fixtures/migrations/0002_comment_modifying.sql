ALTER TABLE `ls_comment`
  ADD COLUMN `flags`                    SMALLINT(5) UNSIGNED  NOT NULL      DEFAULT 0,
  ADD COLUMN `comment_last_modify_id`   INT UNSIGNED          NULL          DEFAULT NULL,
  ADD COLUMN `comment_last_modify_user` INT(10) UNSIGNED      NULL          DEFAULT NULL,
  ADD COLUMN `comment_last_modify_date` DATETIME              NULL          DEFAULT NULL,
  ADD COLUMN `comment_lock_modify_user` INT(10) UNSIGNED      NULL          DEFAULT NULL,
  ADD COLUMN `comment_lock_modify_date` DATETIME              NULL          DEFAULT NULL;
