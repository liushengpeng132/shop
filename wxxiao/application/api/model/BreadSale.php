<?php
namespace app\api\model;

use think\Model;

class BreadSale extends Model
{	
	public function goods()
	{
		return $this->hasOne('Goods','goods_id','goods_id');
	}
}