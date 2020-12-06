<?php

class XenPlaza_XPComment_ControllerPublic_Thread extends XFCP_XenPlaza_XPComment_ControllerPublic_Thread
{
	public function actionIndex()
	{
		$response = parent::actionIndex();

		$commentModel = $this->_getCommentModel();
		$options = XenForo_Application::get('options');
		$exclude = $options->XPComment_exclude;
		$latestComment = $options->XPComment_latestComment;

		$nodeId = $response->params['thread']['node_id'];

		if(isset($response->params['posts']) && !in_array($nodeId, $exclude))
		{
			foreach($response->params['posts'] AS &$post)
			{
				if($post['comment_count'])
				{
					$post['comments'] = $commentModel->getCommentsForPostId($post['post_id']);
					
					foreach($post['comments'] AS &$comment)
					{
						if($commentModel->canEditComment($comment))
						{
							$comment['canEdit'] = true;
						}
						
						if($commentModel->canDeleteComment($comment))
						{
							$comment['canDelete'] = true;
						}
					}
				}
			}
		}

       	return $response;
	}

	protected function _getCommentModel()
	{
		return $this->getModelFromCache('XenPlaza_XPComment_Model_XPComment');
	}
}