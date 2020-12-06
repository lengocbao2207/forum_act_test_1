<?php

class NodesAsTabs_ControllerAdmin_Forum extends XFCP_NodesAsTabs_ControllerAdmin_Forum
{
	public function actionSave()
	{
		$response = parent::actionSave();

		if ($response->redirectType == XenForo_ControllerResponse_Redirect::SUCCESS)
		{
			NodesAsTabs_API::actionSave($response, $this);
		}

		return $response;
	}

	public function actionDelete()
	{
		$response = parent::actionDelete();

		NodesAsTabs_API::actionDelete($response, $this);

		return $response;
	}

	public function actionValidateField()
	{
		$response = parent::actionValidateField();

		NodesAsTabs_API::actionValidateField($response, $this);

		return $response;
	}
}