<?php

abstract class XenPlaza_XPComment_Option_ForumChooser
{
	public static function renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$value = $preparedOption['option_value'];

		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
			'preparedOption' => $preparedOption,
			'canEditOptionDefinition' => $canEdit
		));

		$nodeModel = XenForo_Model::create('XenForo_Model_Node');

		$forumOptions = $nodeModel->getNodeOptionsArray($nodeModel->getAllNodes(), $preparedOption['option_value'], '(None)');

		return $view->createTemplateObject('XPComment_helper_forums', array(
			'fieldPrefix' => $fieldPrefix,
			'listedFieldName' => $fieldPrefix . '_listed[]',
			'preparedOption' => $preparedOption,
			'formatParams' => $forumOptions,
			'editLink' => $editLink
		));
	}
}