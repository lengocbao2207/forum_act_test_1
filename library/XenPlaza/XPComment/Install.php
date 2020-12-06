<?php

class XenPlaza_XPComment_Install
{
	public static function addColumn($table, $field, $attr)
	{
		if (!self::checkIfFieldExist($table, $field)) 
		{
			$db = XenForo_Application::get('db');
			return $db->query("ALTER TABLE `" . $table . "` ADD `" . $field . "` " . $attr);
		}
	}

	public static function checkIfFieldExist($table, $field)
	{
		$db = XenForo_Application::get('db');
		if ($db->fetchRow('SHOW COLUMNS FROM `' . $table . '` WHERE Field = ?', $field)) 
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
	public static function checkIfTableExist($table)
	{
		$db = XenForo_Application::get('db');
		if ($db->fetchRow('SHOW TABLES LIKE \'' . $table . '\'')) 
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
	public static function installCode()
	{
		$db = XenForo_Application::get('db');
		
		$db->query("
			CREATE TABLE IF NOT EXISTS xf_comment (
				comment_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
				user_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
				username VARCHAR(50) NOT NULL DEFAULT '',
				content_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
				comment MEDIUMTEXT NOT NULL,
				comment_date INT(10) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (comment_id),
				KEY date (comment_date)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
		");
		self::addColumn('xf_post', 'comment_count', "  SMALLINT(5) NULL  ");
	}
	
	public static function uninstallCode()
	{
		$db = XenForo_Application::get('db');
		$db->query("
			DROP TABLE xf_comment
		");
		$db->query("
			ALTER TABLE  `xf_post` DROP  `comment_count`
		");
	}	
}