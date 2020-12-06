<?php 

class ZangF_ControllerPublic_Forum extends XFCP_ZangF_ControllerPublic_Forum
{
	
	/**
	 * Inserts a new thread into this forum.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAddThread()
	{
		$this->_assertPostOnly();

		$forumId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
		$forumName = $this->_input->filterSingle('node_name', XenForo_Input::STRING);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		$forum = $ftpHelper->assertForumValidAndViewable($forumId ? $forumId : $forumName);

		$forumId = $forum['node_id'];

		$this->_assertCanPostThreadInForum($forum);

		if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
		{
			return $this->responseCaptchaFailed();
		}

		$visitor = XenForo_Visitor::getInstance();

		$input = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			//Thêm input z_thumb
			'z_thumb' => XenForo_Input::STRING,
			//'price' => XenForo_Input::UINT,
			'prefix_id' => XenForo_Input::UINT,
			'attachment_hash' => XenForo_Input::STRING,

			'watch_thread_state' => XenForo_Input::UINT,
			'watch_thread' => XenForo_Input::UINT,
			'watch_thread_email' => XenForo_Input::UINT,

			'_set' => array(XenForo_Input::UINT, 'array' => true),
			'discussion_open' => XenForo_Input::UINT,
			'sticky' => XenForo_Input::UINT,

			'poll' => XenForo_Input::ARRAY_SIMPLE, // filtered below
		));
		$input['message'] = $this->getHelper('Editor')->getMessageText('message', $this->_input);
		$input['message'] = XenForo_Helper_String::autoLinkBbCode($input['message']);

		//Thêm z_thumb nếu người dùng chưa điền hoặc điền sai
		if($input['z_thumb'] == '' OR !$this->thumbModel()->isImage($input['z_thumb']))
		{
			//Tự động tìm hình trong bài viết
			if(!$input['z_thumb'] = $this->thumbModel()->getThumb($input['message']))
			{
				//Nếu vẫn không tìm đc hình nào trong bài viết thì kệ nó thôi =))
				$input['z_thumb'] = '';
			}
		}
		
		if (!$this->_getPrefixModel()->verifyPrefixIsUsable($input['prefix_id'], $forumId))
		{
			$input['prefix_id'] = 0; // not usable, just blank it out
		}

		$pollInputHandler = new XenForo_Input($input['poll']);
		$pollInput = $pollInputHandler->filter(array(
			'question' => XenForo_Input::STRING,
			'responses' => array(XenForo_Input::STRING, 'array' => true),
			'multiple' => XenForo_Input::UINT,
			'public_votes' => XenForo_Input::UINT,
			'close' => XenForo_Input::UINT,
			'close_length' => XenForo_Input::UNUM,
			'close_units' => XenForo_Input::STRING
		));

		// note: assumes that the message dw will pick up the username issues
		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$writer->bulkSet(array(
			'user_id' => $visitor['user_id'],
			'username' => $visitor['username'],
			'title' => $input['title'],
			'z_thumb' => $input['z_thumb'],
			//'price' => $input['price'],
			'prefix_id' => $input['prefix_id'],
			'node_id' => $forumId
		));

		// discussion state changes instead of first message state
		$writer->set('discussion_state', $this->getModelFromCache('XenForo_Model_Post')->getPostInsertMessageState(array(), $forum));

		// discussion open state - moderator permission required
		if (!empty($input['_set']['discussion_open']) && $this->_getForumModel()->canLockUnlockThreadInForum($forum))
		{
			$writer->set('discussion_open', $input['discussion_open']);
		}

		// discussion sticky state - moderator permission required
		if (!empty($input['_set']['sticky']) && $this->_getForumModel()->canStickUnstickThreadInForum($forum))
		{
			$writer->set('sticky', $input['sticky']);
		}

		$postWriter = $writer->getFirstMessageDw();
		$postWriter->set('message', $input['message']);
		$postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);
		$postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage_Post::OPTION_MAX_TAGGED_USERS, $visitor->hasPermission('general', 'maxTaggedUsers'));

		$writer->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);

		if ($pollInput['question'] !== '')
		{
			$pollWriter = XenForo_DataWriter::create('XenForo_DataWriter_Poll');
			$pollWriter->bulkSet(
				XenForo_Application::arrayFilterKeys($pollInput, array('question', 'multiple', 'public_votes'))
			);
			$pollWriter->set('content_type', 'thread');
			$pollWriter->set('content_id', 0); // changed before saving
			if ($pollInput['close'])
			{
				if (!$pollInput['close_length'])
				{
					$pollWriter->error(new XenForo_Phrase('please_enter_valid_length_of_time'));
				}
				else
				{
					$pollWriter->set('close_date', $pollWriter->preVerifyCloseDate(strtotime('+' . $pollInput['close_length'] . ' ' . $pollInput['close_units'])));
				}
			}
			$pollWriter->addResponses($pollInput['responses']);
			$pollWriter->preSave();
			$writer->mergeErrors($pollWriter->getErrors());

			$writer->set('discussion_type', 'poll', '', array('setAfterPreSave' => true));
		}
		else
		{
			$pollWriter = false;

			foreach ($pollInput['responses'] AS $response)
			{
				if ($response !== '')
				{
					$writer->error(new XenForo_Phrase('you_entered_poll_response_but_no_question'));
					break;
				}
			}
		}

		// TODO: check for required prefix in this node

		$spamModel = $this->_getSpamPreventionModel();

		if (!$writer->hasErrors()
			&& $writer->get('discussion_state') == 'visible'
			&& $spamModel->visitorRequiresSpamCheck()
		)
		{
			switch ($spamModel->checkMessageSpam($input['title'] . "\n" . $input['message'], array(), $this->_request))
			{
				case XenForo_Model_SpamPrevention::RESULT_MODERATED:
					$writer->set('discussion_state', 'moderated');
					break;

				case XenForo_Model_SpamPrevention::RESULT_DENIED;
					$writer->error(new XenForo_Phrase('your_content_cannot_be_submitted_try_later'));
					break;
			}
		}

		$writer->preSave();

		if ($forum['require_prefix'] && !$writer->get('prefix_id'))
		{
			$writer->error(new XenForo_Phrase('please_select_a_prefix'), 'prefix_id');
		}

		if (!$writer->hasErrors())
		{
			$this->assertNotFlooding('post');
		}

		$writer->save();

		$thread = $writer->getMergedData();

		if ($pollWriter)
		{
			$pollWriter->set('content_id', $thread['thread_id'], '', array('setAfterPreSave' => true));
			$pollWriter->save();
		}

		$spamModel->logContentSpamCheck('thread', $thread['thread_id']);
		$this->_getDraftModel()->deleteDraft('forum-' . $forum['node_id']);

		$this->_getThreadWatchModel()->setVisitorThreadWatchStateFromInput($thread['thread_id'], $input);

		$this->_getThreadModel()->markThreadRead($thread, $forum, XenForo_Application::$time);

		if (!$this->_getThreadModel()->canViewThread($thread, $forum))
		{
			$return = XenForo_Link::buildPublicLink('forums', $forum, array('posted' => 1));
		}
		else
		{
			$return = XenForo_Link::buildPublicLink('threads', $thread);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$return,
			new XenForo_Phrase('your_thread_has_been_posted')
		);
	}
	
	protected function thumbModel()
	{
		return $this->getModelFromCache('ZangF_Model_Thumb');
	}
}