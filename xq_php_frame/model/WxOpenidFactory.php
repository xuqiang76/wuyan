<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;

class WxOpenidFactory extends Factory 
{
    const objkey = 'user_php70_wx_openid_multi_';
    private $sql;
    public function __construct($dbobj, $uid) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$uid;
        $this->sql = "select
            `uid`
            , `openid`

            from `wx_openid`
            where `uid`=".intval($uid)."";

        parent::__construct($dbobj, $serverkey, $objkey);
        return true;
    }

    public function retrive() 
    {
        $records = BaseFunction::query_sql_backend($this->sql);
        if( !$records ) 
        {
            return null;
        }

        $obj = null;
        while ( ($row = $records->fetch_row()) != false ) 
        {
            $obj = new WxOpenid;

            $obj->uid = intval($row[0]);
            $obj->openid = ($row[1]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}
