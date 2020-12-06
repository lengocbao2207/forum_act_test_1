<?php
class VietXf_MoreThread_Listener{
	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template){
        switch($templateName) {
            case 'thread_view':
            $template->preloadTemplate('MoreThread_main');
            break;
        }
    }
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template){
        if($hookName == 'tinkhac'){
			$ourTemplate = $template->create('MoreThread_main', $template->getParams());
			$ourTemplate->setParam('message', $hookParams['message']);
			$contents .= $ourTemplate->render();
        }
    }
	public static function load_controllers($class, array &$extend){
		if($class == 'XenForo_ControllerPublic_Thread'){
			$extend[] = 'VietXf_MoreThread_ControllerPublic_Thread';
		}
	}
}
?>