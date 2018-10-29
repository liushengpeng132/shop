<?php
/**
 * Created by 七月.
 * Author: 七月
 * 微信公号：小楼昨夜又秋风
 * 知乎ID: 七月在夏天
 * Date: 2017/3/5
 * Time: 17:59
 */

namespace app\api\controller;


use app\api\service\Token;
use app\api\model\WxUser;
use think\Controller;

class BaseController extends Controller
{

    private $appid;
    private $appsecret;
    protected $staff_id;

    public function __construct()
    {

        $wxUser = model('WxUser')->find();
        $this->appid = $wxUser['appid'];
        $this->appsecret = $wxUser['appsecret'];
        $this->staff_id = Token::getCurrentUid();
    }

    protected function checkExclusiveScope()
    {
        Token::needExclusiveScope();
    }

    protected function checkPrimaryScope()
    {
        Token::needPrimaryScope();
    }

    protected function checkSuperScope()
    {
        Token::needSuperScope();
    }

     public function setrpg(){
         $setnum=I("rpg");
         $rpg["value"]=$setnum;
         $rbg1['name']="rbg";
         M("config")->where($rbg1)->save($rpg);
     }

    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
                "appid"     => $this->appid,
                "nonceStr"  => $nonceStr,
                "timestamp" => $timestamp,
                "url"       => $url,
                "signature" => $signature,
                "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function getJsApiTicket() {
        $accessToken = $this->access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
        $res = json_decode($this->https_request($url));
        //var_dump($res);
        $ticket = $res->ticket;
        return $ticket;
    }

    //采购申请待审批通知
    //模板消息跳转到小程序
    public function setNews($apply_id,$staff_name,$openid,$type=1,$obj=false)
    {
        if($type == 1){
            $content = "您有一个采购申请等待审批";
            $template_id = 'BlxBW1e6IMudkvR3M_ImEQH2O6_WcvtbkLA4NsczpZU';
            $date = date('Y-m-d H:i:s');
            $days = '';
        }
        if($type == 2){
            $content = "您有一个调货申请等待审批";
            $template_id = 'BlxBW1e6IMudkvR3M_ImEQH2O6_WcvtbkLA4NsczpZU';
            $date = date('Y-m-d H:i:s');
            $days = '';
        }
        if($type == 3){
            $apply_leave = model('ApplyLeave')->where('id','=',$apply_id)->find();
            $content = $staff_name."的请假申请需要您审批";
            $template_id = 'CexQTIdya529WdE7eh6-XSv0hjqYOybn5yVOdz6Ccys';
            $apply_id = $apply_leave['type'];
            $staff_name = $apply_leave['start_time'];
            $date = $apply_leave['stop_time'];
            $date_diff = (strtotime($date)-strtotime($staff_name))/24/3600;
            $days = $date_diff+'1'.'天';
        }
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $array = array(
                'touser' => $openid,//接收用户的openid
                'template_id' => $template_id,//模板id
                'data' => array(
                        'first' => array('value' => $content),
                        'keyword1' => array('value' => $apply_id),//审批单号
                        'keyword2' => array('value' => $staff_name),//申请人
                        'keyword3' => array('value' => $date),//申请时间
                        'keyword4' => array('value' => $days),//申请时间
                        'remark' => array('value' => '请尽快审批,辛苦您了'),
                ),
                "miniprogram"=>array(
                    "appid"=>"wxa5ab3db653a55f14",
                    "pagepath"=>'/pages/applyBuy/applyBuy?is_admin=1&apply_id='.$apply_id
                )
            
            );
        $postJson=json_encode($array);
        // print_r($postJson);exit;
        $res = $this->https_request($url, $postJson);
        return true;
        //这里没有处理出错的情况
        // if(!$obj){
        //     $res = json_decode($res);
        //     $msg['msg'] = '发送失败！';
        //     if($res->errmsg == "ok"){
        //         $msg['msg'] = '发送成功！';
        //     }
        //     $msg =json_encode($msg);
        //     echo($msg);              
        // }
    }


     //采购审批处理
    //模板消息跳转到小程序
    public function sendNews($apply_id,$status)
    {
        //组装发送的内容
        $apply = model('ApplyBuy')->where('apply_id','=',$apply_id)->column('apply_id,staff_id,approval,type');
        $applyGoods = model('ApplyGoods')->where('apply_id','=',$apply_id)->column('id,goods_name,goods_apply,goods_unit');
        $apply = array_values($apply)[0];
        $dataContent = '';
        $dataContent = '货品名称/申请数量/申请单位'."\n";
        foreach ($applyGoods as $v) {
            $dataContent .= '　　　　　'.$v['goods_name'].'/'.$v['goods_apply'].'/'.$v['goods_unit']."\n";
        }
        $dataContent = rtrim($dataContent,"\n");
        //如果审批失败,发信息给申请人
        $dataOpenid = model('Staff')->where('id','=',$apply['staff_id'])->value('public_openid');
        
        if($status ==3){
            $dataStatus = '拒绝申请';
            // $dataOpenid = model('Staff')->where('id','=',$apply['staff_id'])->value('public_openid');
            $dataNote = '感谢你的申请';
            $color = '#FF0000';
        }
        if($status ==2){
            //如果采购审批通过,发信息给采购部门
            if($apply['type'] == 1){
                $dataStatus = '购货申请通过';
                // $dataOpenid = model('Staff')->where('wx_scope','=',8)->value('public_openid');
                $dataNote = '请前往tpshop尽快采购,辛苦您了'; 
            }else{
                $dataStatus = '调货申请通过';
                $dataNote = '请尽快完成调货,辛苦您了'; 
            }
            
            $color = '#238E23';
        }
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $array = array(
                'touser' => $dataOpenid,//接收用户的openid
                'template_id' => 'JFo7DEgkRF7MpDGc9FupivSVREZW7l85BW1YV-sc1os',//模板id
                'data' => array(
                        'first' => array('value'=>'审批批注:'.$apply['approval']),
                        'keyword1' => array('value' => $dataStatus,'color'=>$color),//审批状态
                        'keyword2' => array('value' => $dataContent),//审批内容
                        'keyword3' => array('value' => date('Y/m/d H:i:s')),//申请时间
                        'remark' => array('value' => $dataNote),
                ),
               "miniprogram"=>array(
                    "appid"=>"wxa5ab3db653a55f14",
                    "pagepath"=>'/pages/applyBuy/applyBuy?is_admin=0&apply_id='.$apply_id
                )
            
            );
        $postJson=json_encode($array);
        // print_r($postJson);exit;
        $res = $this->https_request($url, $postJson);
        // var_dump($res);
        return true;
        //这里没有处理出错的情况
        // if(!$obj){
        //     $res = json_decode($res);
        //     $msg['msg'] = '发送失败！';
        //     if($res->errmsg == "ok"){
        //         $msg['msg'] = '发送成功！';
        //     }
        //     $msg =json_encode($msg);
        //     echo($msg);              
        // }
    }

     //请假审批处理
    //模板消息跳转到小程序
    public function sendNewss($apply_id,$status)
    {
        //组装发送的内容
        $apply = model('ApplyLeave')->where('id','=',$apply_id)->column('id,staff_id,approval,type');
        $apply = array_values($apply)[0];
        $dataOpenid = model('Staff')->where('id','=',$apply['staff_id'])->value('public_openid');
        // 审核结果
        if($status ==3){
            $dataStatus = '拒绝申请';
            $color = '#FF0000';
        }
        if($status ==2){
            $dataStatus = '申请通过';
            $color = '#238E23';
        }
        $access_token=$this->access_token();
        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $array = array(
                'touser' => $dataOpenid,//接收用户的openid
                'template_id' => 'JFo7DEgkRF7MpDGc9FupivSVREZW7l85BW1YV-sc1os',//模板id
                'data' => array(
                        'first' => array('value'=>'你的请假审批啦'),
                        'keyword1' => array('value' => $dataStatus,'color'=>$color),//审批状态
                        'keyword2' => array('value' => $apply['type']),//审批内容
                        'keyword3' => array('value' => date('Y/m/d H:i:s')),//申请时间
                        'remark' => array('value' => '审批批注:'.$apply['approval']),
                ),
               "miniprogram"=>array(
                    "appid"=>"wxa5ab3db653a55f14",
                    "pagepath"=>'/pages/applyBuy/applyBuy?is_admin=0&apply_id='.$apply_id
                )
            
            );
        $postJson=json_encode($array);
        // print_r($postJson);exit;
        $res = $this->https_request($url, $postJson);
        // var_dump($res);
        return true;
    }
    
  
    public function https_get($url, $data = null)
       {
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          $output = curl_exec($ch);
          curl_close($ch);
          $output_array = json_decode($output,true);
          return($output_array);
       }


    /*
     * curl 用于API获取 post传值
     */
    public function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /*
     * 获取微信token
     */
    public function access_token() {
        $appid=$this->appid;
        $appsecret=$this->appsecret;
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
        $res=$this->https_request($url);
        $json_obj = json_decode($res,true);
        $access_token=$json_obj['access_token'];
        return($access_token);
    }
  
}