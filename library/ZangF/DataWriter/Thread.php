<?php 

class ZangF_DataWriter_Thread extends XFCP_ZangF_DataWriter_Thread
{
	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_thread']['z_thumb'] = array('type' => self::TYPE_STRING, 'default' => '');
		//$fields['xf_thread']['price'] = array('type' => self::TYPE_UINT, 'default' => '0');
		return $fields;
	}
	
}