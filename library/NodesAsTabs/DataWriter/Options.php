<?php

class NodesAsTabs_DataWriter_Options extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'nat_options' => array(
				'node_id'		=> array('type' => self::TYPE_UINT, 'required' => true),
				'nat_display_tab'	=> array('type' => self::TYPE_UINT, 'required' => true),
				'nat_display_tabperms'	=> array('type' => self::TYPE_UINT, 'required' => true),
				'nat_tabtitle'		=> array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),
				'nat_display_order'	=> array('type' => self::TYPE_UINT, 'required' => true, 'default' => 1),
				'nat_position'		=> array('type' => self::TYPE_STRING, 'required' => true, 'allowedValues' => array('home','middle','end'), 'default' => 'end'),
				'nat_childlinks'	=> array('type' => self::TYPE_UINT, 'required' => true, 'default' => 0),
				'nat_childlinksperms'	=> array('type' => self::TYPE_UINT, 'required' => true),
				'nat_unreadcount'	=> array('type' => self::TYPE_UINT, 'required' => true),
				'nat_markread'		=> array('type' => self::TYPE_UINT, 'required' => true),
				'nat_linkstemplate'	=> array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),
				'nat_popup'		=> array('type' => self::TYPE_UINT, 'required' => true, 'default' => 1),
				'nat_popup_columns'	=> array('type' => self::TYPE_UINT, 'required' => true),
				'nat_tabid'		=> array('type' => self::TYPE_STRING, 'maxLength' => 50, 'default' => ''),
				'nat_childnodes'	=> array('type' => self::TYPE_STRING, 'default' => ''),
				'nat_firstchildnodes'	=> array('type' => self::TYPE_STRING, 'default' => '')
			)
		);
	}

	public function getFieldDefaults()
	{
		$fields = $this->_getFields();

		$retval = array();
		foreach ($fields AS $table)
		{
			foreach ($table AS $key => $field)
			{
				if (!empty($field['default']))
				{
					$retval[$key] = $field['default'];
				}
				else
				{
					$retval[$key] = '';
				}
			}
		}

		return $retval;
	}

	protected function _getExistingData($data)
	{
		if (!$nodeId = $this->_getExistingPrimaryKey($data, 'node_id', 'nat_options'))
		{
			return false;
		}

		return array('nat_options' => $this->_getOptionsModel()->getOptionsById($nodeId));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'node_id = ' . $this->_db->quote($this->getExisting('node_id'));
	}

	protected function _getOptionsModel()
	{
		return $this->getModelFromCache('NodesAsTabs_Model_Options');
	}
}