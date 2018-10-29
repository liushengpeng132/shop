<?php
return [
	'app_id'=>'wxa5ab3db653a55f14',
	'app_secret'=>'657bd264af6bc49012c2752a6e9a40c9',
	'login_url'=>'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
	// 微信获取access_token的url地址
    'access_token_url' => "https://api.weixin.qq.com/cgi-bin/token?" .
        "grant_type=client_credential&appid=%s&secret=%s",
];