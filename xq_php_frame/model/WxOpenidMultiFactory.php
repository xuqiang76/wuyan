<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;

class WxOpenidMultiFactory extends MutiStoreFactory 
{
    public $key = 'user_php70_wx_openid_multi_';
    private $sql;

    public function __construct($dbobj, $key_objfactory=null, $uid=null, $key_add='') 
    {
        if( !$key_objfactory && !$uid )
        {
            return false;
        }
        $this->key = $this->key.$key_add;
        $ids = '';
        if($key_objfactory) 
        {
            if($key_objfactory->initialize()) 
            {
                $key_obj = $key_objfactory->get();
                $ids = implode(',', $key_obj);
            }
        }
        $fields = "
            `uid`
            , `openid`
            ";

        if( $uid != null ) 
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from wx_openid where `uid`=".intval($uid)."";
        }
        else
        {
            $this->sql = "select $fields from wx_openid ";
            if($ids)
            {
                $this->sql = $this->sql." where `uid` in ($ids) ";
            }
        }
        parent::__construct($dbobj, $this->key, $this->key, $key_objfactory, $uid);
        return true;
    }

    public function retrive()
    {
        $records = BaseFunction::query_sql_backend($this->sql);
        if( !$records ) 
        {
            return null;
        }

        $objs = array();
        while ( ($row = $records->fetch_row()) != false ) 
        {
            $obj = new WxOpenid;

            $obj->uid = intval($row[0]);
            $obj->openid = ($row[1]);

            $obj->before_writeback();
            $objs[$this->key.'_'.$obj->uid] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}
