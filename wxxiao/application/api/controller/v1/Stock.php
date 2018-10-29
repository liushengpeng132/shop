<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;

class Stock extends BaseController
{

    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'getGoodsByStoreId']
    ];

    public function getGoodsByStoreId()
    {
        $data['store_id'] = input('get.store_id');
        $data['time'] = strtotime(input('get.date'));
        $search = input('get.search');
        if($search != 'undefined'){
            $goods_where['goods_name'] = ['like','%'.$search.'%'];
        }
        //判断店铺和时间,看今天是否已经加载过数据,
        $goods_where['store_id'] = $data['store_id'];
        $goods_where['goods_class'] = 4;//4代表库存商品
       	$goods = model('Goods')->where($goods_where)->column('goods_id,goods_sn,cat_id,goods_name,goods_locat,goods_unit,goods_spec,goods_size,store_count');
        $res = model('StockCount')->where($data)->value('id');
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
        	model('StockCount')->saveAll($goodsArr);
        }else{
            //查看是否有最新数据,如果有,则加上
            $sale_where['store_id'] = $data['store_id'];
            $sale_where['time'] = $data['time'];
            $count1 = model('StockCount')->where($sale_where)->count();
            $time_where['goods_class'] = 4;
            $time_where['store_id'] = $data['store_id'];
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
                model('StockCount')->saveAll($goodsArr);
            }
        }
        // print_r($data);exit;
        $stock_counts = model('StockCount')->where($data)->column('id,goods_id,0+CAST(stock_count as char) as stock_count');
        $resArr = [];
        $i = 0;
        foreach ($stock_counts as $k => $v) {
        	foreach ($goods as $k1 => $v1) {
        		if($v['goods_id'] == $k1){
        			$resArr[$i]['id'] = $v['id'];
        			$resArr[$i]['goods_id'] = $v['goods_id'];
        			$resArr[$i]['stock_count'] = $v['stock_count'];
        			$resArr[$i]['cat_id'] = $v1['cat_id'];
        			$resArr[$i]['goods_name'] = $v1['goods_name'];
        			$resArr[$i]['goods_locat'] = $v1['goods_locat'];
        			$resArr[$i]['goods_sn'] = $v1['goods_sn'];
        			$resArr[$i]['goods_unit'] = $v1['goods_unit'];
        			$resArr[$i]['goods_spec'] = $v1['goods_spec'];
        			$resArr[$i]['goods_size'] = $v1['goods_size'];
        			$resArr[$i]['store_count'] = $v1['store_count'];
        		}
        	}
        	$i++;
        }
        miniapp_log($this->staff_id,'初始化库存表');
        return $resArr;
        // $res = model('StockCount')->where($data)->field('id,goods_name,cat_id,fweek,goods_sn,goods_unit,goods_spec,goods_size')->select();
        // return $res;
    }

    //写入盘点库存
    public function editStock()
    {
    	
        $post =  input('post.products/a');
        $arr['id'] = $post['id'];
        $data1['store_count'] = $data['stock_count'] = $post['value'];
        $data1['staff_id'] = $this->staff_id;//写入员工编号,以最后一个为准
        $res = model('StockCount')->save($data,$arr);
        // var_dump($data1);exit;
        $goods = model('StockCount')->where($arr)->column('goods_id','store_id');
        $where['store_id'] = key($goods);
        $where['goods_id'] = $goods[key($goods)];
        $ress = model('Goods')->allowField(true)->save($data1,$where);
        if($ress){
        	$resss['status'] = 1;
        	$resss['msg'] = '成功';
        }else{
        	$resss['status'] = 0;
        	$resss['msg'] = '失败';
        }
         miniapp_log($this->staff_id,'写入库存');
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
    	$abc = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
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
