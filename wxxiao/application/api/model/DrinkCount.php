<?php
namespace app\api\model;

use think\Model;

class DrinkCount extends Model
{	
	public function goods()
	{
		return $this->belongsTo('Goods','goods_id','goods_id');
	}
}