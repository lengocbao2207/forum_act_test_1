<?php

class NodesAsTabs_ControllerPublic_Thread extends XFCP_NodesAsTabs_ControllerPublic_Thread
{
	protected function _postDispatch($controllerResponse, $controllerName, $action)
	{
		parent::_postDispatch($controllerResponse, $controllerName, $action);

		$nodeId = (isset($controllerResponse->params['forum']['node_id'])
			? $controllerResponse->params['forum']['node_id']
			: 0);

		NodesAsTabs_API::postDispatch($this, $nodeId, $controllerResponse, $controllerName, $action);
	}
}