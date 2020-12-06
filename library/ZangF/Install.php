<?php

class ZangF_Install
{	
	public static function install()
	{
		$db = XenForo_Application::get('db');
 		$db->query("
			ALTER  TABLE `xf_thread` ADD  `z_thumb` TEXT NOT NULL AFTER  `title`
		");
	}
	
	public static function uninstall()
	{
		$db = XenForo_Application::get('db');
 		$db->query("
			ALTER TABLE `xf_thread` DROP  `z_thumb` TEXT NOT NULL AFTER  `title`
		");
	}
}