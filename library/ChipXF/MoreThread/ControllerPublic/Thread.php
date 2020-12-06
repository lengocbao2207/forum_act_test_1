<?php

class ChipXF_MoreThread_ControllerPublic_Thread extends XFCP_ChipXF_MoreThread_ControllerPublic_Thread {

	public function actionIndex()
	{
		$response = parent::actionIndex();
		
		$options = XenForo_Application::getOptions();

		if(	
			$options->chip_morethread_turn 
			AND ! in_array($response->params['forum']['node_id'], explode(',', $options->chip_morethread_exclutenodes))
		)
		{
			$items = $this->getModelFromCache('ChipXF_MoreThread_Model_Thread')->getListThreads(
				$response->params['forum']['node_id'],
				$options->chip_morethread_mode,
				$options->chip_morethread_items
			);
			// truncate title
			foreach(array_keys($items) as $key)
			{
				$items[$key]['title']	 = XenForo_Helper_String::wholeWordTrim($items[$key]['title'], $options->chip_morethread_titletruncated);
				$items[$key]['postDate'] = XenForo_Locale::dateTime($items[$key]['post_date']);
			}
			
			$chipMoreThreads = array(
				'title'	=> (string)new XenForo_Phrase('chip_morethread_' . $options->chip_morethread_mode),
				'items'	=> $items,
			);
			// append to params
			$response->params += array(
				'morethreads'	=> $chipMoreThreads,
			);
		}

		return $response;
	}
}