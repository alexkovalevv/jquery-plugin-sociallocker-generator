<?php

	/**
	 * Работа с транзакциями
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright Alex Kovalev 09.03.2017
	 * @version 1.0
	 */
	class Transaction {

		public $isset = false;
		public $id;
		public $product;
		public $amount;
		public $delivery_data;
		public $data;
		public $status;
		public $gateway;
		public $expires;
		public $began;
		public $finished;
		public $user_email;

		private static $_transactions = array();

		public function __construct(array $transaction)
		{
			foreach($transaction as $key => $value) {
				$key = preg_replace('/([A-Z])/', '_$1', $key);
				$key = str_replace('transaction_', '', $key);
				$key = strtolower($key);

				if( $key == 'data' || $key == 'delivery_data' ) {
					$this->$key = @json_decode($value, true);
					continue;
				}

				$this->$key = $value;
			}
		}

		public static function getInstance($transaction_id)
		{
			global $mysqli;

			$transaction_id = $mysqli->real_escape_string($transaction_id);

			$result = $mysqli->query("
				SELECT *
				FROM wp_pm_transactions
				WHERE transactionId = '" . $transaction_id . "'
				AND transactionStatus = 'finish'
				AND product = 'sociallocker-jquery-basic' LIMIT 1
			");

			if( !$result ) {
				throw new ErrorException('Ошибка при получения списка транзакций.');
			}

			$transaction = $result->fetch_assoc();
			$result->close();

			if( !empty($transaction) ) {
				return new Transaction($transaction);
			}

			return null;
		}

		public function getRelated()
		{
			return self::getAllByEmail($this->user_email);
		}

		public static function getAllByEmail($email)
		{
			global $mysqli;

			if( empty($email) ) {
				return array();
			}

			if( isset(self::$_transactions[$email]) && !empty(self::$_transactions[$email]) ) {
				return self::$_transactions[$email];
			}

			$result = $mysqli->query("
				SELECT *
				FROM wp_pm_transactions
				WHERE userEmail = '" . $mysqli->real_escape_string($email) . "'
				AND transactionStatus = 'finish'
				AND product = 'sociallocker-jquery-basic'
			");

			if( !$result ) {
				throw new ErrorException('Ошибка при получения списка транзакций.');
			}

			while( $transaction = $result->fetch_assoc() ) {
				self::$_transactions[$email][] = new Transaction($transaction);
			}
			$result->close();

			return self::$_transactions[$email];
		}
	}