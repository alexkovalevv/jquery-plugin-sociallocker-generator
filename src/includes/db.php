<?php
	/**
	 * Подключение к базе данных
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright Alex Kovalev 04.03.2017
	 * @version 1.0
	 */

	$mysqli = new mysqli(HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$mysqli->set_charset("utf8");

	if( $mysqli->connect_errno ) {
		echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}

	if( isset($_GET['db_install']) ) {
		$query = $mysqli->query("
			CREATE TABLE IF NOT EXISTS wp_opanda_lockers (
			  id int(15) NOT NULL AUTO_INCREMENT,
			  user_id int(11) NOT NULL,
			  transaction_id varchar(80) CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci DEFAULT NULL,
			  locker_name varchar(50) CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NOT NULL,
			  locker_options text CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NOT NULL,
			  created_at int(11) NOT NULL,
			  PRIMARY KEY (id)
			)
			ENGINE = INNODB
			CHARACTER SET utf8mb4
			COLLATE utf8mb4_general_ci;
		");

		if( !$query ) {
			echo "Не удалось создать таблицу wp_opanda_lockers: (" . $mysqli->errno . ") " . $mysqli->error;
			exit();
		}

		$query = $mysqli->query("
			CREATE TABLE IF NOT EXISTS wp_opanda_users_identify (
			  id int(14) NOT NULL AUTO_INCREMENT,
			  guid varchar(255) NOT NULL,
			  email varchar(50) NOT NULL,
			  PRIMARY KEY (id),
			  UNIQUE INDEX guid (guid)
			)
			ENGINE = INNODB
			CHARACTER SET utf8
			COLLATE utf8_general_mysql500_ci;
		");

		if( !$query ) {
			echo "Не удалось создать таблицу wp_opanda_users_identify: (" . $mysqli->errno . ") " . $mysqli->error;
			exit();
		}

		$query = $mysqli->query("
			CREATE TABLE IF NOT EXISTS wp_opanda_vk_auth (
			  id bigint(20) NOT NULL AUTO_INCREMENT,
			  locker_id int(11) DEFAULT NULL,
			  process_url text DEFAULT NULL,
			  uid int(11) NOT NULL,
			  guid varchar(64) DEFAULT NULL,
			  status enum ('waiting', 'delivered') NOT NULL,
			  created_at int(11) NOT NULL,
			  updated_at int(11) DEFAULT NULL,
			  PRIMARY KEY (id)
			)
			ENGINE = INNODB
			CHARACTER SET utf8
			COLLATE utf8_general_mysql500_ci;
		");

		if( !$query ) {
			echo "Не удалось создать таблицу wp_opanda_vk_auth: (" . $mysqli->errno . ") " . $mysqli->error;
			exit();
		}

		echo 'Таблицы успешно созданы.';
		exit();
	}
