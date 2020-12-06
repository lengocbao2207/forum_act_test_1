<?php

class ChipXF_MoreThread_Listener {
	
	public static function extend($class, &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Thread')
		{
			$extend[] = 'ChipXF_MoreThread_ControllerPublic_Thread';
		}
	}
	
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if($templateName == 'thread_view')
		{
			// cache template
			$template->preloadTemplate('chip_morethreads');
		}
	}
	
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if($hookName == 'tinlienquan')
		{
			$params = $template->getParams();
			static $counter = 0; ++$counter;
			if($params['page'] == 1 AND $counter == 1 AND isset($params['morethreads']))
			{
				$templater = $template->create('chip_morethreads', $params + $hookParams);
				$contents .= $templater->render();
			}
		}
	}
}