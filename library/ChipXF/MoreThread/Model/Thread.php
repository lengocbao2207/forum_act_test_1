<?php

class ChipXF_MoreThread_Model_Thread extends Xenforo_Model {

	public function getListThreads($nodeId, $type, $limit = 10)
	{
		switch($type)
		{
			case 'random':		$orderBy = 'RAND()'; 			break;
			case 'mostviewed':	$orderBy = '`view_count` DESC'; break;
			default: 			$orderBy = '`post_date` DESC'; 
		}

		$items = Xenforo_Application::getDb()->fetchAll('
			SELECT `thread_id`, `title`, `view_count`, `post_date`
			FROM `xf_thread`
			WHERE `node_id` = '.$nodeId.'
			ORDER BY '.$orderBy.'
			LIMIT 0, '.$limit.'
		');
		return $items;
	}
}