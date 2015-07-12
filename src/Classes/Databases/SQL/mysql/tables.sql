CREATE TABLE `dbtrack_actions` (
  `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
  `tablename` VARCHAR(255) NOT NULL DEFAULT '',
  `timeadded` INT(10) NOT NULL DEFAULT 0,
  `actiontype` TINYINT(4) NOT NULL DEFAULT 0,
  `groupid` INT(10) NOT NULL DEFAULT 0,
  `brokenchain` TINYINT(1) NOT NULL DEFAULT 0,
  `message` TEXT,
  PRIMARY KEY (`id`),
  INDEX `ix_tablename` (`tablename` ASC),
  INDEX `ix_timeadded` (`timeadded` ASC),
  INDEX `ix_actiontype` (`actiontype` ASC),
  INDEX `ix_groupid` (`groupid` ASC),
  INDEX `ix_brokenchain` (`brokenchain` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `dbtrack_data` (
  `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
  `actionid` BIGINT(10) NOT NULL DEFAULT 0,
  `columnname` VARCHAR(255) NOT NULL DEFAULT '',
  `databefore` LONGTEXT,
  `dataafter` LONGTEXT,
  PRIMARY KEY (`id`),
  INDEX `ix_actionid` (`actionid` ASC),
  INDEX `ix_columnname` (`columnname` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `dbtrack_keys` (
  `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
  `actionid` BIGINT(10) NOT NULL DEFAULT 0,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `value` LONGTEXT,
  PRIMARY KEY (`id`),
  INDEX `ix_keyactionid` (`actionid` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;