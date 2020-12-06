<?php

class XenPlaza_XPComment_ControllerPublic_Post extends XFCP_XenPlaza_XPComment_ControllerPublic_Post
{
	public function actionComment()
	{
		$this->_assertRegistrationRequired();
		
		$visitor = XenForo_Visitor::getInstance();
		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
		$message = $this->_input->filterSingle('message', XenForo_Input::STRING);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

		$commentModel = $this->_getCommentModel();
		$comment = $commentModel->getCommentsForPostId($postId);
		
		$options = XenForo_Application::get('options');
		$maxLength = $options->XPComment_maxLength;
		$exclude = $options->XPComment_exclude;

		if (in_array($forum['node_id'], $exclude))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_page_not_found'), 404));
		}
		
		if (!$commentModel->canPostComment())
		{
			throw $this->getErrorOrNoPermissionResponseException('do_not_have_permission');
		}

		if ($this->_request->isPost())
		{
			if (utf8_strlen($message) > $maxLength) 
	    	{
	    		return $this->responseError(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)));
	    	}
	    	
	    	if (!$message)
		    {
		    	return $this->responseError(new XenForo_Phrase('please_enter_valid_message'));
		    }
    	
			$writer = XenForo_DataWriter::create('XenPlaza_XPComment_DataWriter_XPComment');
			$writer->set('user_id', $visitor['user_id']);
			$writer->set('username', $visitor['username']);
			$writer->set('content_id', $postId);
			$writer->set('comment', $message);

			$writer->save();
			
			if ($this->_noRedirect())
			{
				$comment = $commentModel->getCommentById($writer->get('comment_id'));
				
				$viewParams = array(
					'comment' => $commentModel->prepareComment($comment)
				);

				return $this->responseView('XenPlaza_XPComment_ViewPublic_XPComment', '', $viewParams);
			}
			else
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('posts', $post)
				);
			}
		}
		else
		{
			$viewParams = array(
				'post' => $post,
				'thread' => $thread,
				'forum' => $forum,
				'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			);
			
			return $this->responseView('XenPlaza_XPComment_ViewPublic', 'XP_post_comment_post', $viewParams);
		}
	}

	public function actionCommentEdit()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);
		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
		$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
		
		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

		$commentModel = $this->_getCommentModel();
		$comment = $commentModel->getCommentById($commentId);
		
		if(!$commentModel->canEditComment($comment))
		{
			throw $this->getErrorOrNoPermissionResponseException('do_not_have_permission');
		}
						
		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('XenPlaza_XPComment_DataWriter_XPComment');
			$dw->setExistingData($commentId);
			$dw->set('comment', $message);
			
			$dw->save();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('posts', $post)
			);
		}
		else
		{	
			$viewParams = array(
				'comment' => $comment,
				'post' => $post,
				'thread' => $thread,
				'forum' => $forum,
				'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			);

			return $this->responseView('XenPlaza_XPComment_ViewPublic', 'XP_post_comment_edit', $viewParams);
		}
	}

	public function actionCommentDelete()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);
		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
		
		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

		$commentModel = $this->_getCommentModel();
		$comment = $commentModel->getCommentById($commentId);
		
		if(!$commentModel->canDeleteComment($comment))
		{
			throw $this->getErrorOrNoPermissionResponseException('do_not_have_permission');
		}
		
		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('XenPlaza_XPComment_DataWriter_XPComment');
			$dw->setExistingData($commentId);
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('posts', $post)
			);
		}
		else
		{			
			$viewParams = array(
				'comment' => $comment,
				'post' => $post,
				'thread' => $thread,
				'forum' => $forum,
				'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			);

			return $this->responseView('XenPlaza_XPComment_ViewPublic', 'XP_post_comment_delete', $viewParams);
		}
	}

	public function actionComments()
	{
		$visitor = XenForo_Visitor::getInstance();
		$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);

		$ftpHelper = $this->getHelper('ForumThreadPost');
		list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);

		$beforeDate = $this->_input->filterSingle('before', XenForo_Input::UINT);

		$commentModel = $this->_getCommentModel();
		$comments = $commentModel->getCommentsForPostId($postId);

		if (!$comments)
		{
			return $this->responseMessage(new XenForo_Phrase('no_comments_to_display'));
		}

		foreach ($comments AS &$comment)
		{
			$comment = $commentModel->prepareComment($comment);
		}

		$viewParams = array(
			'comments' => $comments,
			'post' => $post,
			'thread' => $thread,
			'forum' => $forum,
			'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
		);

		return $this->responseView('XenPlaza_XPComment_ViewPublic', 'XP_post_comments', $viewParams);
	}

	protected function _getCommentModel()
	{
		return $this->getModelFromCache('XenPlaza_XPComment_Model_XPComment');
	}
}