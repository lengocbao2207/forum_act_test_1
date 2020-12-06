<?php

class NodesAsTabs_Listen
{
	public static function enableAPI(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		XenForo_Application::set('nodesAsTabsAPI', true);
	}

	public static function containerExtra(array &$params, XenForo_Dependencies_Abstract $dependencies)
	{
		if (!isset($params['forum']))
		{
			return;
		}

		$extraTabs = $params['extraTabs'];

		foreach ($extraTabs AS &$position)
		{
			foreach ($position AS &$extraTab)
			{
				if (isset($extraTab['selected']) AND $extraTab['selected'])
				{
					$extraTab['forum'] = $params['forum'];
				}
			}
		}

		$params['extraTabs'] = $extraTabs;
	}

	/* LINK FORUM TAB SELECTION */
	public static $linkForum;
	public static function linkForumSelect(XenForo_FrontController $fc, XenForo_RouteMatch &$routeMatch)
	{
		self::$linkForum = array(
			'nodeTabId' => 0,
			'nodeRecord' => array(),
			'exactMatch' => false
		);

		if (!XenForo_Application::get('options')->natlinkForumSelect)
		{
			return;
		}

		$optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');
		$natCache = $optionsModel->getSimpleCacheData();

		if (!$natCache['linkForumSelect'])
		{
			return;
		}

		$paths = XenForo_Application::get('requestPaths');

		$relativeUri = substr($paths['requestUri'], strlen($paths['basePath']));

		$depth = 0;
		foreach ($natCache['linkForumSelect'] AS $linkForum)
		{
			if (   substr($relativeUri, 0, strlen($linkForum['link_url'])) == $linkForum['link_url']   )
			{
				if (!self::$linkForum['nodeTabId'] OR $linkForum['depth'] > $depth)
				{
					$depth = $linkForum['depth'];

					self::$linkForum['nodeTabId'] = $optionsModel->handleRoute($linkForum['node_id'], $routeMatch);
					self::$linkForum['nodeRecord'] = $linkForum;
					self::$linkForum['exactMatch'] = ($relativeUri == $linkForum['link_url'] ? true : false);
				}
			}
		}
	}

	/* LINK FORUM TAB SELECTION */
	public static function linkForumTabId(XenForo_FrontController $fc, XenForo_ControllerResponse_Abstract &$controllerResponse, XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams)
	{
		if (self::$linkForum['nodeTabId'] AND empty($controllerResponse->containerParams['nodeTabId']))
		{
			// USED LATER FOR BREADCRUMBS
			$controllerResponse->containerParams['nodeTabId'] = self::$linkForum['nodeTabId'];
		}
		else
		{
			self::$linkForum['nodeRecord'] = array();
		}
	}

	public static function extendControllers($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Page')
			$extend[] = 'NodesAsTabs_ControllerPublic_Page';
		if ($class == 'XenForo_ControllerPublic_Forum')
			$extend[] = 'NodesAsTabs_ControllerPublic_Forum';
		if ($class == 'XenForo_ControllerPublic_Category')
			$extend[] = 'NodesAsTabs_ControllerPublic_Category';
		if ($class == 'XenForo_ControllerPublic_LinkForum')
			$extend[] = 'NodesAsTabs_ControllerPublic_LinkForum';
		if ($class == 'XenForo_ControllerPublic_Thread')
			$extend[] = 'NodesAsTabs_ControllerPublic_Thread';
		if ($class == 'XenForo_ControllerPublic_Post')
			$extend[] = 'NodesAsTabs_ControllerPublic_Post';



		if ($class == 'XenForo_ControllerAdmin_Page')
			$extend[] = 'NodesAsTabs_ControllerAdmin_Page';
		if ($class == 'XenForo_ControllerAdmin_Forum')
			$extend[] = 'NodesAsTabs_ControllerAdmin_Forum';
		if ($class == 'XenForo_ControllerAdmin_Category')
			$extend[] = 'NodesAsTabs_ControllerAdmin_Category';
		if ($class == 'XenForo_ControllerAdmin_LinkForum')
			$extend[] = 'NodesAsTabs_ControllerAdmin_LinkForum';



		if ($class == 'XenForo_ControllerAdmin_Option')
			$extend[] = 'NodesAsTabs_ControllerAdmin_Option';
	}

	public static function extendViewAdmin($class, array &$extend)
	{
		if ($class == 'XenForo_ViewAdmin_Node_List')
		{
			$extend[] = 'NodesAsTabs_ViewAdmin_Node_List';
		}
	}

