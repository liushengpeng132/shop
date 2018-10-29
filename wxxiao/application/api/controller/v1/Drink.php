<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;

class Drink extends BaseController
{

    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'getGoodsByStoreId']
    ];

    public function getDrinkByStoreId()
    {
        $data['store_id'] = input('get.store_id');
        $data['time'] = strtotime(input('get.date'));
        $data['cat_id'] = 899;//选出饮料记录
        // print_r($data);exit;
        $search = input('get.search');

        //查询就不需要进行检查和初始化了
        if($search == 'undefined'){
	        $goods_where['store_id'] = $data['store_id'];
	        $goods_where['goods_class'] = 0;//4代表库存商品
	        $goods_where['cat_id'] = 899;
	       	$goods = model('Goods')->where($goods_where)->column('goods_id,goods_sn,cat_id,goods_name,goods_locat,goods_unit,goods_spec,goods_size,store_count');
	       	// print_r($goods);exit;
	        //判断店铺和时间,看今天是否已经加载过数据,
	        $res = model('DrinkCount')->where($data)->value('id');
	        //如果不存在就初始化加载后再获取
	        if(!$res){
	        	$goodsArr = [];
	        	$i = 0;
	        	foreach ($goods as $k => $v) {
	        		$goodsArr[$i]['goods_id'] = $v['goods_id'];
	        		$goodsArr[$i]['cat_id'] = $v['cat_id'];
	        		$goodsArr[$i]['time'] = $data['time'];
	        		$goodsArr[$i]['store_id'] = $data['store_id'];
	        		$i++;
	        	}
	        	model('DrinkCount')->saveAll($goodsArr);
	        }else{
	            //查看是否有最新数据,如果有,则加上
	            $sale_where['store_id'] = $data['store_id'];
	            $sale_where['time'] = $data['time'];
	            // $sale_where['cat_id'] = $data['cat_id'];
	            $count1 = model('DrinkCount')->where($sale_where)->count();
	            $time_where['goods_class'] = 0;
	            $time_where['store_id'] = $data['store_id'];
	            $time_where['cat_id'] = $data['cat_id'];
	            $count2 = model('Goods')->where($time_where)->count();
	            // echo $count2-$count1;
	            if($count2>$count1){
	                 $update_goods = model('Goods')->where($time_where)->order('on_time desc')->limit($count2-$count1)->column('goods_id,cat_id');
	                 // var_dump($update_goods);exit;
	                $i = 0;
	                foreach ($update_goods as $k => $v) {
	                    $goodsArr[$i]['goods_id'] = $k;
	                    $goodsArr[$i]['cat_id'] = $v;
	                    $goodsArr[$i]['time'] = $data['time'];
	                    $goodsArr[$i]['store_id'] = $data['store_id'];
	                    $i++;
	                }
	                model('DrinkCount')->saveAll($goodsArr);
	            }
	        }
 		}else{
 			$goods_where['store_id'] = $data['store_id'];
	        $goods_where['goods_class'] = 0;//4代表库存商品
	        $goods_where['cat_id'] = 899;
           	$goods_where['goods_name'] = ['like','%'.$search.'%'];
           	$goods = model('Goods')->where($goods_where)->column('goods_id,goods_name,store_count');
        }
        $drink_counts = model('DrinkCount')->where($data)->column('id,goods_id,drink_count');
        // print_r($drink_counts);exit;
        $resArr = [];
        $i = 0;
        foreach ($goods as $k => $v) {
        	foreach ($drink_counts as $k1 => $v1) {
        		if($k == $v1['goods_id']){
        			$resArr[$i]['id'] = $v1['id'];
        			$resArr[$i]['goods_id'] = $v1['goods_id'];
        			$resArr[$i]['drink_count'] = $v1['drink_count'];
        			$resArr[$i]['goods_name'] = $v['goods_name'];
        		}
        	}
        	$i++;
        }
        // echo model('DrinkCount')->getLastSql();
        miniapp_log($this->staff_id,'初始化饮料表');
        return $resArr;
    }

	public function getDrinkSaleByStoreId()
    {
        $data['store_id'] = input('get.store_id');
        $data['time'] = strtotime(input('get.date'));
        $data['drink_count'] = ['gt',0];
        $search = input('get.search');
 		$goods_where['store_id'] = $data['store_id'];
	    $goods_where['goods_class'] = 0;//4代表库存商品
	    $goods_where['cat_id'] = 899;
	    if($search != 'undefined'){
        	$goods_where['goods_name'] = ['like','%'.$search.'%'];
	    }
        // print_r($goods_where);exit;
        $drink_counts = model('DrinkCount')->where($data)->column('id,goods_id,drink_count');
        if(!$drink_counts){
        	return [
        		'status'=>-1,
        		'msg'=>'没有销售数据,请选择其他日期'
        	];
        }
        $goods = model('Goods')->where($goods_where)->column('goods_id,goods_name,store_count');
        // print_r($drink_counts);exit;
        $resArr = [];
        $i = 0;
        foreach ($goods as $k => $v) {
        	foreach ($drink_counts as $k1 => $v1) {
        		if($k == $v1['goods_id']){
        			$resArr[$i]['id'] = $v1['id'];
        			$resArr[$i]['goods_id'] = $v1['goods_id'];
        			$resArr[$i]['drink_count'] = $v1['drink_count'];
        			$resArr[$i]['goods_name'] = $v['goods_name'];
        		}
        	}
        	$i++;
        }
        return $resArr;
    }


    //写入盘点库存
    public function editDrink()
    {
    	
        $post =  input('post.products/a');
        $arr['id'] = $post['id'];
        $data['drink_count'] = $post['value'];
        $data['staff_id'] = $this->staff_id;//写入员工编号,以最后一个为准
        $res = model('DrinkCount')->save($data,$arr);
        if($res){
        	$return['status'] = 1;
        	$return['msg'] = '成功';
        }else{
        	$return['status'] = 0;
        	$return['msg'] = '失败';
        }
         miniapp_log($this->staff_id,'写入库存');
        return $return;
    }

    public function getMonthByStoreId(){
    	$where['store_id'] = input('get.store_id');
    	$times = model('DrinkCount')->distinct(true)->where($where)->column('time');
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
    	$times = model('DrinkCount')->distinct(true)->where($where)->order('time desc')->column('time');//
    	 // model('DrinkCount')->getLastSql();
    	// 获取到当月盘点的次数对应的时间
    	$timesArr = [];
    	$abc = ['a','b','c','d','e','f','g','h','i','j','k'];
    	$i = 0;
    	foreach ($times as $v) {
    		$timesArr[$abc[$i]] = date('Y-m-d',$v);
    		$i++;
    	}
    	//必须加上id(唯一索引),否则会导致数据不齐全
    	$stock_counts = model('DrinkCount')->where($where)->column('id,goods_id,stock_count,time');
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
    	$cat_ids = model('DrinkCount')->distinct(true)->where($data)->column('cat_id');
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
    	$cat_ids = model('DrinkCount')->distinct(true)->where($where)->column('cat_id');
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
