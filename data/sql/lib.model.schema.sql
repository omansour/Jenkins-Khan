
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- jenkins_group_run
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `jenkins_group_run`;

CREATE TABLE `jenkins_group_run`
(
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`date` DATE NOT NULL,
	`user_id` CHAR(36) NOT NULL,
	`label` CHAR(100) NOT NULL,
	`git_branch` CHAR(32) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `natural_pk` (`user_id`, `git_branch`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- jenkins_run
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `jenkins_run`;

CREATE TABLE `jenkins_run`
(
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`jenkins_group_run_id` INTEGER(11) NOT NULL,
	`job_name` CHAR(30) NOT NULL,
	`job_build_number` INTEGER(11),
	`git_branch` CHAR(32) NOT NULL,
	`launched` INTEGER(1) DEFAULT 1 NOT NULL,
	`parameters` TEXT,
	PRIMARY KEY (`id`),
	INDEX `jenkins_run_FI_1` (`jenkins_group_run_id`),
	CONSTRAINT `jenkins_run_FK_1`
		FOREIGN KEY (`jenkins_group_run_id`)
		REFERENCES `jenkins_group_run` (`id`)
		ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- configuration
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `configuration`;

CREATE TABLE `configuration`
(
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(50) NOT NULL,
	`value` TEXT,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `name` (`name`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- profile
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `profile`;

CREATE TABLE `profile`
(
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`sf_guard_user_id` INTEGER NOT NULL,
	`jenkins_url` VARCHAR(50),
	PRIMARY KEY (`id`),
	INDEX `profile_FI_1` (`sf_guard_user_id`),
	CONSTRAINT `profile_FK_1`
		FOREIGN KEY (`sf_guard_user_id`)
		REFERENCES `sf_guard_user` (`id`)
		ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;