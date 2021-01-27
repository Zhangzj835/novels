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

namespace app\api\model;
use think\Model;
use think\facade\Request;

class Api extends Model{
    public function category($cid,$type,$filter){
        $category=model('common/api')->get_nav($cid,$type,$filter,false,Request::param('cid'),'id,title,pid,icon,type');
        foreach ($category as $key => $value) {
            $class[$key]=$value;
            if($value['branch']==1){
                $class[$key]['subor']=$this->category($value['id'],$type,$filter);
            }
        }
        return $class;
    }
}