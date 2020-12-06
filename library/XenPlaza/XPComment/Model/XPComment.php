<?php

class  XenPlaza_XPComment_Model_XPComment extends XenForo_Model
{

	public function getCommentById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT comment.*,
				user.*
			FROM xf_comment AS comment
			LEFT JOIN xf_user AS user ON (user.user_id = comment.user_id)
			WHERE comment.comment_id = ?
		', $id);
	}
	
	public function getCommentsForPostId($postId)
	{
		return $this->fetchAllKeyed('
			SELECT comment.*,
				user.*
			FROM xf_comment AS comment
			LEFT JOIN xf_user AS user ON (user.user_id = comment.user_id)
			WHERE comment.content_id = ' . $this->_getDb()->quote($postId) . '
		', 'comment_id');
	}
	
	public function getCommentsForPostIds(array $postIds)
	{
		return $this->fetchAllKeyed('
			SELECT comment.*,
				user.*
			FROM xf_comment AS comment
			INNER JOIN xf_user AS user ON
				(comment.user_id = user.user_id)
			WHERE comment.content_id IN (' . $this->_getDb()->quote($postIds) . ')
			ORDER BY comment.comment_date DESC
		', 'comment_id');
	}

	public function getAllComments()
	{
		return $this->fetchAllKeyed('
			SELECT comment.*,
				user.*
			FROM xf_comment AS comment
			LEFT JOIN xf_user AS user 
				ON (user.user_id = comment.user_id)
		', 'comment_id');
	}
	
	public function prepareComment($comment, array $user = null)
	{
		$this->standardizeViewingUserReference($user);

		$comment['canDelete'] = $this->canDeleteComment($comment, $user);
		$comment['canEdit'] = $this->canEditComment($comment, $user);

		return $comment;
	}
	
	public function canPostComment(array $user = null)
	{
		$this->standardizeViewingUserReference($user);

		return XenForo_Permission::hasPermission($user['permissions'], 'XPComment', 'post');		
	}
	
	public function canEditComment(array $comment, array $user = null)
	{
		$this->standardizeViewingUserReference($user);

		if ($user['user_id'] == $comment['user_id'])
		{
			return XenForo_Permission::hasPermission($user['permissions'], 'XPComment', 'editOwn');
		}
		else
		{
			return XenForo_Permission::hasPermission($user['permissions'], 'XPComment', 'edit');
		}
	}
	
	public function canDeleteComment(array $comment, array $user = null)
	{
		$this->standardizeViewingUserReference($user);

		if ($user['user_id'] == $comment['user_id'])
		{
			return XenForo_Permission::hasPermission($user['permissions'], 'XPComment', 'deleteOwn');
		}
		else
		{
			return XenForo_Permission::hasPermission($user['permissions'], 'XPComment', 'delete');
		}
	}
}