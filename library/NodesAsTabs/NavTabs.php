<?php

class NodesAsTabs_NavTabs
{
	public static function createNodeTabs(array &$extraTabs, $selectedTabId)
	{
		// STUFF
		$optionsModel = XenForo_Model::create('NodesAsTabs_Model_Options');
		$visitor = XenForo_Visitor::getInstance();
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');

		// FORCED ORDERS
		$forceOrder = array(
			'home' => XenForo_Application::get('options')->natforceHome,
			'middle' => XenForo_Application::get('options')->natforceMiddle,
			'end' => XenForo_Application::get('options')->natforceEnd
		);
		$forceTemp = array();

		$natCache = $optionsModel->getSimpleCacheData();

		// GET ALL TABS
		if (isset($natCache['checkTabPerms']) AND $natCache['checkTabPerms'])
		{
			$nodeTabs = $optionsModel->getJoinedOptionsForNav($visitor['permission_combination_id']);
		}
		else if (isset($natCache['nodeTabs']))
		{
			$nodeTabs = $natCache['nodeTabs'];
		}
		else
		{
			$nodeTabs = array();
		}

		// GO THROUGH EACH TAB
		foreach ($nodeTabs AS $nodeTab)
		{
			// MIGHT NOT BE A NEW TAB, COULD BE MANUAL TAB ASSIGNMENT
			if (!$nodeTab['nat_display_tab'])
			{
				continue;
			}

			// PERMISSION CHECK
			if ($nodeTab['nat_display_tabperms'] AND isset($nodeTab['node_permission_cache']))
			{
				$visitor->setNodePermissions($nodeTab['node_id'], $nodeTab['node_permission_cache']);
				if (!$optionsModel->canViewNode($nodeTab))
				{
					continue;
				}
			}

			$childLinks = '';
			$linksTemplate = '';

			// DO CHILD LINKS
			if ($nodeTab['nat_childlinks'] AND $nodeTab['nat_firstchildnodes'])
			{
				$firstChildren = unserialize($nodeTab['nat_firstchildnodes']);
				$firstChildNodes = array();

				// IF WE ARE CHECKING PERMISSIONS FOR CHILD LINKS
				if ($nodeTab['nat_childlinksperms'])
				{
					$ids = array();
					foreach ($firstChildren AS $child)
					{
						$ids[] = $child['node_id'];
					}
					$records = $optionsModel->getJoinedChildrenForNav($ids, $visitor['permission_combination_id']);

					foreach ($records AS $record)
					{
						// IF THEY HAVE PERMISSION TO VIEW THE CHILD NODE
						$visitor->setNodePermissions($record['node_id'], $record['node_permission_cache']);
						if ($optionsModel->canViewNode($record))
						{
							$firstChildNodes[] = $record;
						}
					}
				}
				else
				{
					$firstChildNodes = $firstChildren;
				}

				// SET LEVEL OF CHILDREN
				// CAN BE USED FOR MULTILEVEL CHILDREN
				// ALSO PREPARE SUBMENUS
				$lastTopChildKey = 0;
				$lastTopChildLft = 0;
				$lastTopChildRgt = 0;
				// ROOTIFY FOR CHILD LINK OPERATIONS
				$origNodeTab = $nodeTab;
				$optionsModel->rootify($nodeTab, false);
				foreach ($firstChildNodes AS $key => &$childNode)
				{
					$childNode['level'] = $childNode['depth'] - $nodeTab['depth'];

					if ($childNode['level'] == 1)
					{
						$lastTopChildKey = $key;
						$lastTopChildLft = $childNode['lft'];
						$lastTopChildRgt = $childNode['rgt'];

						$childNode['submenu'] = array();
					}

					// IF CHILD IS DEEP AND IT IS WITHIN LAST TOP CHILD
					if ($childNode['level'] > 1 AND $childNode['lft'] > $lastTopChildLft AND $childNode['rgt'] < $lastTopChildRgt)
					{
						// PUT UNDER SUBMENU
						$firstChildNodes[$lastTopChildKey]['submenu'][] = $childNode;
					}
				}
				$nodeTab = $origNodeTab;

				$template = new XenForo_Template_Public('nat_childlinks', array(
					'firstChildNodes' => $firstChildNodes,
					'selected' => $selectedTabId == 'nodetab' . $nodeTab['node_id'],
					'nodeTab' => $nodeTab,
					'nodeTypes' => $nodeModel->getAllNodeTypes()
				));
				$childLinks = $template->render();
			}

			// DO LINKS TEMPLATE
			if ($nodeTab['nat_linkstemplate'])
			{
				$linksTemplate = $nodeTab['nat_linkstemplate'];
			}
			else if ($childLinks)
			{
				$linksTemplate = 'nat_linkstemplate';
			}
			else if ($nodeTab['nat_markread'] AND $selectedTabId == 'nodetab' . $nodeTab['node_id'])
			{
				$linksTemplate = 'nat_linkstemplate';
			}

			// POSSIBLY REMOVE LINKS TEMPLATE
			if (!$nodeTab['nat_popup'] AND $selectedTabId != 'nodetab' . $nodeTab['node_id'])
			{
				$linksTemplate = '';
			}

			// DO UNREAD COUNT
			$unreadCount = 0;
			if ($nodeTab['nat_unreadcount'])
			{
				$nodeIds = array();
				if ($nodeTab['nat_childnodes'])
				{
					foreach(explode(',', $nodeTab['nat_childnodes']) AS $nodeId)
					{
						$nodeIds[] = intval($nodeId);
					}
				}
				$nodeIds[] = $nodeTab['node_id'];

				$ignored = ($visitor['user_id'] ? $visitor['ignored'] : false);

				$unreadCount = $optionsModel->getUnreadCount($nodeIds, $visitor['user_id'], $ignored);
			}

			if ($forceOrder[$nodeTab['nat_position']])
			{
				// PUT IN TEMP ARRAY FOR MERGE LATER
				$forceTemp['nodetab' . $nodeTab['node_id']] = array(
					'title' => ($nodeTab['nat_tabtitle'] ? $nodeTab['nat_tabtitle'] : $nodeTab['title']),
					'href' => $optionsModel->buildLink($nodeTab),
					'position' => $nodeTab['nat_position'],
					'linksTemplate' => $linksTemplate,
					'childLinks' => $childLinks,
					'nodeTab' => $nodeTab,
					'selected' => $selectedTabId == 'nodetab' . $nodeTab['node_id'],
					'counter' => $unreadCount
				);
			}
			else
			{
				// ADD A NAV TAB FOR THIS NODE
				$extraTabs['nodetab' . $nodeTab['node_id']] = array(
					'title' => ($nodeTab['nat_tabtitle'] ? $nodeTab['nat_tabtitle'] : $nodeTab['title']),
					'href' => $optionsModel->buildLink($nodeTab),
					'position' => $nodeTab['nat_position'],
					'linksTemplate' => $linksTemplate,
					'childLinks' => $childLinks,
					'nodeTab' => $nodeTab,
					'selected' => $selectedTabId == 'nodetab' . $nodeTab['node_id'],
					'counter' => $unreadCount
				);
			}
		}

		$extraTabs = array_merge($forceTemp, $extraTabs);
	}
}