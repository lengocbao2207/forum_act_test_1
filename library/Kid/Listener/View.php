<?php
class Kid_Listener_View
{
  public static function view($class, array &$extend)
  {
    switch ($class)
    {
		case 'XenForo_ViewPublic_Forum_View':
        $extend[] = 'Kid_ViewPublic_Forum';
        break;		
    }
  }
  
	public static function tab(array &$extraTabs, $selectedTabId)
	{
	$extraTabs['createthread'] = array(
				'title' => new XenForo_Phrase('create_thread'),
				'href' => XenForo_Link::buildPublicLink('full:createthread'),
				'position' => 'middle',
			);
  }
}