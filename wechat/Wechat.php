<?php

namespace akaba\wechat;
use \think\Cookie;
use \think\Cache;
use app\admin\controller\Account;
use app\admin\model\AddonsFans;
class Wechat
{
    protected $appId = 'wx06f5d44340a73c1c';
    protected $secret = '52afb5dfc24f7edd44138389d6445757';
    const ACCESS_TOKEN_LAST = 7200;
    public function jumpToRequestCodeUrl() {
        $redirectUrl = urlEncode('http://dev.akaba.cn/admin/only_for_dev/code');
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appId.'&redirect_uri='.$redirectUrl.'&response_type=code&scope=snsapi_userinfo&state=abcedf';
        header('location:'.$url);
        exit();
    }
    public function getAccessTokenBy(string $code) {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appId."&secret=".$this->secret."&code=".$code."&grant_type=authorization_code";
        $result = file_get_contents($url);
        $arr = \json_decode($result, true);
        // Cache::set('accessToken', self::ACCESS_TOKEN_LAST);
        return $arr;
    }
    public function getAccessToken() {
        $accessToken = Cache::get('accessToken');
        if (empty($accessToken)) {
            $this->jumpToRequestCodeUrl();//注意此函数中有结束进程的命令            
        }
        return $accessToken;
    }
    public  function getFansData($fansModel, $arr) {
        $fansData = $fansModel->where('openid', $arr['openid'])->find();
        if (empty($fansData)) {
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$arr['access_token']."&openid=".$arr['openid']."&lang=zh_CN";
            $result = file_get_contents($url);
            $fansData = json_decode($result, true);
            $fansData['avatar'] = $fansData['headimgurl'];
        } else {
            $fansData = $fansData->toArray();
        }     
        return $fansData;
    }
    public function getFanDataBy($arr) {
        $fansModel = new AddonsFans();
        $fansData = $this->getFansData($fansModel, $arr);
        Cookie::set("openid", $fansData['openid']);
        $url = Cookie::get('st_prev_link');
        Cookie::delete("st_prev_link");
        preg_match("/(?:\?|\&)acid=(\w+)/", $url, $matches);        
        if (!empty($matches)) {
            $actIdStr = $matches[1];
            $actId = encrypt($actIdStr, 'D');
            $fanId = $fansModel->where([
                'acid' => $actId,
                'openid' => $fansData['openid']
            ])->value("id");
            if (empty($fanId)) {
                $fansModel->save([
                    'openid' => $fansData['openid'],
                    'nickname' => $fansData['nickname'],
                    'avatar' => $fansData['avatar'],
                    'createtime' => time(),
                    'follow' => intVal($fansData['follow']),
                    'sex' => intVal($fansData['sex']),
                    'count' => 0,
                    'acid' => $actId,
                    'signstatus' => 0,
                    'show' => 0,
                    'wish' => '',
                    'signtime' => 0,
                    'audit_status' => 0
                ]);
                $fanId = $fansModel->getLastInsID();
            }
            Cookie::set('fansid', $fanId);
            Cookie::set('nickname',$fansData['nickname']);
            header('location:'.$url);
            die();
        } else {
            throw new Error('匹配活动id出错');
        }
    }
    public function saveNowUrl() {
        $link = 'http://dev.akaba.cn'.$_SERVER['REQUEST_URI'];
        Cookie::set("st_prev_link", $link);
    }
    public function requestFanInfo() {
        $this->saveNowUrl();
        $openid = Cookie::get("openid");
        if (!empty($openid)) {
            $accessToken = $this->getAccessToken();
            $this->getFanDataBy([
                'openid' => $openid,
                'access_token' => $accessToken
            ]);    
            return ;
        }
        $url = $this->jumpToRequestCodeUrl();
    }
}