	public static function categoryWarning($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'node_list')
		{
			$categoryOwnPage = XenForo_Application::get('options')->categoryOwnPage;
			$optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');
			$categoryTabExists = $optionsModel->categoryTabExists();

			if (!$categoryOwnPage AND $categoryTabExists)
			{
				$templateName = 'nat_node_list_warning';
			}
		}
	}

	public static function includeCSS($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'PAGE_CONTAINER')
		{
			if ($template instanceof XenForo_Template_Public)
			{
				$template->addRequiredExternal('css', 'nat_public_css');
			}
			else if ($template instanceof XenForo_Template_Admin)
			{
				$template->addRequiredExternal('css', 'nat_admin_css');
			}
		}
	}

	public static function breadCrumbs($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'PAGE_CONTAINER'
		AND $nodeTabId = $template->getParam('nodeTabId')
		)
		{
			$navigation = array();
			$origNavigation = $template->getParam('navigation');
			$origNavigation = ($origNavigation ? $origNavigation : array());

			/* LINK FORUM TAB SELECTION */
			if (self::$linkForum['nodeRecord'])
			{
				// REMOVE INTERMEDIATE NODE CRUMBS IF LINK-FORUM IS LINKING TO ANOTHER NODE
				foreach ($origNavigation AS $key => $crumb)
				{
					if (!empty($crumb['node_id']))
					{
						unset($origNavigation[$key]);
					}
				}

				$nodeModel = XenForo_Model::create('XenForo_Model_Node');

				$navigation = array_merge(
					$nodeModel->getNodeBreadCrumbs(self::$linkForum['nodeRecord'], true),
					$origNavigation
				);
			}
			else
			{
				$navigation = $origNavigation;
			}

			$foundTabCrumb = 0;
			foreach ($navigation AS $key => $crumb)
			{
				// -1 IS SET FOR MANUAL TAB ASSIGNMENTS FOR TOP LEVEL NODES
				// IT BASICALLY MEANS DON'T REMOVE ANY CRUMBS
				if (!$foundTabCrumb AND $nodeTabId != -1)
				{
					unset($navigation[$key]);
				}

				if (isset($crumb['node_id'])
				AND $crumb['node_id'] == $nodeTabId)
				{
					$foundTabCrumb = 1;
				}
			}

			$template->setParam('navigation', $navigation);
			$template->setParam('nodeTabId', 0);

			// TEMPORARILY REMOVE RELEVANT LISTENERS FOR RE-RENDER
			$listeners = XenForo_CodeEvent::getEventListeners('template_post_render');
			XenForo_CodeEvent::setListeners(array('template_post_render' => false));

			$content = $template->render();

			XenForo_CodeEvent::setListeners(array('template_post_render' => $listeners));
		}
	}

	public static function bodyJS($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if ($hookName == 'page_container_js_body')
		{
			$optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');
			$natCache = $optionsModel->getSimpleCacheData();

			$contents .= $template->create('nat_bodyjs', array(
				'nodeTabs' => $natCache['nodeTabs']
			))->render();
		}
	}

	public static function nodeOptions($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if (in_array($hookName, array(
			'admin_page_edit_basic_information',
			'forum_edit_basic_information',
			'admin_category_edit',
			'admin_link_forum_edit'
		)))
		{
			$nodeId = 0;
			$params = $template->getParams();
			if (!empty($params['page']['node_id']))
			{
				$nodeId = $params['page']['node_id'];
			}
			if (!empty($params['forum']['node_id']))
			{
				$nodeId = $params['forum']['node_id'];
			}
			if (!empty($params['category']['node_id']))
			{
				$nodeId = $params['category']['node_id'];
			}
			if (!empty($params['link']['node_id']))
			{
				$nodeId = $params['link']['node_id'];
			}

			$contents .= self::nodeOptionsRender($nodeId, $template);
		}
	}

	public static function nodeOptionsRender($nodeId = 0, XenForo_Template_Abstract $template)
	{
		return $template->create('nat_nodeoptions', array(
			'natOptions' => self::nodeOptionsRecord($nodeId)
		))->render();
	}

	public static function nodeOptionsRecord($nodeId = 0)
	{
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');

		// START BY SETTING DEFAULT OPTIONS
		$dw = XenForo_DataWriter::create('NodesAsTabs_DataWriter_Options');
		$natOptions = $dw->getFieldDefaults();

		// THEN POSSIBLY OVERRIDE THEM WITH EXISTING OPTIONS
		$node = $nodeModel->getNodeById($nodeId);
		if (!empty($node['node_id']))
		{
			$optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');
			$existing = $optionsModel->getOptionsById($node['node_id']);
			if (!empty($existing['node_id']))
			{
				$natOptions = $existing;
			}

			$natOptions['title'] = $node['title'];
		}

		return $natOptions;
	}
}