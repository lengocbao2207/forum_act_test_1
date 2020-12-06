<?php

class NodesAsTabs_ControllerPublic_Category extends XFCP_NodesAsTabs_ControllerPublic_Category
{
	protected function _postDispatch($controllerResponse, $controllerName, $action)
	{
		parent::_postDispatch($controllerResponse, $controllerName, $action);

		$nodeId = (isset($controllerResponse->params['category']['node_id'])
			? $controllerResponse->params['category']['node_id']
			: 0);

		NodesAsTabs_API::postDispatch($this, $nodeId, $controllerResponse, $controllerName, $action);
	}
}