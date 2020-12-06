<?php
class rellect_NodesGrid_ControllerAdmin_Page extends XFCP_rellect_NodesGrid_ControllerAdmin_Page
{
	public function actionSave()
	{
		$GLOBALS['node_input'] = $this->getInput();
		return parent::actionSave();
	}
}
?>