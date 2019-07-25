<?php define('PLUGIN_DIR', dirname(__FILE__)); ?>
<?php require('includes/config.php'); ?>
<?php require('includes/db.php'); ?>
<?php

// Добавляем социальный замок
$transaction_id = isset($_REQUEST['transaction_id']) && !empty($_REQUEST['transaction_id'])
	? $_REQUEST['transaction_id']
	: null;

$guid = isset($_REQUEST['guid']) && !empty($_REQUEST['guid'])
	? $_REQUEST['guid']
	: null;

$locker_id = (int)isset($_REQUEST['locker_id']) && !empty($_REQUEST['locker_id'])
	? $_REQUEST['locker_id']
	: null;

$locker_name = isset($_POST['locker_name']) && !empty($_POST['locker_name'])
	? $_POST['locker_name']
	: null;

$locker_options = isset($_POST['locker_options']) && !empty($_POST['locker_options']) && is_array($_POST['locker_options'])
	? $_POST['locker_options']
	: null;

require_once('includes/transaction.class.php');
require_once('includes/user.class.php');
require_once('includes/strorage.class.php');

$user = new User($guid, $transaction_id);
$storage = new LockersStorage($user);

if( isset($_POST['action']) ) {
	$allow_actions = array('get_lockers', 'add_locker', 'update_locker');
	if( empty($_POST['action']) || !in_array($_POST['action'], $allow_actions) ) {
		echo 'Ошибка при выполнении неизвестного действия.';
		exit();
	}
	try {
		if( $_POST['action'] == 'get_lockers' ) {
			$lockers = $storage->getLockers(false);

			echo json_encode($lockers);
			exit();
		} else if( $_POST['action'] == 'add_locker' ) {
			$locker_id = $storage->addLocker($locker_name, $locker_options);
			if( empty($locker_id) ) {
				echo json_encode(array(
					'error' => 'Не удалось создать замок.'
				));
			}
		} else if( $_POST['action'] == 'update_locker' ) {
			if( !$storage->updateLocker($locker_id, $locker_name, $locker_options) ) {
				echo json_encode(array(
					'error' => 'Не удалось обновить настройки замка.'
				));
			}
		}

		echo json_encode(array(
			'response' => 'success',
			'locker_id' => $locker_id,
			'external_options_url' => $storage->getExternalOptionsUrl($locker_id),
			'autoload_url' => $storage->getLockersAutoLoadUrl(),
			'locker_url' => $user->getProfileUrl()
		));
		exit();
	} catch( ErrorException $e ) {
		echo json_encode(array(
			'error' => $e->getMessage()
		));
		exit();
	}
}

$plugin_build = $storage->build;

$lockers = $storage->getLockers();
$domains = $storage->getListDomains();

// Сайты пользователя для печати
$print_domains = json_encode($domains);
// Опции замком для печати
$print_locker_options = json_encode($lockers, JSON_HEX_QUOT);
?><!DOCTYPE html>
<html>
<!-- @include templates/header-meta.html -->
<body>
<script>
	window.___guid = '<?=htmlspecialchars($guid);?>';
	window.___domains = <?=$print_domains;?>;
	window.___build = '<?=$plugin_build;?>';
	window.lockersOptions = <?=$print_locker_options;?>;
