<?php

class XenPlaza_XPComment_ViewPublic_XPComment extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return array(
			'comment' => $this->createTemplateObject('XP_post_comment', $this->_params)
		);
	}
}