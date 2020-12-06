<?php

class ZangF_CacheRebuilder_Thumb extends XenForo_CacheRebuilder_Abstract
{
	public function getRebuildMessage()
	{
		return new XenForo_Phrase('zang_rebuild_thread_thumb');
	}

	public function showExitLink()
	{
		return true;
	}

	public function rebuild($position = 0, array &$options = array(), &$detailedMessage = '')
	{
		
		$options['batch'] = max(1, isset($options['batch']) ? $options['batch'] : 100);
		$thumbModel = XenForo_Model::create('ZangF_Model_Thumb');
		$threadModel = Xenforo_Model::create('XenForo_Model_Thread');
		$fetchOptions = array('limit' => $options['batch'], 'offset' => $position); 
		$threads = $threadModel->getThreads(array(), $fetchOptions);
		if (empty($threads))
		{
			return true;
		}
		
		foreach($threads AS $thread)
		{
			$position = $thread['thread_id'];
			$thumbModel->updateThumb($thread['thread_id'], 0);
		}
		
		$detailedMessage = XenForo_Locale::numberFormat($position);
        return $position;
	}
}