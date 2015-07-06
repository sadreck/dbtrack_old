CREATE FUNCTION %NAME%_function() RETURNS trigger AS $%NAME%_function$
  DECLARE lastid bigint;
  BEGIN
    INSERT INTO dbtrack_actions (tablename, timeadded, actiontype)
    VALUES('%TABLE%', EXTRACT(EPOCH FROM now()), %ACTION%) RETURNING id INTO lastid;

    %PRIMARYKEYS%

    %INSERTS%

    RETURN NULL;
  END;
$%NAME%_function$ LANGUAGE plpgsql;

CREATE TRIGGER %NAME% AFTER %TYPE% ON %TABLE% FOR EACH ROW
  EXECUTE PROCEDURE %NAME%_function();