<?php
	/**
	 * Основной код генератора
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright Alex Kovalev 03.03.2017
	 * @version 1.0
	 */
	require_once('transaction.class.php');
	require_once('user.class.php');

	class LockersStorage {

		public $build = 'free';
		public $user;
		public $locker_id;

		public function __construct(User $user)
		{
			$this->user = $user;

			if( $this->user_is_logged_in && !empty($this->user_email) ) {
				$this->build = 'premium';
			}
		}

		public function __get($name)
		{
			if( strpos($name, 'user_') !== false ) {
				$attr_name = str_replace('user_', '', $name);

				if( !empty($this->user) && isset($this->user->$attr_name) ) {
					return $this->user->$attr_name;
				}
			}

			return null;
		}

		public function __isset($name)
		{
			if( strpos($name, 'user_') !== false ) {
				$attr_name = str_replace('user_', '', $name);

				return !empty($this->user) && isset($this->user->$attr_name);
			}
			
			return isset($name);
		}

		public function getLockersAutoLoadUrl()
		{
			return CDN_URL . 'sl-libs/autoload.1.1.1.min.js';
		}

		public function getExternalOptionsUrl($locker_id)
		{
			if( empty($locker_id) ) {
				throw new ErrorException('Не передан обязательный аргумент locker_id');
			}

			return CDN_URL . CDN_WORKSPACE_DIRNAME . md5($this->user_guid) . '/locker' . $locker_id . '-options.js';
		}

		private function updateAutoloadFile(array $locker_options)
		{			
			require(PLUGIN_DIR . '/libs/aws-sdk/vendor/autoload.php');

			$s3 = new Aws\S3\S3Client([
				'version' => 'latest',
				'region' => 'eu-central-1',
				'credentials' => [
					'key' => '',
					'secret' => ''
				]
			]);

			$class_name = ".to-lock-" . $this->locker_id;

			if( isset($locker_options['selector']) && !empty($locker_options['selector']) ) {
				$class_name = $locker_options['selector'];
				unset($locker_options['selector']);
			}

			$locker_options['id'] = $this->locker_id;

			$global_options = array(
				'content_selector' => $class_name,
				'locker_id' => $this->locker_id,
				'build' => $this->build,
				'external_options_url' => $this->getExternalOptionsUrl($this->locker_id),
				'autoload_url' => $this->getLockersAutoLoadUrl(),
				'locker_options' => $locker_options
			);

			// зашиваем лицензию
			if( !empty($this->user_transaction) ) {
				$domains = $this->user_transaction->data['domain'];

				if( is_array($domains) ) {
					foreach($domains as $domain) {
						$global_options['allow_domains'][] = $this->getDomainSuffix(trim($domain));
						$global_options['gmst_modules'][] = $this->hash9Chars($this->getDomainSuffix(trim($domain)));
					}
				} else {
					$global_options['allow_domains'][] = $this->getDomainSuffix(trim($domains));
					$global_options['gmst_modules'][] = $this->hash9Chars($this->getDomainSuffix(trim($domains)));
				};
			}

			$print_options = json_encode($global_options, JSON_HEX_QUOT);

			$write_content = 'window.__onpwgt_global_options = window.__onpwgt_global_options || ' . $print_options . ';';

			try {
				$result = $s3->putObject(array(
					'Bucket' => 'cdn.sociallocker.ru',
					'Key' => 'lockers/' . md5($this->user_guid) . '/locker' . $this->locker_id . '-options.js',
					'ContentType' => 'text/javascript',
					'Body' => $write_content,
					'ACL' => 'public-read'
				));
			} catch( Exception $e ) {
			}
		}

		protected function getDomainSuffix($str)
		{
			$str = preg_replace('/http(?:s)?|[\/:]/i', '', $str);
			preg_match('/[A-z0-9-]+\.(ucoz.ru|blogspot\.[A-z]+|liveinternet\.[A-z]+|livejournal\.[A-z]+|de\.[A-z]{2}|eu\.[A-z]{2}|in\.[A-z]{2}|ru\.[A-z]{2}|co\.[A-z]{2}|org\.[A-z]{2}|com\.[A-z]{2}|[A-z0-9-]+)$/i', $str, $match);

			return $match[0];
		}

		/**
		 * Кодирует домен в 9 символьный хеш код
		 * @param $str (str)
		 *
		 * @since 1.0.0
		 * @return string
		 */
		/*protected function hash9Chars($s)
		{
			$hash = 0;
			$len = mb_strlen($s, 'UTF-8');
			if( $len == 0 ) {
				return $hash;
			}
			for($i = 0; $i < $len; $i++) {
				$c = mb_substr($s, $i, 1, 'UTF-8');
				$cc = unpack('V', iconv('UTF-8', 'UCS-4LE', $c))[1];
				$hash = (($hash << 5) - $hash) + $cc;
				$hash &= $hash; // 16bit > 32bit
			}

			$hash = base_convert($hash, 10, 16);
			$hash = str_replace("-", "", $hash);

			return $hash;
		}*/

		/**
		 * Кодирует домен в 9 символьный хеш код
		 * @param $str (str)
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function hash9Chars($s)
		{
			$h = 0;
			if( empty($s) ) {
				return $h;
			}

			$len = strlen($s);
			for($i = 0; $i < $len; $i++) {
				$h = $this->overflow32(31 * $h + ord($s[$i]));
				$h = $h & $h;
			}

			$h = base_convert($h, 10, 16);
			$h = str_replace("-", "", $h);

			return $h;
		}

		public function overflow32($v)
		{
			$v = $v % 4294967296;
			if( $v > 2147483647 ) {
				return $v - 4294967296;
			} elseif( $v < -2147483648 ) {
				return $v + 4294967296;
			} else return $v;
		}

		public function addLocker($locker_name, array $locker_options)
		{
			global $mysqli;

			if( empty($locker_name) || empty($locker_options) ) {
				throw new ErrorException('Не передан один из обязательных параметров guid, locker_name, locker_options');
			}

			$locker_name = $mysqli->real_escape_string($locker_name);
			$write_options = str_replace("\u0022", "\\\\\"", json_encode($locker_options, JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));

			if( !$this->user_is_logged_in ) {
				$this->user->createIdentify();
			}

			$transaction_id = !empty($this->user_transaction)
				? $this->user_transaction->id
				: null;

			$query = $mysqli->query("
				INSERT into wp_opanda_lockers (user_id, transaction_id, locker_name, locker_options, created_at)
				VALUES ('" . $this->user_id . "', '" . $transaction_id . "', '" . $locker_name . "', '" . $write_options . "', '" . time() . "')
			");

			if( !$query ) {
				throw new ErrorException('Не удалось сохранить настройки замка.');
			}

			$this->locker_id = $mysqli->insert_id;

			$this->updateAutoloadFile($locker_options);

			return $this->locker_id;
		}

		public function updateLocker($locker_id, $locker_name, $locker_options)
		{
			global $mysqli;

			if( empty($locker_id) || empty($locker_name) || empty($locker_options) ) {
				throw new ErrorException('Не передан один из обязательных параметров locker_id, locker_name, locker_options');
			}

			$locker_id = $mysqli->real_escape_string($locker_id);
			$locker_name = $mysqli->real_escape_string($locker_name);
			$write_options = str_replace("\u0022", "\\\\\"", json_encode($locker_options, JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));

			$query = $mysqli->query("
				UPDATE wp_opanda_lockers
				SET	locker_name='" . $locker_name . "',
					locker_options='" . $write_options . "'
				WHERE id = '" . $locker_id . "'
			");

			if( !$query ) {
				throw new ErrorException('Не удалось сохранить настройки замка.');
			}

			if( empty($this->locker_id) ) {
				$this->locker_id = $locker_id;
			}

			$this->updateAutoloadFile($locker_options);

			return true;
		}

		public function getListDomains()
		{
			$domains = array();

			if( !$this->user_is_logged_in && empty($this->user_email) ) {
				return $domains;
			}

			if( empty($this->user_related_transactions) ) {
				return $domains;
			}

			foreach($this->user_related_transactions as $transaction) {
				if( is_array($transaction->data['domain']) ) {
					$domains[$transaction->id] = '[' . implode(', ', $transaction->data['domain']) . ']';
				} else {
					$domains[$transaction->id] = $transaction->data['domain'];
				}
			}

			return $domains;
		}

		public function getLockers()
		{
			global $mysqli;

			$lockers = array();

			if( !$this->user_is_logged_in ) {
				return $lockers;
			}

			$sql = "SELECT id, transaction_id, locker_name, locker_options
					FROM wp_opanda_lockers
					WHERE user_id = '" . $this->user_id . "'";

			$result = $mysqli->query($sql);

			if( $result ) {
				while( $data = $result->fetch_assoc() ) {
					if( !empty($this->user_email) && empty($data['transaction_id']) ) {
						continue;
					}
					$lockers[$data['id']] = array(
						'transaction_id' => $data['transaction_id'],
						'locker_name' => $data['locker_name'],
						'locker_options' => json_decode($data['locker_options'], true),
						'external_options_url' => $this->getExternalOptionsUrl($data['id']),
						'autoload_url' => $this->getLockersAutoLoadUrl(),
						'locker_url' => $this->user->getProfileUrl()
					);
				}
				$result->close();
			}

			return $lockers;
		}
	}