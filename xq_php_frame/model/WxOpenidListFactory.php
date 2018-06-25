<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\model;

use bigcat\inc\ListFactory;

class WxOpenidListFactory extends ListFactory 
{
    public $key = 'user_php70_wx_openid_list_';
    public function __construct($dbobj, $openid = null, $id_multi_str='') 
    {
        //$id_multi_str 是用,分隔的字符串
        if($openid) 
        {
            $this->key = $this->key.$openid;
            $this->sql = "select `uid` from `wx_openid` where openid=".BaseFunction::my_addslashes($openid)."";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif ($id_multi_str) 
        {
            $this->key = $this->key.md5($id_multi_str);
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
            return true;
        }
        return false;
    }
}
