<?php
class XenPlaza_XPComment_Listener
{
	
	public static function templateCreate($templateName, array &$params, XenForo_Template_Abstract $template) 
	{
		$template->preloadTemplate('XP_comment_public_controls');
	}
	
	public static function templatePostRender($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
    {
        if ($templateName == 'post')
        {
			$content = $template->create('XP_comments', $template->getParams());
        }
    }
	
    public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) 
    {
    	$user = XenForo_Visitor::getInstance();
		$canPostComment = XenForo_Permission::hasPermission($user['permissions'], 'XPComment', 'post');
		
    	switch ($hookName)
        {
        	/*
            case 'message_content':
                $ourTemplate = $template->create('XP_comments', $template->getParams());
                $contents .= $ourTemplate->render();
                break;
            */
            case 'post_public_controls':
            	$params = $template->getParams();
                $params['canPostComment'] = $canPostComment;
                $params += $hookParams;
            	$ourTemplate = $template->create('XP_comment_public_controls', $params);
				$contents .= $ourTemplate->render();
				break;
		}
	}
	
	public static function loadClassListener($class, &$extend)
	{
		$classes = array(
			'ControllerPublic_Thread',
			'ControllerPublic_Post',
			'DataWriter_DiscussionMessage_Post',
		);
		foreach($classes AS $clas){
			if ($class == 'XenForo_' .$clas)
			{
				$extend[] = 'XenPlaza_XPComment_' .$clas;
			}
		}
	}

}