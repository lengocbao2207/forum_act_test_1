<?php

class XenForo_ViewPublic_Thread_MultiQuoteOverlay extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$previewLength = XenForo_Application::get('options')->discussionPreviewLength;

		if ($previewLength)
		{
			$formatter = XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Base', array(
				'view' => $this
			));
			$parser = XenForo_BbCode_Parser::create($formatter);
			
			foreach ($this->_params['posts'] AS $postId => $post)
			{
				$this->_params['posts'][$postId]['messageParsed'] = $parser->render($post['message']);
			}
		}
	}
}