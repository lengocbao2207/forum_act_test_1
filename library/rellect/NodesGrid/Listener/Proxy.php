<?php

class rellect_NodesGrid_Listener_Proxy
{
	public static function load_class_datawriter($class, array &$extend)
	{
		switch($class)
		{
			case 'XenForo_DataWriter_Category':
				$extend[] = 'rellect_NodesGrid_DataWriter_Category';
			break;

			case 'XenForo_DataWriter_Forum':
				$extend[] = 'rellect_NodesGrid_DataWriter_Forum';
			break;

			case 'XenForo_DataWriter_LinkForum':
				$extend[] = 'rellect_NodesGrid_DataWriter_LinkForum';
			break;

			case 'XenForo_DataWriter_Page':
				$extend[] = 'rellect_NodesGrid_DataWriter_Page';
			break;
		}
	}

	public static function load_class_controller($class, array &$extend)
	{
		switch($class)
		{
			case 'XenForo_ControllerAdmin_Category':
				$extend[] = 'rellect_NodesGrid_ControllerAdmin_Category';
			break;

			case 'XenForo_ControllerAdmin_Forum':
				$extend[] = 'rellect_NodesGrid_ControllerAdmin_Forum';
			break;

			case 'XenForo_ControllerAdmin_LinkForum':
				$extend[] = 'rellect_NodesGrid_ControllerAdmin_LinkForum';
			break;

			case 'XenForo_ControllerAdmin_Page':
				$extend[] = 'rellect_NodesGrid_ControllerAdmin_Page';
			break;
		}
	}
}

?>