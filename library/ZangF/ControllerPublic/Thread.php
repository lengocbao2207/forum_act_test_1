<?php 

class ZangF_ControllerPublic_Thread extends XFCP_ZangF_ControllerPublic_Thread
{
	public function actionSave()
	{
		$this->_assertPostOnly();
		$this->_assertRegistrationRequired();

		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

		$this->_assertCanEditThread($thread, $forum);

		$dwInput = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'z_thumb' => XenForo_Input::STRING,
			//'price' => XenForo_Input::UINT,
			'prefix_id' => XenForo_Input::UINT,
			'discussion_state' => XenForo_Input::STRING,
			'discussion_open' => XenForo_Input::UINT,
			'sticky' => XenForo_Input::UINT
		));

		$threadModel = $this->_getThreadModel();

		if (!$threadModel->canLockUnlockThread($thread, $forum))
		{
			unset($dwInput['discussion_open']);
		}

		if (!$threadModel->canStickUnstickThread($thread, $forum))
		{
			unset($dwInput['sticky']);
		}

		if (!$threadModel->canAlterThreadState($thread, $forum, $dwInput['discussion_state']))
		{
			unset($dwInput['discussion_state']);
		}

		if (!$this->_getPrefixModel()->verifyPrefixIsUsable($dwInput['prefix_id'], $forum['node_id']))
		{
			$dwInput['prefix_id'] = 0; // not usable, just blank it out
		}

		// TODO: check prefix requirements?

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$dw->setExistingData($threadId);
		$dw->bulkSet($dwInput);
		$dw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
		$dw->save();

		$this->_updateModeratorLogThreadEdit($thread, $dw);

		// special case for the discussion list inline editor
		if ($this->_input->filterSingle('_returnDiscussionListItem', XenForo_Input::UINT))
		{
			$visitorUserId = XenForo_Visitor::getUserId();

			$threadFetchOptions = array(
				'readUserId' => $visitorUserId,
				'postCountUserId' => $visitorUserId,
				'watchUserId' => $visitorUserId,
				'join' => XenForo_Model_Thread::FETCH_USER | XenForo_Model_Thread::FETCH_DELETION_LOG
			);
			$forumFetchOptions = array(
				'readUserId' => $visitorUserId
			);

			list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);

			$thread['forum'] = $forum;

			$viewParams = array(
				'thread' => $thread,
				'forum' => $forum,
				'showForumLink' => $this->_input->filterSingle('showForumLink', XenForo_Input::BOOLEAN)
			);

			return $this->responseView('XenForo_ViewPublic_Thread_Save_ThreadListItem', 'thread_list_item', $viewParams);
		}

		$thread = $dw->getMergedData();

		// regular redirect
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('threads', $thread)
		);
	}
	
	public function actionEditTitle()
	{
		$this->_assertRegistrationRequired();

		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId);

		$threadModel = $this->_getThreadModel();

		if (!$threadModel->canEditThreadTitle($thread, $forum, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->isConfirmedPost())
		{
			$dwInput = $this->_input->filter(array(
				'title' => XenForo_Input::STRING,
				//'price' =>  XenForo_Input::UINT,
				'prefix_id' => XenForo_Input::UINT
			));

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
			$dw->setExistingData($threadId);
			$dw->bulkSet($dwInput);
			$dw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
			$dw->preSave();

			if ($forum['require_prefix'] && !$dw->get('prefix_id'))
			{
				$dw->error(new XenForo_Phrase('please_select_a_prefix'), 'prefix_id');
			}

			$dw->save();

			$this->_updateModeratorLogThreadEdit($thread, $dw);
			$thread = $dw->getMergedData();

			// regular redirect
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('threads', $thread)
			);
		}
		else
		{
			$viewParams = array(
				'thread' => $thread,
				'forum' => $forum,

				'prefixes' => $this->_getPrefixModel()->getUsablePrefixesInForums($forum['node_id']),

				'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			);

			return $this->responseView('XenForo_ViewPublic_Thread_EditTitle', 'thread_edit_title', $viewParams);
		}
	}

}