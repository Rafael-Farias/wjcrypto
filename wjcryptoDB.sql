CREATE DATABASE wjcrypto;
CREATE TABLE wjcrypto.users(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`email` VARCHAR(255) NOT NULL UNIQUE,
	`password` VARCHAR(255) NOT NULL,
	`salt` VARCHAR(255) NOT NULL,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
CREATE TABLE wjcrypto.accounts_number(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
	`account_number` BIGINT UNSIGNED UNIQUE,
	`legal_person_account_id` BIGINT UNSIGNED NULL UNIQUE,
	`natural_person_account_id` BIGINT UNSIGNED NULL UNIQUE,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
CREATE TABLE wjcrypto.legal_person_accounts(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`cnpj` VARCHAR(255) NOT NULL UNIQUE,
	`company_register` VARCHAR(255) NOT NULL,
	`foundation_date` DATE NOT NULL,
	`balance` DECIMAL(15, 2) NOT NULL,
	`address_id` BIGINT UNSIGNED NOT NULL,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
CREATE TABLE wjcrypto.natural_person_accounts(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`cpf` VARCHAR(255) NOT NULL UNIQUE,
	`rg` VARCHAR(255) NOT NULL,
	`birth_date` DATE NOT NULL,
	`balance` DECIMAL(15, 2) NOT NULL,
	`address_id` BIGINT UNSIGNED NOT NULL,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
CREATE TABLE wjcrypto.clients_contacts(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`legal_person_account_id` BIGINT UNSIGNED NULL,
	`natural_person_account_id` BIGINT UNSIGNED NULL,
	`telephone` VARCHAR(255) NOT NULL,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
CREATE TABLE wjcrypto.addresses(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`address` VARCHAR(255) NOT NULL,
	`complement` TEXT,
	`city_id` BIGINT UNSIGNED,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
CREATE TABLE wjcrypto.cities(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`state_id` BIGINT UNSIGNED NOT NULL,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
CREATE TABLE wjcrypto.states(
	`id` BIGINT UNSIGNED AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`initials` VARCHAR(10) NOT NULL,
	`creation_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`update_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);
ALTER TABLE wjcrypto.accounts_number
ADD FOREIGN KEY (`user_id`) REFERENCES wjcrypto.users(`id`);
ALTER TABLE wjcrypto.accounts_number
ADD FOREIGN KEY (`legal_person_account_id`) REFERENCES wjcrypto.legal_person_accounts(`id`);
ALTER TABLE wjcrypto.accounts_number
ADD FOREIGN KEY (`natural_person_account_id`) REFERENCES wjcrypto.natural_person_accounts(`id`);
ALTER TABLE wjcrypto.legal_person_accounts
ADD FOREIGN KEY (`address_id`) REFERENCES wjcrypto.addresses(`id`);
ALTER TABLE wjcrypto.natural_person_accounts
ADD FOREIGN KEY (`address_id`) REFERENCES wjcrypto.addresses(`id`);
ALTER TABLE wjcrypto.clients_contacts
ADD FOREIGN KEY (`legal_person_account_id`) REFERENCES wjcrypto.legal_person_accounts(`id`);
ALTER TABLE wjcrypto.clients_contacts
ADD FOREIGN KEY (`natural_person_account_id`) REFERENCES wjcrypto.natural_person_accounts(`id`);
ALTER TABLE wjcrypto.addresses
ADD FOREIGN KEY (`city_id`) REFERENCES wjcrypto.cities(`id`);
ALTER TABLE wjcrypto.cities
ADD FOREIGN KEY (`state_id`) REFERENCES wjcrypto.states(`id`);