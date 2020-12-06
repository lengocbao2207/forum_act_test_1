<?php
class VietXf_MoreThread_ControllerPublic_Thread extends XFCP_VietXf_MoreThread_ControllerPublic_Thread{
	public function actionIndex(){
		$response = parent::actionIndex();
		$options = XenForo_Application::get('options');
		if($options->vietxf_enable_disable == 1){
			if ($response instanceof XenForo_ControllerResponse_View){
				$morethread['samecat'] = $this->getModelFromCache('VietXf_MoreThread_Model_MoreThread')->getListMoreThread($response->params['forum']['node_id'], $options->vietxf_limit_thread, $options->vietxf_morethread_lenght_title, $options->vietxf_morethread_timepost, $options->vietxf_order_by);
				$response->params += array(
					'morethread' => $morethread
				);
			}
		}
       	return $response;
	}
}
?>