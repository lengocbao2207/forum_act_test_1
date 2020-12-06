<?php

// CLASS FOR ESSENTIAL NODE FUNCTIONS WITH RESPECT TO THIS ADDON
// NEW NON-DEFAULT NODE TYPES FROM OTHER ADDONS CAN CALL THESE FUNCTIONS AS APPROPRIATE TO WORK WITH THIS ADDON
// GENERAL USAGE:
// if (XenForo_Application::isRegistered('nodesAsTabsAPI'))
// {
//	NodesAsTabs_API::function();
// }
class NodesAsTabs_API
{
	// TO BE CALLED BY POSTDISPATCH IN YOUR PUBLIC CONTROLLER
	// HANDLES TAB SELECTION
	public static function postDispatch($controller, $nodeId, $controllerResponse, $controllerName, $action)
	{
		$optionsModel = self::_getOptionsModel();

		$routeMatch = $controller->getRouteMatch();
		$request = $controller->getRequest();

		$nodeId = ($nodeId ? $nodeId : $optionsModel->getNodeIdFromRequest($request));

		if ($nodeTabId = $optionsModel->handleRoute($nodeId, $routeMatch))
		{
			// USED LATER FOR BREADCRUMBS
			$controllerResponse->containerParams['nodeTabId'] = $nodeTabId;
		}
	}

	// TO BE CALLED BY NODE ADMIN CONTROLLER FOR EDIT FUNCTION
	// RETURNS NAT RECORD FOR THE NODE, SET AS VIEWPARAM
	// USED IN CONJUNCTION WITH A TEMPLATE INCLUDE IN YOUR CONTENT TEMPLATE:
	// <xen:include template="nat_nodeoptions" />
	public static function nodeOptionsRecord($nodeId = 0)
	{
		return NodesAsTabs_Listen::nodeOptionsRecord($nodeId);
	}

	// TO BE CALLED BY NODE ADMIN CONTROLLER FOR SAVE FUNCTION
	public static function actionSave($response, $controller)
	{
		$nodeData = $controller->getInput()->filter(array(
			'node_id' => XenForo_Input::UINT,
			'nat_display_tab' => XenForo_Input::UINT,
			'nat_display_tabperms' => XenForo_Input::UINT,
			'nat_tabtitle' => XenForo_Input::STRING,
			'nat_display_order' => XenForo_Input::UINT,
			'nat_position' => XenForo_Input::STRING,
			'nat_childlinks' => XenForo_Input::UINT,
			'nat_childlinksperms' => XenForo_Input::UINT,
			'nat_unreadcount' => XenForo_Input::UINT,
			'nat_markread' => XenForo_Input::UINT,
			'nat_linkstemplate' => XenForo_Input::STRING,
			'nat_popup' => XenForo_Input::UINT,
			'nat_popup_columns' => XenForo_Input::UINT,
			'nat_tabid' => XenForo_Input::STRING
		));

		if (empty($nodeData['node_id']))
		{
			$db = XenForo_Application::get('db');
			$nodeData['node_id'] = $db->fetchOne("
				SELECT node_id
				FROM xf_node
				ORDER BY node_id
				DESC
			");
		}

		$optionsModel = self::_getOptionsModel();

		$optionsModel->saveOptions($nodeData);

		$optionsModel->deleteOrphans();
		$optionsModel->rebuildCache();
	}

	// TO BE CALLED BY NODE ADMIN CONTROLLER FOR DELETE FUNCTION
	public static function actionDelete($response, $controller)
	{
		$optionsModel = self::_getOptionsModel();
		$optionsModel->deleteOrphans();
		$optionsModel->rebuildCache();
	}

	// TO BE CALLED BY NODE ADMIN CONTROLLER FOR VALIDATEFIELD FUNCTION
	// CAN MODIFY RESPONSE
	public static function actionValidateField(&$response, $controller)
	{
		// IF FIELD BEING VALIDATED IS FROM THIS ADDON THEN PROCESS DIFFERENTLY
		$fieldName = $controller->getInput()->filterSingle('name', XenForo_Input::STRING);
		$fieldValue = $controller->getInput()->filterSingle('value', XenForo_Input::STRING);
		if (substr($fieldName, 0, 4) == 'nat_')
		{
			// THIS STUFF BASED ON XenForo_Controller::_validateField WHICH IS PROTECTED
			$writer = XenForo_DataWriter::create('NodesAsTabs_DataWriter_Options');
			$writer->set($fieldName, $fieldValue);

			if ($errors = $writer->getErrors())
			{
				$newResponse = $controller->responseError($errors);
			}
			else
			{
				$newResponse = $controller->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					'',
					new XenForo_Phrase('redirect_field_validated', array('name' => $fieldName, 'value' => $fieldValue))
				);
			}

			$response = $newResponse;
		}
		else
		{
			// RESPONSE IS GOOD
		}
	}

	protected static function _getOptionsModel()
	{
		return XenForo_Model::create('NodesAsTabs_Model_Options');
	}
}