<?php

class Kid_Listener_Model
{
  public static function model($class, array &$extend)
  {
    switch ($class)
    {
      case 'XenForo_Model_Thread':
        $extend[] = 'Kid_Model_Thread';
        break;
    }
  }
  
	public static function init(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		//Get the static variable $helperCallbacks and add a new item in the array.
		XenForo_Template_Helper_Core::$helperCallbacks += array(
			'kidexplode' => array('Kid_Helper', 'kidexplode')
		);
	}
}