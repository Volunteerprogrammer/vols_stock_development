-- RegenerateTriggers(tbl)
-- Drops and recreates the AFTER INSERT / UPDATE / DELETE audit triggers for the
-- given table by reading column names from information_schema at call time.
-- Run this after any schema change to client, client_member, user, or user_role.
--
-- DELETE pattern: old_data = JSON(OLD row), new_data = NULL
-- INSERT pattern: old_data = NULL,           new_data = JSON(NEW row)
-- UPDATE pattern: old_data = JSON(OLD row),  new_data = JSON(NEW row)
--
-- Usage:
--   CALL RegenerateTriggers('client');
--   CALL RegenerateTriggers('client_member');
--   CALL RegenerateTriggers('user');
--   CALL RegenerateTriggers('user_role');

DELIMITER $$

DROP PROCEDURE IF EXISTS RegenerateTriggers $$

CREATE DEFINER=`sarum964_root`@`144.6.77.193` PROCEDURE RegenerateTriggers(IN tbl VARCHAR(64))
BEGIN
    DECLARE new_cols TEXT;
    DECLARE old_cols TEXT;

    SELECT GROUP_CONCAT(
        CONCAT('''', COLUMN_NAME, ''', NEW.`', COLUMN_NAME, '`')
        ORDER BY ORDINAL_POSITION
        SEPARATOR ', '
    ) INTO new_cols
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = tbl;

    SELECT GROUP_CONCAT(
        CONCAT('''', COLUMN_NAME, ''', OLD.`', COLUMN_NAME, '`')
        ORDER BY ORDINAL_POSITION
        SEPARATOR ', '
    ) INTO old_cols
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = tbl;

    -- INSERT
    SET @sql = CONCAT(
        'CREATE OR REPLACE DEFINER=`sarum964_root`@`144.6.77.193`',
        ' TRIGGER `', tbl, '_afterinsert_trigger`',
        ' AFTER INSERT ON `', tbl, '` FOR EACH ROW',
        ' CALL LogDML(''', tbl, ''', ''INSERT'', NEW.id, NULL, JSON_OBJECT(', new_cols, '), 0)'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- UPDATE
    SET @sql = CONCAT(
        'CREATE OR REPLACE DEFINER=`sarum964_root`@`144.6.77.193`',
        ' TRIGGER `', tbl, '_afterupdate_trigger`',
        ' AFTER UPDATE ON `', tbl, '` FOR EACH ROW',
        ' CALL LogDML(''', tbl, ''', ''UPDATE'', OLD.id, JSON_OBJECT(', old_cols, '), JSON_OBJECT(', new_cols, '), 0)'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- DELETE  (old_data = deleted row, new_data = NULL)
    SET @sql = CONCAT(
        'CREATE OR REPLACE DEFINER=`sarum964_root`@`144.6.77.193`',
        ' TRIGGER `', tbl, '_afterdelete_trigger`',
        ' AFTER DELETE ON `', tbl, '` FOR EACH ROW',
        ' CALL LogDML(''', tbl, ''', ''DELETE'', OLD.id, JSON_OBJECT(', old_cols, '), NULL, 0)'
    );
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

END $$

DELIMITER ;

-- Run immediately for all four audited tables:
CALL RegenerateTriggers('client');
CALL RegenerateTriggers('client_member');
CALL RegenerateTriggers('user');
CALL RegenerateTriggers('user_role');
