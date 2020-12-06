<?php
class rellect_NodesGrid_DataWriter_Category extends XFCP_rellect_NodesGrid_DataWriter_Category
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xf_node']['grid_column'] = array(
			'type' => self::TYPE_BOOLEAN,
			'default' => 0
		);
		return $fields;
	}

	protected function _preSave()
	{
		parent::_preSave();
		if(isset($GLOBALS['node_input']))
		{
			$this->set('grid_column', $GLOBALS['node_input']->filterSingle('grid_column', XenForo_Input::BOOLEAN));
		}
	}
}
?>