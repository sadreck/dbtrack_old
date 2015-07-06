CREATE TABLE dbtrack_actions (
  id SERIAL CONSTRAINT actionkey PRIMARY KEY,
  tablename VARCHAR(255) NOT NULL DEFAULT '',
  timeadded INT NOT NULL DEFAULT 0,
  actiontype SMALLINT NOT NULL DEFAULT 0,
  groupid INT NOT NULL DEFAULT 0,
  message TEXT
);

CREATE INDEX ix_tablename ON dbtrack_actions(tablename);
CREATE INDEX ix_timeadded ON dbtrack_actions(timeadded);
CREATE INDEX ix_actiontype ON dbtrack_actions(actiontype);
CREATE INDEX ix_groupid ON dbtrack_actions(groupid);

CREATE TABLE dbtrack_data (
  id SERIAL CONSTRAINT datakey PRIMARY KEY,
  actionid INT NOT NULL DEFAULT 0,
  columnname VARCHAR(255) NOT NULL DEFAULT '',
  databefore TEXT,
  dataafter TEXT
);

CREATE INDEX ix_actionid ON dbtrack_data(actionid);
CREATE INDEX ix_columnname ON dbtrack_data(columnname);

CREATE TABLE dbtrack_keys (
  id SERIAL CONSTRAINT keyskey PRIMARY KEY,
  actionid INT NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  value TEXT
);

CREATE INDEX ix_keyactionid ON dbtrack_keys(actionid);