CREATE FUNCTION %NAME%_function() RETURNS trigger AS $%NAME%_function$
  DECLARE lastid bigint;
  BEGIN
    INSERT INTO dbtrack_actions (tablename, timeadded, actiontype, primarycolumn, primaryvalue)
    VALUES('%TABLE%', EXTRACT(EPOCH FROM now()), %ACTION%, '%PRIMARY%', NEW.%PRIMARY%) RETURNING id INTO lastid;

    %INSERTS%

    RETURN NULL;
  END;
$%NAME%_function$ LANGUAGE plpgsql;

CREATE TRIGGER %NAME% AFTER INSERT ON %TABLE% FOR EACH ROW
  EXECUTE PROCEDURE %NAME%_function();