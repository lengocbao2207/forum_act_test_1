<?php

class NodesAsTabs_ControllerPublic_Page extends XFCP_NodesAsTabs_ControllerPublic_Page
{
	protected function _postDispatch($controllerResponse, $controllerName, $action)
	{
		parent::_postDispatch($controllerResponse, $controllerName, $action);

		$nodeId = (isset($controllerResponse->params['page']['node_id'])
			? $controllerResponse->params['page']['node_id']
			: 0);

		NodesAsTabs_API::postDispatch($this, $nodeId, $controllerResponse, $controllerName, $action);
	}
}