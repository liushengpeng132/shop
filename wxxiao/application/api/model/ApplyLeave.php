<?php
namespace app\api\model;

use think\Model;
use app\api\service\Token as TokenService;

class ApplyLeave extends Model
{	

    //获取器的作用,很是强大啊
    public function getStaffIdAttr($value)
	{
		$staffs = model('Staff')->column('id,name');
		// $status = [76=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
		return $staffs[$value];
	}

    // 获取所有请假申请
	public static function getMyApply($is_admin,$page = 1)
	{
		if(!$is_admin){
    		$staff_id = TokenService::getCurrentUid(); 
    		$where['staff_id'] = $staff_id;
    	}else{
    		$where['status'] = ['gt',0];
    	}
    	$res = model('ApplyLeave')->where($where)->field('id,staff_id,create_time,status')->limit(($page-1)*10,10)->order('create_time desc')->select();
    	return $res;
	}
}