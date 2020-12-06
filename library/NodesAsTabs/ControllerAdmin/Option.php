<?php

class NodesAsTabs_ControllerAdmin_Option extends XFCP_NodesAsTabs_ControllerAdmin_Option
{
	public function actionSave()
	{
		$response = parent::actionSave();

		if ($response->redirectType == XenForo_ControllerResponse_Redirect::SUCCESS
			AND $this->_input->filterSingle('group_id', XenForo_Input::STRING) == 'nodesAsTabs'
		)
		{
			$inputOptions = $this->_input->filterSingle('options', XenForo_Input::ARRAY_SIMPLE);
			$rootNode = (isset($inputOptions['natrootNode']) ? $inputOptions['natrootNode'] : 0);
			$rootNodeSelect = (isset($inputOptions['natrootNodeSelect']) ? $inputOptions['natrootNodeSelect'] : 0);
			$data['options'] = XenForo_Model::create('XenForo_Model_Option')->rebuildOptionCache();

			$options = new XenForo_Options($data['options']);
			$options->__set('natrootNode', $rootNode);
			$options->__set('natrootNodeSelect', $rootNodeSelect);
			XenForo_Application::setDefaultsFromOptions($options);
			XenForo_Application::set('options', $options);

			$optionsModel = $this->_getOptionsModel();

			$optionsModel->deleteOrphans();
			$optionsModel->rebuildCache();
		}

		return $response;
	}

	protected function _getOptionsModel()
	{
		return $this->getModelFromCache('NodesAsTabs_Model_Options');
	}
}