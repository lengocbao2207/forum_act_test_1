<?php 

class ZangF_ControllerPublic_Post extends XFCP_ZangF_ControllerPublic_Post
{
	public function actionSave()
	{
		$this->_assertPostOnly();

		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

		$this->_assertCanEditPost($post, $thread, $forum);

		$input = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING,

			'watch_thread_state' => XenForo_Input::UINT,
			'watch_thread' => XenForo_Input::UINT,
			'watch_thread_email' => XenForo_Input::UINT
		));
		$input['message'] = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$input['message'] = XenForo_Helper_String::autoLinkBbCode($input['message']);
		if($thread['z_thumb'] == '')
		{
				//Tự động tìm hình trong bài viết
				if($z_thumb = $this->thumbModel()->getThumb($input['message']))
				{
					$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
					$dw->setExistingData($thread['thread_id']);
					$dw->set('z_thumb', $z_thumb);
					$dw->save();
				}
		}
		
		$dw = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
		$dw->setExistingData($postId);
		$dw->set('message', $input['message']);
		$dw->setExtraData(XenForo_DataWriter_DiscussionMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);
		$dw->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
		$this->_setSilentEditOptions($post, $thread, $forum, $dw);
		$dw->save();

		$this->_getThreadWatchModel()->setVisitorThreadWatchStateFromInput($thread['thread_id'], $input);

		XenForo_Model_Log::logModeratorAction('post', $post, 'edit', array(), $thread);

		return $this->getPostSpecificRedirect($post, $thread);
	}
	
	public function actionSaveInline()
	{
		$this->_assertPostOnly();

		if ($this->_input->inRequest('more_options'))
		{
			return $this->responseReroute(__CLASS__, 'edit');
		}

		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

		$this->_assertCanEditPost($post, $thread, $forum);

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
		$dw->setExistingData($postId);
		$dw->set('message',
			XenForo_Helper_String::autoLinkBbCode(
				$this->getHelper('Editor')->getMessageText('message', $this->_input)
			)
		);
		$dw->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
		$this->_setSilentEditOptions($post, $thread, $forum, $dw);
		$dw->save();

		if($thread['z_thumb'] == '')
		{
				//Tự động tìm hình trong bài viết
				if($z_thumb = $this->thumbModel()->getThumb($this->getHelper('Editor')->getMessageText('message', $this->_input)))
				{
					$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
					$dw->setExistingData($thread['thread_id']);
					$dw->set('z_thumb', $z_thumb);
					$dw->save();
				}
		}
		
		XenForo_Model_Log::logModeratorAction('post', $post, 'edit', array(), $thread);

		if ($this->_noRedirect())
		{
			$this->_request->setParam('thread_id', $thread['thread_id']);

			return $this->responseReroute('XenForo_ControllerPublic_Thread', 'show-posts');
		}
		else
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('posts', $post)
			);
		}
	}
	
	protected function thumbModel()
	{
		return $this->getModelFromCache('ZangF_Model_Thumb');
	}
}