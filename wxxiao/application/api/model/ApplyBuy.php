<?php
namespace app\api\model;

use think\Model;
use app\api\service\Token as TokenService;

class ApplyBuy extends Model
{	
	public function goods()
    {
       return $this->hasMany('ApplyGoods','apply_id','apply_id');
    }

    //获取器的作用,很是强大啊
    public function getStaffIdAttr($value)
	{
		$staffs = model('Staff')->column('id,name');
		// $status = [76=>'删除',0=>'禁用',1=>'正常',2=>'待审核'];
		return $staffs[$value];
	}

    // 20181024新增$collect $type 判断审核收货的
	public static function getMyApply($is_admin,$type,$collect,$page = 1)
	{

		if(!$is_admin){
    		$staff_id = TokenService::getCurrentUid(); 
    		$where['staff_id'] = $staff_id;
    	}else{
    		$where['status'] = ['gt',0];
    	}
        if($collect){
            $where['status'] = ['in',[2,4]];
        }
        if($type){
            $where['type'] = $type;
        }
    	$res = self::where($where)->with(['goods'=>function($query){
    		$query->field('apply_id,goods_name,goods_apply,goods_unit,goods_remain');
    	}])->limit(($page-1)*10,10)->order('create_time desc')->select();
    	return $res;
	}
}