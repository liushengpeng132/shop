<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\lib\enum\GoodsClassEnum;
use app\api\service\Token as TokenService;

class BreadSale extends BaseController
{
    protected $cat_ids;
    public function __construct()
    {
        parent::__construct();
        $where22['parent_id'] = ['in',[1,2]];
        $this->cat_ids = model('GoodsCategory')->where($where22)->column('id');
        $data['store_id'] = input('get.store_id');
        $date = input('get.date');
        if($date != 'undefined'){
            $data['time'] = strtotime($date);
        }else{
            $data['time'] = strtotime(date('Y/m/d'));
        }
        
        $res = model('BreadSale')->where($data)->value('id');
        //如果不存在就初始化加载后再获取
        if(!$res){
            // $where['goods_class'] = GoodsClassEnum::NORMAL;
            $where['store_id'] = $data['store_id'];
            $goodsArr = [];
            $goods = model('Goods')->where($where)->where('cat_id','in',$this->cat_ids)->column('goods_id,goods_sn,goods_name,store_count,cat_id');
            $data['time'] = $data['time'] - 86400;
            $lastData = model('BreadSale')->where($data)->where('invent','>',0)->column('goods_id,invent,reject');
            $data['time'] = $data['time'] + 86400;
            // model('BreadSale')->where($data)->where('goods_id','=',$k)->update(['bread_count'=>$v['invent']-$v['reject']]);
            $i = 0;
            foreach ($goods as $k => $v) {
                $goodsArr[$i]['goods_sn'] = $v['goods_sn'];
                $goodsArr[$i]['goods_id'] = $v['goods_id'];
                $goodsArr[$i]['bread_count'] = isset($lastData[$k])?$lastData[$k]['invent']-$lastData[$k]['reject']:0;
                $goodsArr[$i]['goods_name'] = $v['goods_name'];
                $goodsArr[$i]['cat_id'] = $v['cat_id'];
                $goodsArr[$i]['time'] = $data['time'];
                $goodsArr[$i]['store_id'] = $data['store_id'];
                $i++;
            }
            //获取前一天的盘点数和下架数.
            model('BreadSale')->saveAll($goodsArr);

            // $res = model('BreadSale')->where($data)->field('id,goods_name,cat_id,shipping')->select();
            // return $res;
        }
        else{
            $sale_where['store_id'] = $data['store_id'];
            $sale_where['time'] = $data['time'];
            $count1 = model('BreadSale')->where($sale_where)->count();
            // $time_where['goods_class'] = 0;
            $time_where['store_id'] = $data['store_id'];
            $count2 = model('Goods')->where($time_where)->where('cat_id','in',$this->cat_ids)->count();
            if($count2>$count1){
                 $update_goods = model('Goods')->where($time_where)->where('cat_id','in',$this->cat_ids)->order('on_time desc')->limit($count2-$count1)->column('goods_sn,goods_id,store_count,goods_name,cat_id');
                $i = 0;
                foreach ($update_goods as $k => $v) {
                    $goodsArr[$i]['goods_sn'] = $v['goods_sn'];
                    $goodsArr[$i]['goods_id'] = $v['goods_id'];
                    $goodsArr[$i]['bread_count'] = 0;//默认必须为空
                    $goodsArr[$i]['goods_name'] = $v['goods_name'];
                    $goodsArr[$i]['cat_id'] = $v['cat_id'];
                    $goodsArr[$i]['time'] = $data['time'];
                    $goodsArr[$i]['store_id'] = $data['store_id'];
                    $i++;
                }
                model('BreadSale')->saveAll($goodsArr);
            }
        }
    }

    public function getBreadByStoreId()
    {
        $data['store_id'] = input('get.store_id');
       	$date = input('get.date');
       	if($date != 'undefined'){
       		$data['time'] = strtotime($date);
       	}else{
       		$data['time'] = strtotime(date('Y/m/d'));
       	}
        $search = input('get.search');
        if($search != 'undefined'){
            $data['goods_name'] = ['like','%'.$search.'%'];
        }
        $res = model('BreadSale')->where($data)->field('id,goods_name,cat_id,shipping')->select();
        // echo 22;
        //  echo TokenService::getCurrentUid();
        // echo $this->staff_id;exit;
        // miniapp_log($this->staff_id,'获取出货列表');
        return $res;
    }

