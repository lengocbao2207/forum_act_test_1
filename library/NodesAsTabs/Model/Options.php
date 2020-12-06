<?php

class NodesAsTabs_Model_Options extends XenForo_Model
{
	public function getAllTabs()
	{
		return $this->_getDb()->fetchAll("
			SELECT options.*, node.*
			FROM nat_options AS options
			INNER JOIN xf_node AS node
				ON (node.node_id = options.node_id)
			WHERE nat_display_tab = 1
			OR nat_tabid != ''
			ORDER BY options.nat_display_order
			ASC
		");
	}

	public function getOptionsById($nodeId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM nat_options
			WHERE node_id = ?
		', $nodeId);
	}

	public function getJoinedOptionsForNav($permComboId)
	{
		return $this->_getDb()->fetchAll("
			SELECT options.*, node.*,
				permission.cache_value AS node_permission_cache
			FROM nat_options AS options
			INNER JOIN xf_node AS node
				ON (node.node_id = options.node_id)
			LEFT JOIN xf_permission_cache_content AS permission
				ON (permission.permission_combination_id = ?
				AND permission.content_type = 'node'
				AND permission.content_id = options.node_id)
			WHERE nat_display_tab = 1
			ORDER BY options.nat_display_order
			ASC
		", $permComboId);
	}

	public function getJoinedChildrenForNav(array $nodeIds, $permComboId)
	{
		$db = $this->_getDb();

		return $db->fetchAll("
			SELECT node.*,
				permission.cache_value AS node_permission_cache
			FROM xf_node AS node
			LEFT JOIN xf_permission_cache_content AS permission
				ON (permission.permission_combination_id = ?
				AND permission.content_type = 'node'
				AND permission.content_id = node.node_id)
			WHERE node.node_id IN (" . $db->quote($nodeIds) . ")
			ORDER BY node.lft
			ASC
		", $permComboId);
	}

	public function getEligibleRootNodes()
	{
		return $this->fetchAllKeyed("
			SELECT *
			FROM nat_options
			WHERE nat_display_tab = 1
		", 'node_id');
	}

	public function categoryTabExists()
	{
		return $this->_getDb()->fetchOne("
			SELECT options.node_id
			FROM nat_options AS options
			LEFT JOIN xf_node AS node ON (node.node_id = options.node_id)
			WHERE node.node_type_id = 'Category'
			AND node.depth = 0
			AND options.nat_display_tab = 1
		");
	}

	public function getTabLabelsForNodeTree()
	{
		$records = $this->_getDb()->fetchAll("
			SELECT *
			FROM nat_options
			WHERE nat_display_tab = 1
			OR nat_tabid != ''
		");

		$nodeIds = array();
		foreach ($records AS $record)
		{
			$nodeIds[$record['node_id']] = $record;
		}

		return $nodeIds;
	}

	public function isTab($nodeId = 0, &$manual = '')
	{
		if (!$nodeId)
		{
			return false;
		}

		$natCache = $this->getSimpleCacheData();

		if (!isset($natCache['nodeTabs']))
		{
			return false;
		}

		$parentTab = 0;
		$depth = 0;

		foreach ($natCache['nodeTabs'] AS $nodeTab)
		{
			$this->rootify($nodeTab, false);

			if ($nodeId == $nodeTab['node_id']
			OR in_array($nodeId, explode(',', $nodeTab['nat_childnodes']))
			)
			{
				if (!$parentTab OR $nodeTab['depth'] > $depth)
				{
					$depth = $nodeTab['depth'];

					if ($nodeTab['nat_display_tab'])
					{
						$parentTab = $nodeTab['node_id'];
						$manual = '';
					}
					// MANUAL TAB ASSIGNMENT
					else
					{
						// SET PARENT SO THIS BREADCRUMB ISN'T REMOVED
						// NORMALLY WOULD RETURN ID OF NODE TAB, BUT THIS IS MANUAL ASSIGNMENT
						$parentTab = ($nodeTab['parent_node_id'] ? $nodeTab['parent_node_id'] : -1);
						$manual = $nodeTab['nat_tabid'];
					}
				}
			}
		}

		// DISABLE ROOT TAB SELECTION
		if ($parentTab == XenForo_Application::get('options')->natrootNode
		AND !XenForo_Application::get('options')->natrootNodeSelect
		)
		{
			$parentTab = 0;
		}

		// USE MANUAL ASSIGNMENT SYSTEM FOR A SELECTED ROOT TAB SO THAT BREADCRUMBS ARE NOT REMOVED
		if ($parentTab == XenForo_Application::get('options')->natrootNode
		AND XenForo_Application::get('options')->natrootNodeSelect
		)
		{
			$manual = 'nodetab' . $parentTab;
			$parentTab = -1;
		}

		return $parentTab;
	}

	public function handleRoute($nodeId, $routeMatch)
	{
		$manual = '';
		$parentTab = 0;

		// CHECK IF NODE IS IN A TAB
		if ($parentTab = $this->isTab($nodeId, $manual))
		{
			if ($manual)
			{
				// SET NEW MAJOR SECTION
				// USED INTERNALLY FOR NAV TAB SELECTION
				$routeMatch->setSections($manual);

				return $parentTab;
			}
			else
			{
				// SET NEW MAJOR SECTION
				// USED INTERNALLY FOR NAV TAB SELECTION
				$routeMatch->setSections('nodetab' . $parentTab);

				return $parentTab;
			}
		}

		return 0;
	}

	public function getNodeIdFromRequest($request)
	{
		if ($nodeId = $request->getParam('node_id'))
		{
			return $nodeId;
		}
		else if ($nodeName = $request->getParam('node_name'))
		{
			$nodeId = $this->_getDb()->fetchOne("
				SELECT node_id
				FROM xf_node
				WHERE node_name = ?
			", $nodeName);

			return $nodeId;
		}
		else if ($threadId = $request->getParam('thread_id'))
		{
			$nodeId = $this->_getDb()->fetchOne("
				SELECT node_id
				FROM xf_thread
				WHERE thread_id = ?
			", $threadId);

			return $nodeId;
		}
		else if ($postId = $request->getParam('post_id'))
		{
			$nodeId = $this->_getDb()->fetchOne("
				SELECT thread.node_id
				FROM xf_post AS post
				LEFT JOIN xf_thread AS thread ON (thread.thread_id = post.thread_id)
				WHERE post.post_id = ?
			", $postId);

			return $nodeId;
		}

		return 0;
	}

	// PERFORM ROOT CHANGES TO NODE RECORD
	// BE MINDFUL OF USAGE
	public function rootify(&$node, $doExtras = true)
	{
		if (!$rootNodeId = XenForo_Application::get('options')->natrootNode)
		{
			return;
		}

		if ($rootNodeId == $node['node_id'])
		{
			$node['depth'] = -1;

			if ($doExtras)
			{
				$node['node_id'] = 0;

				$node['lft'] = $this->_getDb()->fetchOne("
					SELECT MIN(lft) - 1
					FROM xf_node
				");
				$node['rgt'] = $this->_getDb()->fetchOne("
					SELECT MAX(rgt) + 1
					FROM xf_node
				");
			}
		}
	}

	// RETURNS A COMMA LIST OF NODEIDS
	public function buildChildList($nodeId)
	{
		$childNodes = array();
		$list = array();
		$childList = '';
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');

		if ($nodeId == XenForo_Application::get('options')->natrootNode)
		{
			$childNodes = $nodeModel->getAllNodes();
		}
		else
		{
			$childNodes = $nodeModel->getChildNodesForNodeIds(array($nodeId));
		}

		// IF THERE IS A ROOT NODE THEN UNSET IT FROM CHILDREN
		if ($rootNodeId = XenForo_Application::get('options')->natrootNode
		AND isset($childNodes[$rootNodeId])
		)
		{
			unset($childNodes[$rootNodeId]);
		}

		foreach ($childNodes AS $key => $child)
		{
			$list[] = $key;
		}
		$childList = implode(',', $list);

		return $childList;
	}

	// RETURNS NODE INFO OF FIRST CHILDREN NECESSARY FOR CREATING LINKS
	public function buildFirstChildList($nodeId, $childDepth)
	{
		$list = array();
		$childList = '';

		$nodeModel = XenForo_Model::create('XenForo_Model_Node');

		$curNode = $nodeModel->getNodeById($nodeId);
		$this->rootify($curNode, true);
		$firstChildren = $nodeModel->getChildNodesToDepth($curNode, $childDepth);

		// IF THERE IS A ROOT NODE THEN UNSET IT FROM CHILDREN
		if ($rootNodeId = XenForo_Application::get('options')->natrootNode
		AND isset($firstChildren[$rootNodeId])
		)
		{
			unset($firstChildren[$rootNodeId]);
		}

		if (is_array($firstChildren))
		foreach ($firstChildren AS $child)
		{
			$list[] = $child;
		}
		if (isset($list[0]))
		{
			$childList = serialize($list);
		}

		return $childList;
	}

	public function rebuildCache($nodeId = 0, $childDepth = 0)
	{
		if (!$nodeId)
		{
			// VALIDATE ROOT NODE OPTION AND RESET IF INVALID
			if ($rootNodeId = XenForo_Application::get('options')->natrootNode)
			{
				$possibleRootNodes = $this->getEligibleRootNodes();

				if (!isset($possibleRootNodes[$rootNodeId]))
				{
					$xfOptionModel = XenForo_Model::create('XenForo_Model_Option');

					$xfOptionModel->updateOptions(array('natrootNode' => 0));

					$data['options'] = XenForo_Model::create('XenForo_Model_Option')->rebuildOptionCache();

					$options = new XenForo_Options($data['options']);
					$options->__set('natrootNode', 0);
					XenForo_Application::setDefaultsFromOptions($options);
					XenForo_Application::set('options', $options);
				}
			}

			$nodes = $this->_getDb()->fetchAll("
				SELECT node_id, nat_childlinks
				FROM nat_options
				WHERE nat_display_tab = 1
				OR nat_tabid != ''
			");

			foreach ($nodes AS $node)
			{
				$this->rebuildCache($node['node_id'], $node['nat_childlinks']);
			}

			$nodeTabs = $this->getAllTabs();
			$natCache = array(
				'checkTabPerms' => false,
				'nodeTabs' => $nodeTabs,
				'linkForumSelect' => array()
			);
			$db = $this->_getDb();
			foreach ($nodeTabs AS $nodeTab)
			{
				if ($nodeTab['nat_display_tabperms'] AND $nodeTab['nat_display_tab'])
				{
					$natCache['checkTabPerms'] = true;
				}

				/* LINK FORUM TAB SELECTION */
				$linkForums = $db->fetchAll("
					SELECT *
					FROM xf_link_forum AS lf
					LEFT JOIN xf_node AS n ON (n.node_id = lf.node_id)
					WHERE " . ($nodeTab['nat_childnodes'] ? "lf.node_id IN ({$nodeTab['nat_childnodes']})" : "0") . "
					OR lf.node_id = ?
				", $nodeTab['node_id']);

				foreach ($linkForums AS $linkForum)
				{
					// IF IT'S A RELATIVE TARGET
					// CONDITIONS TAKEN FROM XenForo_Link::convertUriToAbsoluteUri
					if ($linkForum['link_url'] != '.'
					AND substr($linkForum['link_url'], 0, 1) != '/'
					AND !preg_match('#^[a-z0-9-]+://#i', $linkForum['link_url'])
					)
					{
						$natCache['linkForumSelect'][$linkForum['node_id']] = $linkForum;
					}
				}
			}

			XenForo_Application::setSimpleCacheData('natCache', $natCache);
		}
		else
		{
			$nodeData = array();

			$nodeData['node_id'] = $nodeId;
			$nodeData['nat_childnodes'] = $this->buildChildList($nodeId);
			$nodeData['nat_firstchildnodes'] = $this->buildFirstChildList($nodeId, $childDepth);

			$this->saveOptions($nodeData);
		}
	}

	public function deleteOrphans()
	{
		$nodeIds = $this->_getDb()->fetchCol("
			SELECT options.node_id
			FROM nat_options AS options
			LEFT JOIN xf_node AS node ON (node.node_id = options.node_id)
			WHERE node.node_id IS NULL
		");

		foreach ($nodeIds AS $nodeId)
		{
			$this->deleteOptions($nodeId);
		}
	}

	public function getSimpleCacheData()
	{
		$natCache = XenForo_Application::getSimpleCacheData('natCache');

		if (!$natCache)
		{
			$this->deleteOrphans();
			$this->rebuildCache();

			$natCache = XenForo_Application::getSimpleCacheData('natCache');
		}

		return $natCache;
	}

	public function deleteOptions($nodeId)
	{
		if (!$nodeId)
		{
			return;
		}

		$existing = $this->getOptionsById($nodeId);
		if (!empty($existing['node_id']))
		{
			$dw = XenForo_DataWriter::create('NodesAsTabs_DataWriter_Options');
			$dw->setExistingData($existing['node_id']);
			$dw->delete();
		}
	}

	public function canViewNode(array $node, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReferenceForNode($node['node_id'], $viewingUser, $nodePermissions);

		return XenForo_Permission::hasContentPermission($nodePermissions, 'view');
	}

	public function buildLink($node)
	{
		$retval = '';

		$nodeModel = $this->getModelFromCache('XenForo_Model_Node');
		$nodeTypes = $nodeModel->getAllNodeTypes();

		if (isset($nodeTypes[$node['node_type_id']]['public_route_prefix']))
		{
			$retval = XenForo_Link::buildPublicLink('full:' . $nodeTypes[$node['node_type_id']]['public_route_prefix'], $node);
		}

		return $retval;
	}

	// BASED ON XenForo_Model_Forum::getUnreadThreadCountInForum
	// THREADS ARE THE ONLY THING TO COUNT FOR THE DEFAULT NODE TYPES
	// ADDONS THAT ADD NEW NODE TYPES CAN EXTEND THIS FUNCTION, COUNT THEIR OWN UNREAD CONTENT, AND ADD IT TO THE UNREAD COUNT THAT IS RETURNED
	public function getUnreadCount($nodeIds, $userId, $ignored = false)
	{
		if (!$userId)
		{
			return false;
		}

		if ($ignored && is_string($ignored))
		{
			$ignored = unserialize($ignored);
			$ignored = array_keys($ignored);
		}

		$db = $this->_getDb();
		$visitor = XenForo_Visitor::getInstance();
		$threadModel = XenForo_Model::create('XenForo_Model_Thread');

		$autoReadDate = XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);

		$unreadThreads = $db->fetchAll('
			SELECT thread.*, permission.cache_value AS node_permission_cache
			FROM xf_thread AS thread


			LEFT JOIN xf_permission_cache_content AS permission
				ON (permission.permission_combination_id = ?
				AND permission.content_type = \'node\'
				AND permission.content_id = thread.node_id)


			LEFT JOIN xf_thread_read AS thread_read ON
				(thread_read.thread_id = thread.thread_id AND thread_read.user_id = ?)
			LEFT JOIN xf_forum_read AS forum_read ON
				(forum_read.node_id = thread.node_id AND forum_read.user_id = ?)
			WHERE thread.node_id IN (' . $db->quote($nodeIds) . ')
				AND (thread.last_post_date > ' . strval($autoReadDate) . ' AND (forum_read.forum_read_date IS NULL OR thread.last_post_date > forum_read.forum_read_date))
				AND (thread_read.thread_id IS NULL OR thread.last_post_date > thread_read.thread_read_date)
				' . ($ignored ? 'AND thread.user_id NOT IN (' . $db->quote($ignored) . ')' : '') . '
				AND thread.discussion_state = \'visible\'
				AND thread.discussion_type <> \'redirect\'
		', array($visitor['permission_combination_id'], $userId, $userId));

		$count = 0;
		foreach ($unreadThreads AS $thread)
		{
			$visitor->setNodePermissions($thread['node_id'], $thread['node_permission_cache']);

			if ($threadModel->canViewThreadAndContainer($thread, array('node_id' => $thread['node_id'])))
			{
				$count++;
			}
		}

		return $count;
	}

	public function saveOptions(array $node)
	{
		if (!$node['node_id'])
		{
			return;
		}

		$dw = XenForo_DataWriter::create('NodesAsTabs_DataWriter_Options');

		$existing = $this->getOptionsById($node['node_id']);
		if (!empty($existing['node_id']))
		{
			$dw->setExistingData($existing['node_id']);
		}

		$dw->bulkSet($node);

		$dw->save();
	}
}