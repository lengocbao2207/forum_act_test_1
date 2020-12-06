<?php
class rellect_NodesGrid_ControllerAdmin_Category extends XFCP_rellect_NodesGrid_ControllerAdmin_Category
{
	public function actionSave()
	{
		$GLOBALS['node_input'] = $this->getInput();
		return parent::actionSave();
	}
}
?>