    /**
     * 获取商品,通过不同的操作显示不同的返回不同的数据
     * @param store_id 
     * @param oprate
     * @return  id,goods_name
     */
    public function getSaleByOprate()
    {

    	$data['store_id'] = input('get.store_id');
    	$oprate = input('get.oprate');
        $data['time'] = strtotime(input('get.date')); 
        // echo $data['time'] = strtotime(date('Y/m/d')); 
    	 // $data['time'] = strtotime(date("Y-m-d",strtotime("-1 day")));
    	// 出货逻辑
    	if($oprate == 'deliver'){
    		//先把出货数据复制一份到收货行,这里没有判断怎么可以啊
            // 如果别人有值,然后你去重新刷新,我试试
            $isFirst = model('BreadSale')->where($data)->where('deliver','>',0)->find();
            if(!$isFirst){
                $data['shipping'] = ['gt',0];   //
                $shipping_data = model('BreadSale')->where($data)->column('id,shipping,goods_id');
                // print_r($shipping_data);exit;
                $insert = [];
                $i = 0;
                foreach ($shipping_data as $k => $v) {
                    $insert[$i]['id'] = $v['id'];
                    $insert[$i]['deliver'] = $v['shipping'];
                    $insert[$i]['staff_id'] = $this->staff_id;
                    $i++;
                }
                // echo 22;
                // print_r($insert);exit;
                model('BreadSale')->saveAll($insert);
                foreach ($shipping_data as $k => $v) {
                    $res = model('Goods')->where('goods_id','=',$v['goods_id'])->setInc('store_count',$v['shipping']);
                }
            } 
    		//然后选出id,goods_name,shiping,deliver
    		$search = input('get.search');
	        if($search != 'undefined'){
	            $data['goods_name'] = ['like','%'.$search.'%'];
	        }
    		$data['shipping'] = ['gt',0];
    		$res = model('BreadSale')->field('id,cat_id,goods_name,shipping,deliver')->where($data)->select();
    		return $res;
    	}else if($oprate == 'freetaste'){
    		$search = input('get.search');
	        if($search != 'undefined'){
	            $data['goods_name'] = ['like','%'.$search.'%'];
	        }
            $res = model('BreadSale')->with(['goods'=>function($query){
                $query->field('goods_id,cat_id,goods_name,store_count');
            }])->field('id,goods_id,freetaste,bread_count')->where($data)->select();
            
    		return $res;
    	}else if($oprate == 'invent'){
    		$search = input('get.search');
        	if($search != 'undefined'){
           		$data['goods_name'] = ['like','%'.$search.'%'];
        	}			
    		$res = model('BreadSale')->with(['goods'=>function($query){
                $query->field('goods_id,cat_id,goods_name,store_count');
            }])->field('id,goods_id,deliver,invent,reim,dist,bread_count')->where($data)->select();
    		// $res = model('BreadSale')->field('id,cat_id,goods_name,')->where($data)->select();
    		return $res;
    	}else if($oprate == 'reject'){
    		$search = input('get.search');
	        if($search != 'undefined'){
	            $data['goods_name'] = ['like','%'.$search.'%'];
	        }
    		$res = model('BreadSale')->with(['goods'=>function($query){
                $query->field('goods_id,cat_id,goods_name,store_count');
            }])->field('id,goods_id,invent,reject')->where($data)->where('invent','>',0)->select();
    		// $res = model('BreadSale')->field('id,cat_id,goods_name,invent,reject')->where($data)->select();
            // echo model('BreadSale')->getLastSql();
    		return $res;
    	}
    }

