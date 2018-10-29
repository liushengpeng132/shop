<?php

namespace app\api\controller\v1;

use app\api\service\Token as TokenService;
use app\api\controller\BaseController;
use app\lib\enum\GoodsClassEnum;
use app\api\model\ApplyBuy as ApplyBuyModel;

class ApplyBuy extends BaseController
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
    public function submitApplyBuy()
    {
        $staff_id = TokenService::getCurrentUid(); 
        $post =  input('post.products/a');
        $data['staff_id'] = $staff_id;

        $data['cause'] = $post['cause']?$post['cause']:'';
        $data['note'] = $post['note']?$post['note']:'';
        $data['store_id'] = $post['store_id'];
        if($post['type'] == 2){
            $data['is_store'] = $post['is_store'];
        }else{
            $data['is_store'] = 0;
        }
        $data['type'] = $post['type'];
        if($post['date'] != '点击选择'){
            $data['pre_time'] = $post['date'];
            // $data['pre_time'] = strtotime($post['date']);
        }
        $data['create_time'] = time();
        $model = model('ApplyBuy')->create($data);
        $apply_id = $model->apply_id;
        $data1['apply_id'] = $apply_id;
        $goods = [];
        for ($i=0; $i <$post['goodsNum'] ; $i++) { 
            $data1['goods_name'] = $post["goodsName[$i]"]?$post["goodsName[$i]"]:'';   
            $data1['goods_unit'] = $post["goods_unit[$i]"]?$post["goods_unit[$i]"]:'';   
            $data1['goods_remain'] = $post["remaining[$i]"]?$post["remaining[$i]"]:0;   
            $data1['goods_apply'] = $post["appNum[$i]"]?$post["appNum[$i]"]:0;
            model('ApplyGoods')->create($data1);   
        }
        //怎么确认哪些人是管理层,得有个方法,写入管理层的openid到员工表
        //然后通过权限获取所有的管理员
        // 申请完成,发送提示信息给管理层
        //如果多个管理员怎么办
        //这个为申请员工的姓名
        $type = $data['type'];  //申请类型
        $staff_name = model('Staff')->where('id','=',$staff_id)->value('name');
        $admins = model('Staff')->where('public_openid','neq','')->where('wx_scope','=',16)->column('public_openid');
        foreach ($admins as $v) {
            $res = $this->setNews($apply_id,$staff_name,$v,$type);
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

    /**
     * 申请材料到货确认
     * @return [type] [description]
     */
    public function submitArrival()
    {
        $staff_id = TokenService::getCurrentUid(); 
        $post =  input('post.products/a');
        $data['arr_time'] = $post['date'];  //到货时间
        $data['status'] = 4;    //改变状态
        $apply_id = $post['id'];  
        $model = model('ApplyBuy')->where('apply_id','=',$apply_id)->update($data,true);
        // 成功后
        if($model){
            // 循环编辑更改事件到货数量
            $ids = model('ApplyGoods')->field('id')->where('apply_id','=',$apply_id)->select();
            foreach ($ids as $key => $value) {  
                model('ApplyGoods')->where('id','=',$value['id'])->update(['goods_arrival'=>$post["applyGoods[{$value['id']}]"]],true);  
            }
        }else{
            //怎么确认哪些人是管理层,得有个方法,写入管理层的openid到员工表
            //然后通过权限获取所有的管理员
            // 申请完成,发送提示信息给管理层
            //如果多个管理员怎么办
            //这个为申请员工的姓名
            // $staff_name = model('Staff')->where('id','=',$staff_id)->value('name');
            // $admins = model('Staff')->where('public_openid','neq','')->where('wx_scope','=',16)->column('public_openid');
            // foreach ($admins as $v) {
            //     $res = $this->setNews($apply_id,$staff_name,$v);
            // }
            
            // if(!$res){
                return [
                    'status'=>-1,
                    'msg'=>'服务器繁忙，确认失败，请稍后重试'
                ];
            // }
        }
        return [
                'status'=>1,
                'msg'=>'成功'
           ];       
    }

    // 20181025 增加了店铺筛选条件
    public function searchGoods(){
        $search = input('get.search');
    	$store_id = input('get.store_id');
    	if(!$search){
    		return [];
    	}
        $where['goods_name'] = ['like','%'.$search.'%'];
        $where['store_id'] = $store_id;
        $where['goods_class'] = GoodsClassEnum::RAW;
        $goods = model('Goods')->where($where)->column('goods_id,goods_name,store_count,goods_unit');
        $goods = array_values($goods);
        // print_r($goods);exit;
        return [
            'mindKeys'=>$goods,
            'view'=>[
                'isShow'=>1
            ]
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
	public function checkApply()
    {
        //必须核对是否有权限啊,
        // $staff_id = $this->staff_id;
        // $wx_scope = model('Staff')->where('id','=',$staff_id)->value('wx_scope');
        // if($wx_scope != 16){
        //     //这里应该抛出异常
        // }
        $post = input('post.');
        $condi['apply_id'] = $post['id'];
        $data['status'] = $post['status'];
        $data['approval'] = $post['approval'];
        $res = model('ApplyBuy')->save($data,$condi);

        $this->sendNews($post['id'],$post['status']);

    	if($res == 1){
    		return [
    			'status'=>1,
    			'msg'=>'成功'
    		];
    	}
    }

    // 20181025 更新了店铺名称
    public function getApplyById(){
    	$apply_id = input('get.apply_id');
    	$model_res = ApplyBuyModel::with(['goods'])->where('apply_id','=',$apply_id)->find();
        $store = model('store')->where('id','=',$model_res['store_id'])->find();
        if($store){
            $model_res['name'] = $store['name'];
        }else{
            $model_res['name'] = '没有选择店铺';
        }
        $is_store = model('store')->where('id','=',$model_res['is_store'])->find();
        if($is_store){
            $model_res['is_name'] = $is_store['name'];
        }
    	return $model_res;
    	
    }

    /**
     * [getMyApply 获取我的所有申请,优先排序status,然后是时间
     * @return [type] [description]
     */
    // 20181024新增$collect $type 判断审核收货的
    public function getMyApply($is_admin,$type,$collect,$page)
    {
    	
        $myApply = ApplyBuyModel::getMyApply($is_admin,$type,$collect,$page);
    	return $myApply;
    }

    //写入盘点库存
    public function editStock()
    {
    	
        $post =  input('post.products/a');
        $arr['id'] = $post['id'];
        $data1['store_count'] = $data['stock_count'] = $post['value'];
        $res = model('StockCount')->save($data,$arr);
        $goods = model('StockCount')->where($arr)->column('goods_id','store_id');
        $where['store_id'] = key($goods);
        $where['goods_id'] = $goods[key($goods)];
        $ress = model('Goods')->save($data1,$where);
        if($ress){
        	$resss['status'] = 1;
        	$resss['msg'] = '成功';
        }else{
        	$resss['status'] = 0;
        	$resss['msg'] = '失败';
        }
        return $resss;
    }

    public function getMonthByStoreId(){
    	$where['store_id'] = input('get.store_id');
    	$times = model('StockCount')->distinct(true)->where($where)->column('time');
    	$month = [];
    	foreach ($times as $v) {
    		$month[] = date('Y-m',$v);
    	}
    	return $month = array_unique($month);
    }

    //给出每个月的盘点信息
    public function getAllStockByMonth(){
    	$date = input('get.date');
    	$store_id = input('get.store_id');

    	// $date = '2018-8';
    	$firstDay = strtotime(date('Y-m-01', strtotime($date)));
    	$lastDay =  strtotime(date('Y-m-01', strtotime($date)) . ' +1 month -1 day');
    	$where['time'] = [['egt',$firstDay],['elt',$lastDay]];
    	$where['store_id'] = $store_id;
    	$times = model('StockCount')->distinct(true)->where($where)->order('time desc')->column('time');//
    	 // model('StockCount')->getLastSql();
    	// 获取到当月盘点的次数对应的时间
    	$timesArr = [];
    	$abc = ['a','b','c','d','e','f','g','h','i','j','k'];
    	$i = 0;
    	foreach ($times as $v) {
    		$timesArr[$abc[$i]] = date('Y-m-d',$v);
    		$i++;
    	}
    	//必须加上id(唯一索引),否则会导致数据不齐全
    	$stock_counts = model('StockCount')->where($where)->column('id,goods_id,stock_count,time');
    	$stocksArr = []; 
    	foreach ($timesArr as $k => $v) {
    		foreach ($stock_counts as $v1) {
    			if($v ==date('Y-m-d',$v1['time'])){
    				// $stocksArr[$v1['goods_id']]['goods_id'] = $v1['goods_id'];
    				$stocksArr[$v1['goods_id']][$k] = $v1['stock_count'];
    			}
    		}
    	}
    	//获取所有商品.
        $search = input('get.search');//
        if($search != 'undefined'){
            $goods_where['goods_name'] = ['like','%'.$search.'%'];
        }
    	$goods_where['goods_class'] = 4;
    	$goods_where['store_id'] = $store_id;
    	$goods = model('Goods')->where($goods_where)->column('goods_id,cat_id,goods_sn,goods_name,goods_locat,goods_size,goods_spec,goods_unit');
    	foreach ($stocksArr as $k => &$v) {
    		foreach ($goods as $k1 => $v1) {
    			if($k == $k1){
    				$v['goods_sn'] = $v1['goods_sn'];
    				$v['cat_id'] = $v1['cat_id'];
    				$v['goods_name'] = $v1['goods_name'];
    				$v['goods_locat'] = $v1['goods_locat'];
    				$v['goods_size'] = $v1['goods_size'];
    				$v['goods_spec'] = $v1['goods_spec'];
    				$v['goods_unit'] = $v1['goods_unit'];
    			}
    		}
    	}
    	return [
    		'times'=>$timesArr,
    		'res'=>$stocksArr
    	];

    }

    public function getStockCats(){
    	$data['store_id'] = input('get.store_id');
    	$data['time'] = strtotime(input('get.date'));
    	$cat_ids = model('StockCount')->distinct(true)->where($data)->column('cat_id');
    	$where['id'] = ['in',$cat_ids];
    	$res = model('GoodsCategory')->field('id,name')->where($where)->select();
    	// print_r($res);
    	return $res;
    }

    public function getAllCatsByDate(){
    	$date = input('get.date');
    	$store_id = input('get.store_id');
    	// $month = '2018-8';
    	$firstDay = strtotime(date('Y-m-01', strtotime($date)));
    	$lastDay =  strtotime(date('Y-m-01', strtotime($date)) . ' +1 month -1 day');
    	$where['time'] = [['egt',$firstDay],['elt',$lastDay]];
    	$where['store_id'] = $store_id;
    	$cat_ids = model('StockCount')->distinct(true)->where($where)->column('cat_id');
    	$where1['id'] = ['in',$cat_ids];
    	$res = model('GoodsCategory')->field('id,name')->where($where1)->select();
    	// print_r($res);
    	if(!$res){
    		$res['status'] = 0;
    		$res['msg'] = '当前日期无数据,请选择其他日期';
    	}
    	return $res;
    }
}
