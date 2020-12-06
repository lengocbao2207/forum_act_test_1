<?php

class ZangF_Listener
{
	public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data) {
		XenForo_CacheRebuilder_Abstract::$builders['ZangF_CacheRebuilder_Thumb'] = 'ZangF_CacheRebuilder_Thumb';
		XenForo_Template_Helper_Core::$helperCallbacks += array(
            'showthumb' => array('ZangF_Helper', 'showthumb')
        );
	}	
	
	public static function controller($class, array &$extend)
	{
		//Controller
		if($class == 'XenForo_ControllerPublic_Forum')
		{
			$extend[] = 'ZangF_ControllerPublic_Forum';
		}
		if($class == 'XenForo_ControllerPublic_Thread')
		{
			$extend[] = 'ZangF_ControllerPublic_Thread';
		}
		if($class == 'XenForo_ControllerPublic_Post')
		{
			$extend[] = 'ZangF_ControllerPublic_Post';
		}
		
	}
	
	public static function datawriter($class, array &$extend)
	{
		if($class == 'XenForo_DataWriter_Discussion_Thread')
		{
			$extend[] = 'ZangF_DataWriter_Thread';
		}
				
	}
	
	public static function model($class, array &$extend)
	{
		if($class == 'XenForo_Model_Feed')
		{
			$extend[] = 'ZangF_Model_Feed';
		}
	}
	
	
	public static function hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch($hookName)
		{
			case 'thread_create_fields_main':
				$contents .= $template->create('zang_thread_create');
			break;
		}
	}

}
