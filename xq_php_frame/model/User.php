<?php
namespace HttpApi\puremodel;

use bigcatorm\BaseObject;

class Wallet extends BaseObject {
	const TABLE_NAME = 'wallet';

	public $id; //
	public $uid = 0; //
	public $balance = 0; //现金
	public $token = 0; //
	public $freeze = 0; //冻结 违反规定

	public $transfer = 0; //是否具有转账权限 0未开通 1已开通 2被锁定
	public $status = 0; //钱包状态，0时正常，非0时具体值包含具体的禁止原因
	public $adwords = 0; //广告收入
	public $lock = 0; //锁钱包

	public function getUpdateSql() {
		return [
			"update `wallet` SET
            `uid`=?
            , `balance`=?
            , `token`=?
            , `freeze`=?

            , `transfer`=?
            , `status`=?
            , `adwords`=?
            , `lock`=?

            where `id`=?"

			, [

				intval($this->uid)
				, intval($this->balance)
				, intval($this->token)
				, intval($this->freeze)
				, intval($this->transfer)

				, intval($this->status)
				, intval($this->adwords)
				, intval($this->lock)

				, intval($this->id),
			],
		];
	}

	public function getInsertSql() {
		return [
			"insert into `wallet` SET

            `uid`=?
            , `balance`=?
            , `token`=?
            , `freeze`=?

            , `transfer`=?
            , `status`=?
            , `adwords`=?
            , `lock`=?"

			, [

				intval($this->uid)
				, intval($this->balance)
				, intval($this->token)
				, intval($this->freeze)
				, intval($this->transfer)

				, intval($this->status)
				, intval($this->adwords)
				, intval($this->lock),

			],
		];
	}

	public function getDelSql() {
		return [
			"delete from `wallet`
			where `id`=?"
			, [
				$this->id,
			],
		];
	}

	public function before_writeback() {
		parent::before_writeback();
		return true;
	}

}