    // 编辑面包销售表
    public function editBreadSale()
    {
    	
        $post =  input('post.products/a');
        $id = $post['id'];
        $oprate = $post['oprate'];
        $value = $post['value'];
        $arr['id'] = $id;

        //获取出来,然后进行处理
        $getOne = model('BreadSale')->where('id','=',$arr['id'])->column("id,goods_id,$oprate");
        $getOne = array_values($getOne)[0];
        // var_dump($getOne);exit;
        $data1[$oprate] = $value;
        $data1['staff_id'] = $this->staff_id;
        $ress = model('BreadSale')->save($data1,$arr);
        if($ress){
        	$resss['status'] = 1;
        	$resss['msg'] = '成功';
        }else{
        	$resss['status'] = 0;
        	$resss['msg'] = '失败';
        }
        if($getOne[$oprate]>0){
            // echo $sqlValue;
            $countInc = $value - $getOne[$oprate];//(5-3=2)
            }else{
                $countInc = $value;//5
            }
        if($oprate == 'deliver'){
            model('Goods')->where('goods_id','=',$getOne['goods_id'])->setInc('store_count',$countInc);
        }else if($oprate == 'freetaste' || $oprate =='dist' || $oprate =='reim' || $oprate == 'reject'){
            model('Goods')->where('goods_id','=',$getOne['goods_id'])->setDec('store_count',$countInc);
        }else if($oprate == 'invent'){
            $updateCon['store_count'] = $value;
            $updateArr['goods_id'] = $getOne['goods_id'];
            model('Goods')->save($updateCon,$updateArr);
        }
        miniapp_log($this->staff_id,'面包销售表:操作'.$post['oprate'].'值:'.$post['value']);
        return $resss;
    }

    public function getAllSaleByDate(){
    	$data['store_id'] = input('get.store_id');
    	$data['time'] = strtotime(input('get.date'));
    	// $data['shipping'] = ['gt',0];
    	$data['cat_id'] = input('get.cat_id');
    	$field = "cat_id,sum(shipping) as shipping,sum(bread_count) as bread_count,sum(deliver) as deliver,";
    	$field .="sum(freetaste) as freetaste,sum(dist) as dist,";
    	$field .="sum(reim) as reim,sum(invent) as invent,sum(reject) as reject";
    	$sum = model('BreadSale')->where($data)->where(function($query){
            $query->where('deliver','>',0)->whereOr('bread_count','>',0)->whereOr('shipping','>',0);
        })->field($field)->select();
        // print_r($sum->toArray());
        // echo model('BreadSale')->getLastSql();exit;
        $sum[0]['salesum'] = $sum[0]['bread_count']+$sum[0]['deliver']-$sum[0]['freetaste']-$sum[0]['dist']-$sum[0]['invent']-$sum[0]['reim'];
    	$res = model('BreadSale')->field('cat_id,goods_name,bread_count,shipping,deliver,freetaste,dist,invent,reim,reject')->where($data)->where(function($query){
                $query->where('deliver','>',0)->whereOr('bread_count','>',0)->whereOr('shipping','>',0);
        })->select();
        foreach ($res as &$v) {
            $v['salesum'] = $v['bread_count']+$v['deliver']-$v['freetaste']-$v['dist']-$v['invent']-$v['reim'];
        }
    	$result['sums'] = $sum[0];
    	$result['sales'] = $res;
        miniapp_log($this->staff_id,'获取销售记录');
    	return $result;
    }

    public function getAllCats(){
    	$res = model('GoodsCategory')->field('id,name')->where('id','in',$this->cat_ids)->select();
    	// print_r($res);
    	return $res;
    }

    public function getAllCatsByGroup(){
        // $res = model('GoodsCategory')->field('id,name')->where('cat_group','=',0)->select();
    	$res = model('GoodsCategory')->field('id,name')->where('id','in',$this->cat_ids)->select();
    	// if(!$res){
    	// 	$res['status'] = 0;
    	// 	$res['msg'] = '当前日期无数据,请选择其他日期';
    	// }
    	return $res;
    }
}
