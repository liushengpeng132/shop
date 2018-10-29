<?php

namespace app\api\controller\v1;

use app\api\service\Token as TokenService;
use app\api\controller\BaseController;
use app\lib\enum\GoodsClassEnum;
use app\api\model\ApplyLeave as ApplyLeaveModel;

class ApplyLeave extends BaseController
{

    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'getGoodsByStoreId']
    ];


    public function is_admin()
    {
        $wx_scope = model('Staff')->where('id','=',$this->staff_id)->value('wx_scope');
    	if($wx_scope == 16){
    		$is_admin = true;
    	}else{
    		$is_admin = false;
    	}
    	return $is_admin;

    }

    /**
     * 提交申请
     * @return [type] [description]
     */
    public function submitApplyLeave()
    {
        $staff_id = TokenService::getCurrentUid(); 
        $post =  input('post.products/a');
        $post['staff_id'] = $staff_id;
        $post['create_time'] = time();
        $post['status'] = 1;
        // dump($post);exit;
        $model = model('ApplyLeave')->create($post,true);
        $apply_id = $model->id;
        //怎么确认哪些人是管理层,得有个方法,写入管理层的openid到员工表
        //然后通过权限获取所有的管理员
        // 申请完成,发送提示信息给管理层
        //如果多个管理员怎么办
        //这个为申请员工的姓名
        $staff_name = model('Staff')->where('id','=',$staff_id)->value('name');
        $admins = model('Staff')->where('public_openid','neq','')->where('wx_scope','=',16)->column('public_openid');
        foreach ($admins as $v) {
            $res = $this->setNews($apply_id,$staff_name,$v,3);
        }
        
        if(!$res){
        	return [
        		'status'=>-1,
        		'msg'=>'通知失败,请自行通知审批人员'
        	];
        }
       	return [
       		'status'=>1,
       		'msg'=>'成功'
       ];
    }


    public function deleteApplyById($apply_id)
    {
    	$where['apply_id'] = $apply_id;
    	$where['status'] = 1;
    	$res = model('ApplyBuy')->where($where)->delete();
    	if($res == 1){
    		return [
    			'status'=>1,
    			'msg'=>'成功'
    		];
    	}
    }

    // 审核请假
	public function checkApply()
    {
        $post = input('post.');
        $condi['id'] = $post['id'];
        $data['status'] = $post['status'];
        $data['approval'] = $post['approval'];
        $data['approval_time'] = time();
        $res = model('ApplyLeave')->save($data,$condi);

        $this->sendNewss($post['id'],$post['status']);

    	if($res == 1){
    		return [
    			'status'=>1,
    			'msg'=>'成功'
    		];
    	}
    }

    // 获取请假申请详情
    public function getApplyById(){
    	$id = input('get.id');
    	$model_res = model('ApplyLeave')->where('id','=',$id)->find();
        $store = model('store')->where('id','=',$model_res['store_id'])->find();
        if($store){
            $model_res['name'] = $store['name'];
        }else{
            $model_res['name'] = '没有选择店铺';
        }
    	return $model_res;
    	
    }

    /**
     * [getMyApply 获取我的所有请假申请,优先排序status,然后是时间
     * @return [type] [description]
     */
    public function getMyApply($is_admin,$page)
    {
    	
        $myApply = ApplyLeaveModel::getMyApply($is_admin,$page);
    	return $myApply;
    }

}
