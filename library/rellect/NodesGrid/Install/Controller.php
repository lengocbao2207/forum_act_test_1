<?php

class rellect_NodesGrid_Install_Controller
{
	public static $addOnData;
	public static $db;

	public static function install($existingAddOn, $addOnData, SimpleXMLElement $xml = null)
	{
		if(XenForo_Application::$versionId < 1020000)
		{
			throw new XenForo_Exception('This add-on requires XenForo 1.2 or higher.', true);
		}

		self::$addOnData = $addOnData;
		self::$db = XenForo_Application::get('db');

		if(!$existingAddOn)
		{
			// New installation
			self::jumpToStep(1);
		}
		else
		{
			// Upgrades
			if($addOnData['version_id'] > $existingAddOn['version_id'])
			{
				self::jumpToStep($addOnData['version_id'] + 1);
			}
		}
	}

	/**
	* Check if$field exists in $table
	*/
	public static function isFieldExists($field, $table)
	{
		if(empty(self::$db))
			self::$db = XenForo_Application::get('db');

		if(self::$db->fetchRow("SHOW columns FROM `$table` WHERE Field = '$field'"))
			return true;

		return false;
	}

	public static function uninstall()
	{
		self::$db = XenForo_Application::get('db');

		if(self::isFieldExists('grid_column', 'xf_node'))
		{
			self::$db->query("
				ALTER TABLE `xf_node` DROP `grid_column`
			");
		}
	}

	/**
	* Upgrades queue
	* @var		int		step number
	* @var		int		last step
	*/
	public static function jumpToStep($stepNumber)
	{
		$step = 'step'.$stepNumber;
		if(method_exists(__CLASS__, $step))
		{
			$flag = self::$step(self::$db);
		}

		/* Check for the next step */
		while($stepNumber <= self::$addOnData['version_id'])
		{
			$stepNumber++;
			if(method_exists(__CLASS__, $step))
				self::jumpToStep($stepNumber + 1);
		}
	}

	/**
	* v1.0.0
	*/
	private static function step1()
	{
		if(!self::isFieldExists('grid_column', 'xf_node'))
		{
			self::$db->query("
				ALTER TABLE `xf_node` 
				ADD (`grid_column` BOOLEAN NOT NULL DEFAULT FALSE)
			");
		}
	}
}

?>