</script>
<div class="slg-dark-layer"></div>
<div class="wrap">
	<header class="header">
		<h1>Сервис социального замка</h1>
		<?php if( $plugin_build == 'free' ): ?>
			<div>
				Бесплатная версия является ограниченной, вам доступно меньшее количество кнопок, а в теле замка
				установлена авторская ссылка. Чтобы снять ограничение, пожалуйста, приобретите примиум версию.
			</div>
		<?php else: ?>
			<div>
				Вы используете премиум версию социального замка без ограничений. Виджет социального замка будет работать
				только на оплаченных вами доменах.
				Перед началом использования, пожалуйста, ознакомьтесь с
				<a href="#" style="text-decoration: underline;">правилами использования и лицензией</a>.
			</div>
		<?php endif; ?>
		<ul class="menu">
			<li><a href="https://sociallocker.ru/addons/">Наши плагины</a></li>
			<li><a href="https://sociallocker.ru/documentation/">Документация</a></li>
			<?php if( $plugin_build == 'free' ): ?>
				<li>
					<a href="https://sociallocker.ru/download/#sociallocker-jquery-purchase-anchor" class="premium">
						Приобрести премиум
					</a>
				</li>
			<?php endif; ?>
			<li><a href="https://sociallocker.ru/create-ticket/">Служба поддержки</a></li>
		</ul>
	</header>
	<div class="slg-example-content">
		<div class="slg-setting-panel slg-setting-panel-locker-text">
			<p>
				<label for="onp-slg-locker-name"><strong>Название замка</strong><i class="slg-icon-help"></i>
					<span class="slg-field-hint">Название замка будет отображаться в списке выбора созданных вами замков.</span>
				</label>
				<input type="text" id="onp-slg-field-locker-name" data-parse="false" value="Новый социальный замок">
			</p>

			<p>
				<label for="slg-text-header"><strong>Заголовок замка</strong><i class="slg-icon-help"></i>
                    <span class="slg-field-hint">Текст, который появляется над социальными кнопками. Можно использовать HTML-теги, например: "<strong>Мое
		                    сообщение</strong>"</span>
				</label>
				<input type="text" id="slg-text-header" value="Этот контент заблокирован!"
				       data-default="Этот контент заблокирован!">
			</p>

			<p>
				<label for="slg-text-message"><strong>Описание замка</strong><i class="slg-icon-help"></i>
                    <span class="slg-field-hint">Текст, который появляется над социальными кнопками. Можно использовать HTML-теги, например: "<strong>Мое
		                    сообщение</strong>"</span>
				</label>
                <textarea id="slg-text-message"
                          data-default="Чтобы получить скидку 10% на наш товар, пожалуйста, поделитесь нашей страничкой в социальных сетях. После чего вам откроется промокод на покупку со скидкой 10%.">Чтобы получить скидку 10% на наш товар, пожалуйста, поделитесь нашей страничкой в социальных сетях. После чего вам откроется промокод на покупку со скидкой 10%.</textarea>
			</p>
		</div>
		<div id="preview">
			<div class="preview-title">Превью. Вы можете увидеть, как будет выглядеть ваш замок.</div>
			<section>
				<div class="to-lock" style="display: none; background-color: #f9f9f9; text-align: center;">
					<div>
						<p><strong>Lorem ipsum — название классического текста-«рыбы».</strong></p>

						<p>
							«Рыба» — слово из жаргона дизайнеров, обозначает условный, зачастую бессмысленный текст,
							вставляемый в макет страницы.
						</p>
					</div>
					<div class="image">
						<img src="assets/img/image.jpg" alt="Preview image"><br/>
						<i>Lorem ipsum — название классического текста-«рыбы».</i>
					</div>
					<div class="footer">
						<p>Lorem ipsum представляет собой искажённый отрывок из философского трактата Цицерона «О
							пределах добра и зла» , написанного в 45 году до нашей эры на латинском языке. Впервые этот
							текст был применен для набора шрифтовых образцов неизвестным печатником в XVI веке.</p>
					</div>
				</div>
			</section>
		</div>
		<?php if( $plugin_build == 'free' ): ?>
			<div class="onp-slg-disabled-athr-box">
				Хотите убрать ссылку на автора? Перейдите на
				<a href="https://sociallocker.ru/download/#sociallocker-jquery-purchase-anchor">премиум версию</a>.
			</div>
		<?php endif; ?>
		<hr>
		<!-- @include templates/footer.html -->
	</div>
	<!-- @include templates/left-sidebar.html -->
	<!-- @include templates/right-sidebar.html -->
	<div id="onp-slg-select-locker-popup">
		<p class="control-group">
			<label for="onp-slg-field-sites"><strong>Выберите сайт:</strong><i class="slg-icon-help"></i>
				<span class="slg-field-hint">Созданные вами замки будут привязаны к выбранному сайту.</span>
			</label>
			<select name="onp_slg_field_sites" id="onp-slg-field-sites">
				<option value="none">Выберите сайт</option>
			</select>
		</p>
		<p class="control-group">
			<label for="onp-slg-field-lockers"><strong>Выберите замок для
					редактирования:</strong><i class="slg-icon-help"></i>
				<span class="slg-field-hint">Вы можете откредактировать уже имеющиеся у вас социальные замки.</span>
			</label>
			<select name="onp_slg_field_lockers" id="onp-slg-field-lockers">
				<option value="none">Выберите замок</option>
			</select>
		</p>
		<button id="onp-slg-add-new-locker" class="default-button grey">Создать замок</button>
		<button id="onp-slg-select-locker" class="default-button grey">Выбрать</button>
	</div>
	<div id="onp-slg-confirmation-popup">
		<button class="onp-slg-popup-close-button">x</button>
		<h4 class="onp-slg-popup-header">Настройки замка успешно сохранены!</h4>
		<h4><strong>1. Подключите установочный код к вашей странице.</strong></h4>

		<p>Вставьте следующий код перед тегом &lt;/head&gt; или &lt;/body&gt;, на странице, где вы хотите использовать
			Социальный замок.</p>
		<textarea id="onp-slg-require-files-area" onfocus="this.select();" onmouseup="return false;" style='font-family:Consolas, monospace;'></textarea>

		<div class="onp-slg-confirmation-step2">
			<h4><strong>2. Оберните контент html тегами.</strong></h4>

			<p>Вам необходимо открыть вашу статью или страницу в режиме html и обернуть контент, который вы хотите
				скрыть.</p>
			<textarea id="onp-slg-wrap-tags-area" onfocus="this.select();" onmouseup="return false;" style='font-family:Consolas, monospace;'>&lt;div class="to-lock"&gt;ваш скрытый контент&lt;/div&gt;</textarea>
		</div>
		<div>
			<p><strong>Совет #1:</strong> Добавьте эту страницу в закладки, чтобы в будущем быстро отредактировать уже
				созданные вами замки.</p>

			<p>
				<strong>Совет #2:</strong>
				Посмотрите
				<a href="https://sociallocker.ru/documentation/" style="color:#3b5f88;text-decoration: underline;" target="_blank">документацию</a>,
				чтобы разобраться в установке виджета и его продвинутой настройке.
			</p>
		</div>
	</div>
</body>
</html>