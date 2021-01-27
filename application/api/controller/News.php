<?php
// +----------------------------------------------------------------------
// | KyxsCMS [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2019 http://www.kyxscms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kyxscms
// +----------------------------------------------------------------------

namespace app\api\controller;
use think\Controller;
use think\Db;
use org\Oauth;

class News extends Controller{

	protected $beforeActionList = [
        'checkDeduct'  =>  ['only'=>'content']
    ];

    protected function checkDeduct(){
    	$api_key=$this->request->param('api_key');
    	if(empty($key)){
    		$user_ip=$this->request->ip();
	    	$allow_ip=['127.0.0.1','localhost'];
	    	if(in_array($user_ip,$allow_ip)){
	    		return false;
	    	}
	        $oauth = new Oauth();
			$check_deduct=$oauth->checkDeduct('news');
			$check_deduct=json_decode($check_deduct,true);
			if($check_deduct['code']!=1){
				echo json_encode($check_deduct);
				exit;
			}
    	}else{
    		if(Config::get('web.api_key')==$key){
    			return false;
    		}
    		exit;
    	}
    }

	public function category($cid=false,$type=1,$filter=false){
		$category=model('api/api')->category($cid,$type,$filter);
		return json($category);
	}

	public function lists($cid=false,$order='update_time desc',$limit=20,$pos=false,$time=false,$paginator=1,$id=null){
		$api=model('common/api');
		$api->api_url=true;
		$list=$api->get_news($cid,$order,$limit,$pos,$time,$paginator,$id);
		return json($list);
	}

	public function content($id,$order='id asc',$limit='',$page=false){
		$api=model('common/api');
		$api->api_url=true;
		$news=$api->news_detail($id);
		return json($news);
	}
}