<?php 
class VietXf_MoreThread_Model_MoreThread extends XenForo_Model{
	public function getListMoreThread($forumid, $limit, $lenght_title, $time_format, $orderby){
		switch($orderby){
			case 'post_date': $keite_ord = 'post_date DESC'; break;
			case 'random': $keite_ord = 'RAND() DESC'; break;
			case 'view_count': $keite_ord = 'view_count DESC'; break;
		}
		
		$data = array();
		$db = XenForo_Application::get('db');
		$data = $db->fetchAll("
			SELECT thread_id, title, last_post_id , post_date 
			FROM `xf_thread` 
			WHERE node_id='{$forumid}' 
			ORDER BY $keite_ord 
			LIMIT 0, $limit 
		");
		foreach($data as $key=>$dt){
			$dt['title'] = $this->subString($dt['title'],$lenght_title);
			if($time_format == 'none'){
				unset($dt['post_date']);
			}else{
				$dt['post_date'] = date($time_format, $dt['post_date']);
			}
			$data[$key] = $dt;
		}
		return $data;
	}
	private function subString($str, $len, $charset='UTF-8'){
		$str = html_entity_decode($str, ENT_QUOTES, $charset);
		if(mb_strlen($str, $charset)> $len){
			$arr = explode(' ', $str);
			$str = mb_substr($str, 0, $len, $charset);
			$arrRes = explode(' ', $str);
			$last = $arr[count($arrRes)-1];
			unset($arr);
			if(strcasecmp($arrRes[count($arrRes)-1], $last))
			{
				unset($arrRes[count($arrRes)-1]);
			}
		return implode(' ', $arrRes)." ...";
		}
		return $str;
	}
}
?>