<?php

class NodesAsTabs_ViewAdmin_Node_List extends XFCP_NodesAsTabs_ViewAdmin_Node_List
{
	public function renderHtml()
	{
		if ($this->_parentHasMethod('renderHtml'))
		{
			parent::renderHtml();
		}

		$optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');
		$tabNodes = $optionsModel->getTabLabelsForNodeTree();

		foreach ($this->_params['nodes'] AS &$node)
		{
			if (!empty($tabNodes[$node['node_id']]))
			{
				if ($tabNodes[$node['node_id']]['nat_display_tab'])
				{
					$node['nat_tab_label'] = ' <span class="natNodeListTabLabel">' . new XenForo_Phrase('nat_' . $tabNodes[$node['node_id']]['nat_position'] . '_tab') . ' #' . $tabNodes[$node['node_id']]['nat_display_order'] . '</span>';

					if ($node['node_id'] == XenForo_Application::get('options')->natrootNode)
					{
						$node['nat_tab_label'] .= ' <span class="natNodeListTabLabel">' . new XenForo_Phrase('nat_root_node') . '</span>';
					}
				}
				else if ($tabNodes[$node['node_id']]['nat_tabid'])
				{
					$node['nat_tab_label'] = ' <span class="natNodeListTabLabel">' . new XenForo_Phrase('nat_assigned_to') . ' "' . $tabNodes[$node['node_id']]['nat_tabid'] . '"</span>';
				}
			}
		}
	}

	// FROM xfrocks ON XENFORO.COM
	// http://xenforo.com/community/threads/profile-posts-with-bb-codes.47847/page-3#post-610448
	protected function _parentHasMethod($method)
	{
		$us ='XFCP_' . __CLASS__;
		$usFound = false;

		foreach (class_parents($this) as $parent)
		{
			if ($parent === $us)
			{
				$usFound = true;
				continue;
			}

			if (!$usFound)
			{
				continue;
			}

			// Do not perform method check until we found ourself in the class hierarchy.
			// That needs to be done to safely trigger parent::$method.
			// Performing method_exists(get_parent_class($this), $method) is not enough
			// if our class is in the middle of the hierarchy:
			//
			// SomeAddOn_Class extends XFCP_SomeAddOn_Class...
			// Our_Class extends XFCP_Our_Class...
			// Target_Class
			//
			// pretty confusing...
			if (method_exists($parent, $method))
			{
				return true;
			}
		}

		return false;
	}
}