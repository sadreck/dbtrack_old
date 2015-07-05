CREATE TRIGGER %NAME% AFTER DELETE ON %TABLE% FOR EACH ROW
BEGIN

INSERT INTO dbtrack_actions (tablename, timeadded, actiontype, primarycolumn, primaryvalue)
VALUES('%TABLE%', UNIX_TIMESTAMP(), %ACTION%, '%PRIMARY%', OLD.%PRIMARY%);

SET @id = (SELECT LAST_INSERT_ID());
%INSERTS%

END