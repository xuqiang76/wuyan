<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;

class WxOpenid extends BaseObject 
{
    const TABLE_NAME = 'wx_openid';

    public $uid;	//
    public $openid = '';	//名字

    public function getUpdateSql() 
    {
        return "update `wx_openid` SET
            `openid`='".BaseFunction::my_addslashes($this->openid)."'

            where `uid`=".intval($this->uid)."";
    }

    public function getInsertSql() 
    {
        return "insert into `wx_openid` SET

            `uid`=".intval($this->uid)."
            , `openid`='".BaseFunction::my_addslashes($this->openid)."'
            ";
    }

    public function getDelSql() 
    {
        return "delete from `wx_openid`
            where `uid`=".intval($this->uid)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}
