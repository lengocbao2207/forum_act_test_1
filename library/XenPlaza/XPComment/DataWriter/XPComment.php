<?php

class XenPlaza_XPComment_DataWriter_XPComment extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'xf_comment' => array(
				'comment_id'          	=> array('type' => self::TYPE_UINT,    'autoIncrement' => true),
				'user_id'         	 	=> array('type' => self::TYPE_UINT,    'required' => true),
				'username'    		 	=> array('type' => self::TYPE_STRING,  'required' => true, 'maxLength' => 50),
				'content_id'         	=> array('type' => self::TYPE_UINT,    'required' => true),
				'comment'    			=> array('type' => self::TYPE_STRING,  'required' => true),
				'comment_date'  		=> array('type' => self::TYPE_UINT,    'default' => XenForo_Application::$time),
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_comment' => $this->_getCommentModel()->getCommentById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'comment_id = ' . $this->_db->quote($this->getExisting('comment_id'));
	}

	protected function _preSave()
	{
		if ($this->isChanged('message'))
		{
			$maxLength = 4;
			if (utf8_strlen($this->get('message')) > $maxLength)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)), 'message');
			}
		}
	}

	protected function _postSave()
	{
		$comment = $this->getMergedData();
		
		if ($this->isInsert())
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
			$dw->setExistingData($this->get('content_id'));
			if ($comment['comment'])
			{
				$dw->set('comment_count', $dw->get('comment_count') + 1);
			}
			$dw->save();

			$post = $this->_getPostModel()->getPostById($this->get('content_id'), array(
				'join' => XenForo_Model_User::FETCH_USER_OPTION
			));

			/*
			XenForo_Model_Alert::alert(
				$post['user_id'],
				$this->get('user_id'),
				$this->get('username'),
				'profile_post',
				$this->get('comment_id'),
				'comment_your_post'
			);
			*/
		}
	}
	
	protected function _postDelete()
	{
		$comment = $this->getMergedData();

		$this->_db->query('
			UPDATE xf_post
			SET comment_count = IF(comment_count > 0, comment_count - 1, 0)
			WHERE post_id = ?
		', $this->get('content_id'));

	}
	
	protected function _getCommentModel()
	{
		return $this->getModelFromCache('XenPlaza_XPComment_Model_XPComment');
	}

	protected function _getPostModel()
	{
		return $this->getModelFromCache('XenForo_Model_Post');
	}
}