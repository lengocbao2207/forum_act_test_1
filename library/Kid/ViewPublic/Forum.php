<?php
class Kid_ViewPublic_Forum extends XFCP_Kid_ViewPublic_Forum
{
	/**
	 * Help render the HTML output.
	 *
	 * @return mixed
	 */
	public function renderHtml()
	{
		$this->_params['renderedNodes'] = XenForo_ViewPublic_Helper_Node::renderNodeTreeFromDisplayArray(
			$this, $this->_params['nodeList'], 2 // start at level 2, which means only 1 level of recursion
		);
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
			$this, 'message', ''
		);
	}

}
