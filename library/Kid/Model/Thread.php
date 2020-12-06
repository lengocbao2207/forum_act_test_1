<?php

class Kid_Model_Thread extends XFCP_Kid_Model_Thread
{
	public function getThreadsByIds(array $threadIds, array $fetchOptions = array())
	{
		if (!$threadIds)
		{
			return array();
		}

		$joinOptions = $this->prepareThreadFetchOptions($fetchOptions);

		$preResults = $this->fetchAllKeyed('
			SELECT thread.*, xf_post.message
				' . $joinOptions['selectFields'] . '
			FROM xf_thread AS thread' . $joinOptions['joinTables'] . ' 
			LEFT JOIN xf_post ON (xf_post.post_id = thread.first_post_id)
			WHERE thread.thread_id IN (' . $this->_getDb()->quote($threadIds) . ')
		', 'thread_id');
		foreach ($preResults as $thread_id => &$thread)
		{
			
			$thread['kid_thumb'] = $this->xuly($thread['message'], $thread['first_post_id'], $thread['title']);
			$thread['message'] = XenForo_Helper_String::bbCodeStrip($thread['message']);
			$vowels = array('[IMG]','[MEDIA]','[ATTACH]','[media]');
			$thread['message'] = str_replace($vowels, "", $thread['message']);	
		}
		return $preResults;
	}
	
  public function getThreads(array $conditions, array $fetchOptions = array())
  {
    $whereConditions = parent::prepareThreadConditions($conditions, $fetchOptions);

    $sqlClauses = parent::prepareThreadFetchOptions($fetchOptions);
    $limitOptions = parent::prepareLimitFetchOptions($fetchOptions);

    $forceIndex = (!empty($fetchOptions['forceThreadIndex']) ? 'FORCE INDEX (' . $fetchOptions['forceThreadIndex'] . ')' : '');
	
    $preResults = parent::fetchAllKeyed(parent::limitQueryResults(
      '
        SELECT thread.*, xf_post.message AS kid_content
          ' . $sqlClauses['selectFields'] . '
				FROM xf_thread AS thread INNER JOIN xf_post ON (thread.first_post_id = xf_post.post_id) ' . $forceIndex . '
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'], $limitOptions['offset']
    ), 'thread_id');
	
	foreach ($preResults as $thread_id => &$thread)
	{
		
		$thread['kid_thumb'] = $this->xuly($thread['kid_content'],$thread['first_post_id'],$thread['title']);
		$thread['kid_content'] = XenForo_Helper_String::bbCodeStrip($thread['kid_content']);
		$vowels = array('[IMG]','[MEDIA]','[ATTACH]','[media]');
		
		$thread['kid_content'] = str_replace($vowels, "", $thread['kid_content']);	
	}
	return $preResults;
	
  }
  
  private function xuly($string,$id,$title)
  {
	$attachmentModel = new XenForo_Model_Attachment;
		
		$img_arr = array();		
		
		//[IMG] BBcode
		$pattern = '/\[IMG\]([^\[]*)\[\/IMG\]/';
		preg_match_all($pattern, $string, $matches);
		
		//[media=youtbe] BBcode
		$patternv = '/\[media\=youtube\]([^\[]*)\[\/media\]/';
		preg_match_all($patternv, $string, $matchesv);
		
		
		$attachments = $attachmentModel->getAttachmentsByContentId('post', $id);
		foreach ($matchesv[0] as $matchesv2)
		{
			if(isset($matchesv2)){	 
			$img_arr[] = '<div class="icon_play_video_recentNews"></div><img src="http://i1.ytimg.com/vi/'.$matchesv2.'/mqdefault.jpg" class="img_thumb" alt="'.$title.'"/>';			
			}
		} 
		foreach ($attachments AS $attachment)
		{
			if($attachment['width']){
				$attachmented= $attachmentModel->prepareAttachment($attachment);
				$img_arr[] = '<img src="'.$attachmented['thumbnailUrl'].'" class="img_thumb" alt="'.$title.'"/>';
				
			}
		}
				
		foreach ($matches[0] as $matches2)
		{
			if(isset($matches2)){	 
			$img_arr[] = '<img src="timthumb.php?src='.$matches2.'&h=300&w=0&zc=1" class="img_thumb" alt="'.$title.'"/>'; 
			}
		}

		
		$vowels = array('[IMG]','[/IMG]','[media=youtube]','[/media]');
		
		$img_arr = str_replace($vowels, "", $img_arr);	
		return $img_arr;
		
  
  }
}