<?php

/**
 * Admin controller for handling actions on styles.
 *
 * @package XenForo_Style
 */
class TMS_ControllerAdmin_Style extends XFCP_TMS_ControllerAdmin_Style
{
	/**
	 * Displays the list of modifications in the specified style.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionTemplateModifications()
	{
		$styleId = $this->_input->filterSingle('style_id', XenForo_Input::UINT);
		$style = $this->_getStyleModel()->getStyleById($styleId, true);
		if (!$style)
		{
			return $this->responseError(new XenForo_Phrase('requested_style_not_found'), 404);
		}

		// set an edit_style_id cookie so we can switch to another area and maintain the current style selection
		XenForo_Helper_Cookie::setCookie('edit_style_id', $styleId);

		$styleModel = $this->_getStyleModel();

		$modificationModel = $this->_getModificationModel();

		$viewParams = array(
			'groupedModifications' => $modificationModel->groupModificationsByAddon(
				$modificationModel->getAllModificationsInStyle($styleId)
			),
			'logSummary' => $modificationModel->getModificationLogSummary(),
			'addOns' => $this->_getAddOnModel()->getAllAddOns(),


			'styles' => $styleModel->getAllStylesAsFlattenedTree($styleModel->showMasterStyle() ? 1 : 0),
			'masterStyle' => $styleModel->showMasterStyle() ? $styleModel->getStyleById(0, true) : array(),
			'style' => $style,

			'canCreateModification' => XenForo_Application::debugMode()
		);

		return $this->responseView('XenForo_ViewAdmin_TemplateModification_List', 'template_modification_list', $viewParams);
	}

	/**
	 * Lists all templates and style properties customized directly within the specified style
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionCustomizedComponents()
	{
		/* @var $response XenForo_ControllerResponse_Error */
		$response = parent::actionCustomizedComponents();
		if($response instanceof XenForo_ControllerResponse_Error
			&& $response->errorText == new XenForo_Phrase('style_contains_no_customized_templates_or_style_properties'))
		{
			$styleId = $this->_input->filterSingle('style_id', XenForo_Input::UINT);
			if (empty($styleId))
			{
				$styleId = $this->_getStyleModel()->getStyleIdFromCookie(false);
			}
			$style = $this->_getStyleOrError($styleId);

			$viewParams = array(
				'style' => $style,
				'templates' => array(),
				'properties' => array(),
				'itemCount' => 0,
				'styles' => $this->_getStyleModel()->getAllStylesAsFlattenedTree(),
			);
			$response = $this->responseView('XenForo_ViewAdmin_Style_CustomizedComponents',
				'style_customized_components', $viewParams);
		}
		elseif(!$response instanceof XenForo_ControllerResponse_View)
		{
			return $response;
		}

		/* @var $response XenForo_ControllerResponse_View */

		$style = $response->params['style'];

		$modifications = $this->_getModificationModel()->getAllModificationsInStyle($style['style_id']);

		if (empty($response->params['templates']) && empty($response->params['properties']) && empty($modifications))
		{
			return $this->responseError(new XenForo_Phrase('tms_style_contains_no_customized_templates_or_modifications_or_properties'));
		}

		$response->params['modifications'] = $modifications;
		$response->params['itemCount'] += count($modifications);

		return $response;
	}

	public function actionMassRevert()
	{
		$response = parent::actionMassRevert();

		$revertInfo = $this->_input->filter(array(
			'modifications' => array(XenForo_Input::UINT, 'array' => true),
		));

		if ($response instanceof XenForo_ControllerResponse_Redirect)
		{
			if ($revertInfo['modifications'])
			{
				foreach ($revertInfo['modifications'] AS $modificationId)
				{
					$dw = XenForo_DataWriter::create('XenForo_DataWriter_TemplateModification', XenForo_DataWriter::ERROR_SILENT);
					$dw->setExistingData($modificationId);
					$dw->delete();
				}
			}
		}
		elseif($response instanceof XenForo_ControllerResponse_View)
		{
			$response->params['modifications'] = $revertInfo['modifications'];
			return $response;
		}

		return $response;
	}
}