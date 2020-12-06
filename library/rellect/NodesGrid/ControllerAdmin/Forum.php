<?php
class rellect_NodesGrid_ControllerAdmin_Forum extends XFCP_rellect_NodesGrid_ControllerAdmin_Forum
{
	public function actionSave()
	{
		$GLOBALS['node_input'] = $this->getInput();
		return parent::actionSave();
	}
}
?>