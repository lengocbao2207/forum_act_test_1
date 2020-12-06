<?php
class rellect_NodesGrid_ControllerAdmin_LinkForum extends XFCP_rellect_NodesGrid_ControllerAdmin_LinkForum
{
	public function actionSave()
	{
		$GLOBALS['node_input'] = $this->getInput();
		return parent::actionSave();
	}
}
?>