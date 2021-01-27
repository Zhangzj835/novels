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

namespace app\user\model;

use think\Model;
use think\facade\Request;
use think\facade\Config;
use think\facade\Session;
use think\facade\Cookie;
use captcha\Captcha;
use app\user\validate\User as UserValidate;

class User extends Model
{

	protected $insert = ['password','status'=>1];

    protected $autoWriteTimestamp = true;

    protected function setPasswordAttr($value){
        return think_ucenter_md5($value);
    }

	public function get_info($id=UID,$field = true){
		$map=['status'=>1,'id'=>$id];
		$data=User::where($map)->field($field)->find();
		return $data;
	}

	public function check_require($field,$value){
		if(User::where($field,$value)->value('id')){
			return true;
		}else{
			return false;
		}
	}

	public function login($username, $password){
    	$data=Request::post();
        $validate = new UserValidate;
        if(!$this->checkCode($data,'login')){
            $this->error='验证码错误！';
            return false;
        }
        if (!$validate->scene('login')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $map=['username'=>$username,'status'=>1];
		$user = User::where($map)->find();
		if($user){
			if(think_ucenter_md5($password) === $user['password']){
                $autologin=empty($data['autologin'])?0:$data['autologin'];
				$this->auto_login($user,$autologin);
				return true;
			} else {
				$this->error = '密码错误！';
				return false;
			}
		} else {
			$this->error = '用户不存在或被禁用！';
			return false;
		}
	}

	public function auto_login($user,$autologin=1){
        $data = [
            'login'           => ['inc', 1],
            'login_time' => Request::time(),
            'login_ip'   => Request::ip(1)
        ];
        User::where('id', $user['id'])->update($data);
        $auth = [
            'uid'             => $user['id'],
            'username'        => $user['username']
        ];
        if($autologin==1){
            Cookie::forever('user_auth', $auth);
            Cookie::forever('user_auth_sign', data_auth_sign($auth));
        }else{
            Cookie::set('user_auth', $auth);
            Cookie::set('user_auth_sign', data_auth_sign($auth));
        }
    }

    public function logout(){
        cookie('user_auth', null);
        cookie('user_auth_sign', null);
    }

    public function reg($data){
        $validate = new UserValidate;
        if(!$this->checkCode($data,'login')){
            $this->error='验证码错误！';
            return false;
        }
        if (!$validate->scene('reg')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $data['headimgurl']=substr(Config::get('web.upload_path'),1).'user/head/user-icon.png';
        $data['integral']=Config::get('web.user_reg_integral');
        $result = User::allowField(true)->create($data);
        if(false === $result){
            $this->error=User::getError();
            return false;
        }else{
            return $result;
        }
    }

    public function edit(){
        $data=Request::post();
        $validate = new UserValidate;
        if (!$validate->scene('edit')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $result = User::allowField(true)->save($data,['id'=>UID]);
        if(false === $result){
            $this->error=User::getError();
            return false;
        }else{
            return $result;
        }
    }

    public function password(){
        $data=Request::post();
        $validate = new UserValidate;
        if (!$validate->scene('password')->check($data)) {
            $this->error=$validate->getError();
            return false;
        }
        $data['password']=think_ucenter_md5($data['newpassword']);
        $result = User::allowField(true)->save($data,['id'=>UID]);
        if(false === $result){
            $this->error=User::getError();
            return false;
        }
        return $result;
    }

    public function forgetpwd(){
        $password=Request::post('newpassword');
        $forget_pwd=Session::get('forget_pwd');
        $map['id']=$forget_pwd['uid'];
        $map['status']=1;
        User::where($map)->update(['password'=>think_ucenter_md5($password)]);
        Session::pull('forget_pwd');
    }

    public function crop_img($params){
        $params = explode(',', $params);
        $Image=\image\Image::open(Config::get('web.upload_path').'user/head/user_haed_'.UID.'.png');
        $headimg=Config::get('web.upload_path').'user/head/'.uniqid().'.png';
        $Image->crop($params[2],$params[3],$params[0],$params[1])->save($headimg);
        $headimg=substr($headimg, 1);
        $headimgurl=User::where('id',UID)->value('headimgurl');
        if($headimgurl!= substr(Config::get('web.upload_path'), 1).'user/head/user-icon.png'){
            @unlink(".".$headimgurl);
        }
        @unlink(Config::get('web.upload_path').'user/head/user_haed_'.UID.'.png');
        User::where('id',UID)->setField('headimgurl',$headimg);
        return $headimg;
    }

    protected function checkCode($value,$rule){
        if(Config::get('web.user_reg_verify')!=1 && $rule=='reg'){
            return true;
        }
        if(Config::get('web.user_login_verify')!=1  && $rule=='login'){
            return true;
        }
        if(empty($value['code'])){
            return false;
        }
        $captcha = new Captcha();
        return $captcha->check($value['code']);
    }
}