<?php
	// ** Параметры MySQL: Эту информацию можно получить у вашего хостинг-провайдера ** //
	/** Имя базы данных для WordPress */
	define('DB_NAME', 'co01015_slru');
	/** Имя пользователя MySQL */
	define('DB_USER', 'co01015_slru');
	/** Пароль к базе данных MySQL */
	define('DB_PASSWORD', '86PSPX4v');
	/** Имя сервера MySQL */
	define('DB_HOST', 'localhost');
	/** Кодировка базы данных для создания таблиц. */
	define('DB_CHARSET', 'utf8');

	header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');

	session_start();

	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$mysqli->set_charset(DB_CHARSET);

	if( $mysqli->connect_errno ) {
		echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}

	$page_refresh = false;

	if( isset($_REQUEST['action']) ) {

		$guid = isset($_REQUEST['guid'])
			? $mysqli->real_escape_string($_REQUEST['guid'])
			: null;

		$user_id = isset($_GET['user_id'])
			? (int)$mysqli->real_escape_string($_GET['user_id'])
			: null;

		$error_text = 'Произошла неизвестная ошибка (код ошибки %s). Вы не можете продолжить процесс. Пожалуйста, сообщите об ошибке администратору сайта.';

		if( $_GET['action'] == 'user_register' ) {
			if( isset($_SESSION['page_refresh']) ) {
				unset($_SESSION['page_refresh']);
			}

			$reffer = str_replace('https://sociallocker.ru/api.php?api_url=https://api.vk.com/api.php&', '', $_SERVER["HTTP_REFERER"]);
			parse_str($reffer, $data);

			$guid = isset($data['hash'])
				? $mysqli->real_escape_string($data['hash'])
				: null;

			$uid = isset($data['viewer_id'])
				? $mysqli->real_escape_string($data['viewer_id'])
				: null;

			if( empty($guid) || empty($uid) ) {
				echo sprintf($error_text, 'invalid_guid_or_uid');
				exit();
			}

			$query = $mysqli->query("
				INSERT into wp_opanda_vk_auth (guid, uid, status, created_at)
				VALUES ('" . $guid . "', '" . $uid . "', 'waiting', '" . time() . "')
			");

			if( !$query ) {
				echo sprintf($error_text, 'user_is_not_created');
				exit();
			}

			$user_id = $mysqli->insert_id;

			if( empty($user_id) ) {
				echo sprintf($error_text, 'unexpected_error');
				exit();
			}

			header('Location: https://sociallocker.ru/api.php?action=user_waiting&user_id=' . $user_id . '&hash=' . md5($user_id . 'od843kdfdfd5'));
			exit();
		} else if( $_GET['action'] == 'user_waiting' ) {
			if( $_SESSION['page_refresh'] > 10 ) {
				echo 'Превышен лимит запросов. Вы не можете продолжить процесс.';
				exit();
			}

			$_SESSION['page_refresh']++;

			$hash = isset($_GET['hash'])
				? trim($_GET['hash'])
				: null;

			if( empty($user_id) || empty($hash) ) {
				echo sprintf($error_text, 'invalid_user_id_or_hash');
				exit();
			}

			$hash_compare = md5($user_id . 'od843kdfdfd5');

			if( $hash_compare !== $hash ) {
				echo sprintf($error_text, 'access_denied');
				exit();
			}

			$result = $mysqli->query("
				SELECT status, process_url FROM wp_opanda_vk_auth
				WHERE id = '" . $user_id . "' LIMIT 1
			");

			if( !$result ) {
				echo sprintf($error_text, 'unexpected_error');
				exit();
			}

			$user = $result->fetch_assoc();
			$result->close();

			if( empty($user) ) {
				echo sprintf($error_text, 'user_not_found');
				exit();
			}

			if( $user['status'] == 'waiting' ) {
				$page_refresh = true;
			} else {
				if( empty($user['process_url']) ) {
					echo sprintf($error_text, 'empty_process_url');
					exit();
				}

				$query = $mysqli->query("
					UPDATE wp_opanda_vk_auth
					SET
					 process_url = '',
					 status = 'redirected',
					 updated_at = '" . time() . "'
					WHERE id = '" . (int)$user_id . "'
				");

				if( !$query ) {
					echo json_encode(array('error' => 'Не удалось обновить данные пользователя'));
					exit();
				}

				header('Location: ' . $user['process_url']);
				exit();
			}
		} else if( isset($_POST['action']) && $_POST['action'] == 'get_user_info' ) {
			header('content-type: application/json; charset=utf-8');

			if( empty($guid) ) {
				echo json_encode(array('error' => 'Не передан обязательный параметр guid.'));
				exit();
			}

			$locker_id = isset($_POST['locker_id'])
				? (int)$mysqli->real_escape_string($_POST['locker_id'])
				: null;

			$process_url = isset($_POST['process_url'])
				? $mysqli->real_escape_string($_POST['process_url'])
				: null;

			$share_url = isset($_POST['share_url'])
				? $mysqli->real_escape_string($_POST['share_url'])
				: null;

			$group_id = isset($_POST['group_id'])
				? $mysqli->real_escape_string($_POST['group_id'])
				: null;

			$page_url = isset($_POST['page_url'])
				? $mysqli->real_escape_string($_POST['page_url'])
				: null;

			$result = $mysqli->query("
				SELECT id, uid FROM wp_opanda_vk_auth
				WHERE guid = '" . $guid . "' LIMIT 1
			");

			/*$file = fopen('api-log.txt', 'a+');
			$string = "ip: " . get_client_ip() . "|";
			$string .= "user-agent:" . $_SERVER['HTTP_USER_AGENT'] . "|";
			$string .= "page_url: " . $page_url . "|";
			$string .= "group_id: " . $group_id . "|";
			$string .= "share_url: " . $share_url . "|";
			$string .= "process_url: " . $process_url . "|";
			$string .= "locker_id: " . $locker_id;
			$string .= "\n";
			fwrite($file, $string);
			fclose($file);*/

			if( !$result ) {
				echo json_encode(array('error' => 'Ошибка при выполнении запроса.'));
				exit();
			}

			$user = $result->fetch_assoc();
			$result->close();

			if( empty($user) ) {
				echo json_encode(array('error' => 'Пользователь не найден.', 'code' => 'user_not_found'));
				exit();
			}

			$query = $mysqli->query("
				UPDATE wp_opanda_vk_auth
				SET
				 locker_id = '" . $locker_id . "',
				 process_url = '" . $process_url . "',
				 share_url = '" . $share_url . "',
				 group_id = '" . $group_id . "',
				 page_url = '" . $page_url . "',
				 guid = '',
				 status = 'delivered',
				 updated_at = '" . time() . "'
				WHERE id = '" . (int)$user['id'] . "'
			");

			if( !$query ) {
				echo json_encode(array('error' => 'Не удалось обновить данные пользователя'));
				exit();
			}

			echo json_encode(array('uid' => $user['uid']));
			exit();
		}
	}

	// Function to get the client IP address
	function get_client_ip()
	{
		$ipaddress = '';
		if( getenv('HTTP_CLIENT_IP') ) {
			$ipaddress = getenv('HTTP_CLIENT_IP');
		} else if( getenv('HTTP_X_FORWARDED_FOR') ) {
			$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
		} else if( getenv('HTTP_X_FORWARDED') ) {
			$ipaddress = getenv('HTTP_X_FORWARDED');
		} else if( getenv('HTTP_FORWARDED_FOR') ) {
			$ipaddress = getenv('HTTP_FORWARDED_FOR');
		} else if( getenv('HTTP_FORWARDED') ) {
			$ipaddress = getenv('HTTP_FORWARDED');
		} else if( getenv('REMOTE_ADDR') ) {
			$ipaddress = getenv('REMOTE_ADDR');
		} else {
			$ipaddress = 'UNKNOWN';
		}

		return $ipaddress;
	}

	echo 'Пожалуйста, подождите, идет обработка данных...';
?>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<?php if( $page_refresh ): ?>
		<meta http-equiv="refresh" content="1.5">
		<script>
			window.resizeTo(550, 420);
			window.focus();
		</script>
	<?php endif; ?>
	<title>Переадресация...</title>
</head>
<body>
<?php if( !isset($_REQUEST['action']) ): ?>
	<script>
		window.parent.location.href = "https://sociallocker.ru/api.php?action=user_register";
	</script>
<?php endif; ?>
</body>
</html>