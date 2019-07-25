<?php
	/**
	 * Идентификация пользователей
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright Alex Kovalev 09.03.2017
	 * @version 1.0
	 */
	require_once('transaction.class.php');
	
	class User {
		
		public $id;
		public $email;
		public $guid;
		public $is_logged_in = false;
		public $transaction;
		public $related_transactions;
		
		public function __construct($guid = null, $transaction_id = null)
		{
			$this->transaction = Transaction::getInstance($transaction_id);

			$this->guid = $guid;
			
			if( empty($this->guid) ) {
				if( isset($_COOKIE['onp_slg_guid']) && !empty($_COOKIE['onp_slg_guid']) ) {
					$this->guid = $_COOKIE['onp_slg_guid'];
				}
			}
			
			if( $this->userIdentify() ) {
				$this->is_logged_in = true;

				if( !isset($_GET['guid']) ) {
					$this->reditectToProfile();
				}
			}
		}

		public function __get($name)
		{
			$attr_name = str_replace('transaction_', '', $name);

			if( !empty($this->transaction) && isset($this->transaction->$attr_name) ) {
				return $this->transaction->$attr_name;
			}

			return null;
		}

		public function __isset($name)
		{
			$attr_name = str_replace('transaction_', '', $name);

			return !empty($this->transaction) && isset($this->transaction->$attr_name);
		}

		public function getProfileUrl()
		{
			return SITE_URL . '?guid=' . $this->guid;
		}
		
		public function reditectToProfile()
		{
			header("Location: " . $this->getProfileUrl());
			exit();
		}
		
		public function reditectToError($code)
		{
			if( empty($code) ) {
				$code = 'unexpected_error';
			}
			header("Location: " . SITE_URL . '?error=' . $code);
			exit();
		}
		
		public function destroyIdentify()
		{
			unset($_COOKIE['onp_slg_guid']);
			unset($_COOKIE['onp_slg_transaction_id']);
			
			setcookie('onp_slg_guid', null, -1, '/');
			setcookie('onp_slg_transaction_id', null, -1, '/');
			
			$this->id = null;
			$this->guid = null;
		}
		
		private function userIdentify()
		{
			global $mysqli;
			
			if( $this->is_logged_in ) {
				return true;
			}

			$sql = "SELECT * FROM wp_opanda_users_identify
					WHERE";
			
			if( !empty($this->guid) ) {
				$sql .= " guid='" . $this->guid . "'";
			}
			if( !empty($this->guid) && !empty($this->transaction_user_email) ) {
				$sql .= " OR";
			}
			if( !empty($this->transaction_user_email) ) {
				$sql .= " email='" . $this->transaction_user_email . "'";
			}
			
			$result = $mysqli->query($sql);
			
			if( !empty($result) ) {
				$user = $result->fetch_assoc();
				$result->close();
				
				if( !empty($user) ) {

					if( (!empty($user['email']) && !empty($this->transaction_user_email)) && $this->transaction_user_email != $user['email'] ) {
						$this->destroyIdentify();
						$this->createIdentify();
						$this->reditectToProfile();
					}

					if( empty($this->transaction) ) {
						if( !empty($user['email']) ) {
							$this->related_transactions = Transaction::getAllByEmail($user['email']);
						}
					} else {
						$this->related_transactions = $this->transaction->getRelated();
					}

					$this->id = $user['id'];
					$this->guid = $user['guid'];
					$this->email = $user['email'];

					if( !isset($_COOKIE['onp_slg_guid']) || $_COOKIE['onp_slg_guid'] != $this->guid ) {
						setcookie("onp_slg_guid", $this->guid, time() + 86400 * 364, "/");
					}
					
					if( empty($user['email']) && !empty($this->transaction_user_email) ) {
						$this->email = $this->transaction_user_email;
						$this->updateIdentify();
					}

					return true;
				}
				
				if( !empty($this->transaction) ) {
					$this->destroyIdentify();
					$this->createIdentify();
					$this->reditectToProfile();
				}

				if( !empty($this->guid) ) {
					$this->destroyIdentify();
					$this->reditectToError('error_authorization');
				}
			}
			
			return false;
		}
		
		private function updateIdentify()
		{
			global $mysqli;

			if( empty($this->id) || empty($this->email) ) {
				return false;
			}
			
			$query = $mysqli->query("
				UPDATE wp_opanda_users_identify
				SET	email = '" . $this->email . "'
				WHERE id = '" . $this->id . "'
			");
			
			if( !$query ) {
				throw new ErrorException('Не удалось обновить данные пользователя.');
			}
			
			return true;
		}
		
		public function createIdentify()
		{
			global $mysqli;
			
			if( empty($this->guid) ) {
				$this->guid = $this->generateGuid();
			}

			$query = $mysqli->query("
				INSERT into wp_opanda_users_identify (guid, email)
				VALUES ('" . $mysqli->real_escape_string($this->guid) . "', '" . $mysqli->real_escape_string($this->transaction_user_email) . "')
			");
			
			if( !$query ) {
				throw new ErrorException('Не удалось создать пользователя.');
			}

			if( !empty($this->transaction) ) {
				$this->related_transactions = $this->transaction->getRelated();
			}
			
			$this->id = $mysqli->insert_id;
			$this->email = $this->transaction_user_email;

			return $this->id;
		}
		
		private function generateGuid()
		{
			return md5(bin2hex(openssl_random_pseudo_bytes(32)));
		}
	}