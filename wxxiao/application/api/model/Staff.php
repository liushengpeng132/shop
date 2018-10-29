<?php

namespace app\api\model;

use think\Model;

class Staff extends Model
{
	public static function getByOpenID($openid)
	{
		return self::where('openid','=',$openid)->find();
	}
    
}
