<?php

class XenPlaza_XPComment_DataWriter_DiscussionMessage_Post extends XFCP_XenPlaza_XPComment_DataWriter_DiscussionMessage_Post
{
	protected function _getFields() 
	{
		$fields = parent::_getFields();
		
		$fields['xf_post']['comment_count'] = array(
			'type' => self::TYPE_UINT,
		);
		
		return $fields;
	}
}