<?php 

class ZangF_Model_Thumb extends XenForo_Model
{
	public function getThumb($string, $check=1)
	{
		$remove = array('[IMG]','[/IMG]','[media=youtube]','[/media]');
		$imgs = array();
		$count = 0;
		//[IMG] BBcode
		$pattern = '/\[IMG\]([^\[]*)\[\/IMG\]/';
		preg_match_all($pattern, $string, $img);
		if(isset($img[0][0])) {	 
			foreach($img[0] AS $thumb)
			{
				$thumb = str_replace($remove, '', $thumb);
				
				if($this->isImage($thumb, $check))
				{
					$imgs[] = $thumb;
					$count++;
				}
				
				if($count>=5)
				{
					return serialize($imgs);
				}
			}
		}
		
		//[media=youtbe] BBcode
		$pattern_media = '/\[media\=youtube\]([^\[]*)\[\/media\]/';
		preg_match_all($pattern_media, $string, $media);
		
		if(isset($media[0][0])) {
			foreach($media[0] AS $thumb)
			{
				$thumb = str_replace($remove, '', $thumb);
				if($this->isImage($thumb, $check))
				{
					$imgs[] = 'http://img.youtube.com/vi/'.$thumb.'/0.jpg';
					$count++;
				}
				
				if($count>=5)
				{
					return serialize($imgs);
				}
			}
		}
		
		if($imgs){
			return serialize($imgs);
		} else {
			return '';
		}
    }
	
	public function isImage($url, $check=1)
	{
		if($check!=1) return true;
		$list_type = XenForo_Application::get('options')->z_type_thumb;
		$min_size = XenForo_Application::get('options')->z_min_size;
		
		$list_type = explode(',', $list_type);
		
		$img_info = @getimagesize($url);
		if(!isset($img_info) OR !is_array($img_info)) {
			return false;
		}
		else {
			if($img_info['0'] >= $min_size AND $img_info['1'] >= $min_size AND in_array($img_info['2'], $list_type))
			{
				return true;
			}
		}
		return false;
		
	}
	
	public function updateThumb($thread_id, $check=1)
	{
		$fetchOptions = array('limit' => '1');
		if($posts = $this->postModel()->getPostsInThread($thread_id, $fetchOptions))
		{
			$thumb = '';
			foreach($posts AS $post)
			{
				if($thumb = $this->getThumb($posts[$post['post_id']]['message'], 0))
				{
					$this->_getDb()->query("UPDATE xf_thread SET	z_thumb = '$thumb' WHERE thread_id = ? ", $thread_id);	
					return true;
				}
			}
		}
		return false;
	}

	public function getThumbsByIds(array $threadIds)
	{
		if (!$threadIds)
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT thread.z_thumb, thread.thread_id
			FROM xf_thread AS thread
			WHERE thread.thread_id IN (' . $this->_getDb()->quote($threadIds) . ')
		', 'thread_id');
	}
	
	protected function attachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
	
	protected function postModel()
	{
		return $this->getModelFromCache('XenForo_Model_Post');
	}
}