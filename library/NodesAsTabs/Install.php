<?php

class NodesAsTabs_Install
{
	public static function install()
	{
		$db = XenForo_Application::get('db');

		$db->query("
			CREATE TABLE IF NOT EXISTS `nat_options` (
				`node_id` int(10) unsigned NOT NULL,
				`nat_display_tab` tinyint(3) unsigned NOT NULL,
				`nat_display_tabperms` tinyint(3) unsigned NOT NULL,
				`nat_tabtitle` varchar(50) NOT NULL,
				`nat_display_order` int(10) unsigned NOT NULL,
				`nat_position` enum('home','middle','end') NOT NULL,
				`nat_childlinks` tinyint(3) unsigned NOT NULL,
				`nat_childlinksperms` tinyint(3) unsigned NOT NULL,
				`nat_unreadcount` tinyint(3) unsigned NOT NULL,
				`nat_markread` tinyint(3) unsigned NOT NULL,
				`nat_linkstemplate` varchar(50) NOT NULL,
				`nat_popup` tinyint(3) unsigned NOT NULL,
				`nat_popup_columns` tinyint(3) unsigned NOT NULL,
				`nat_tabid` varchar(50) NOT NULL,
				`nat_childnodes` text NOT NULL,
				`nat_firstchildnodes` mediumblob NOT NULL,
				UNIQUE KEY `node_id` (`node_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='NodesAsTabs addon'
		");

		// EARLY VERSIONS DIDN'T HAVE THESE COLUMNS
		self::addColumnIfNotExists('nat_markread', 'tinyint(3) unsigned NOT NULL', 'nat_childlinksperms');
		self::addColumnIfNotExists('nat_popup', 'tinyint(3) unsigned NOT NULL', 'nat_linkstemplate');
		self::addColumnIfNotExists('nat_tabtitle', 'varchar(50) NOT NULL', 'nat_display_tabperms');
		self::addColumnIfNotExists('nat_tabid', 'varchar(50) NOT NULL', 'nat_popup');
		self::addColumnIfNotExists('nat_unreadcount', 'tinyint(3) unsigned NOT NULL', 'nat_childlinksperms');
		self::addColumnIfNotExists('nat_popup_columns', 'tinyint(3) unsigned NOT NULL', 'nat_popup');

		$optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');

		$optionsModel->deleteOrphans();
		$optionsModel->rebuildCache();
	}

	public static function addColumnIfNotExists($fieldName, $fieldDef, $after)
	{
		$db = XenForo_Application::get('db');

		$exists = $db->fetchRow("
			SHOW COLUMNS
			FROM nat_options
			WHERE Field = ?
		", $fieldName);

		if (!$exists)
		{
			$db->query("
				ALTER TABLE nat_options ADD {$fieldName} {$fieldDef} AFTER {$after}
			");
		}
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		$db->query("
			DROP TABLE IF EXISTS `nat_options`
		");

		XenForo_Application::setSimpleCacheData('natCache', false);
	}
}