(function($) {

	window.optGenerator = window.optGenerator || {};

	window.optGenerator = {

		_options: {},
		_buttonsOrder: [],
		_local: false,
		_guid: null,
		_lockerId: null,
		_transactionId: null,
		_autoloadUrl: null,
		_externalOptionsUrl: null,

		init: function() {

			this.prepareOptions();
			this.setupEvents();

			var hash = null;

			if( window.location.hash ) {
				hash = window.location.hash.replace('#', '');
			}

			if( !$.isEmptyObject(window.lockersOptions) && hash && Math.floor(hash) == hash && $.isNumeric(hash) && window.lockersOptions[hash] ) {
				this.setLockerOptions(hash);
				this.showConfirmationWindow();
				return;
			}

			if( !$.isEmptyObject(window.___domains) || !$.isEmptyObject(window.lockersOptions) ) {
				this.showSelectLockerWindwow();
				this.updateSelectPopUpFields(true, true);
				this.setLockerOptions();

				return;
			}

			this.refresh();
		},

		_getTransactionId: function() {
			if( $.isEmptyObject(window.___domains) ) {
				return null;
			}
			var transactions = Object.keys(window.___domains);
			return $('#onp-slg-field-sites').val() || this._transactionId || transactions[0];
		},

		prepareOptions: function() {
			this._guid = window.___guid || null;
		},

		updateSelectPopUpFields: function(updateSites, updateLockers) {
			var self = this;
			var lockers = window.lockersOptions,
				sites = window.___domains,
				selectSitesControl = $('#onp-slg-field-sites'),
				selectLockersControl = $('#onp-slg-field-lockers'),
				selectLockerButton = $('#onp-slg-select-locker'),
				popup = $('#onp-slg-select-locker-popup'),
				selected = '',
				transactionId;

			if( updateSites ) {
				selectSitesControl.html('');
				if( !$.isEmptyObject(window.___domains) ) {
					for( var trId in sites ) {
						if( !sites.hasOwnProperty(trId) ) {
							continue;
						}

						if( !$.isEmptyObject(window.___domains) ) {
							transactionId = this._getTransactionId();
							selected = trId == transactionId ? 'selected' : '';
						}
						selectSitesControl.append('<option value="' + trId + '"' + selected + '>' + sites[trId] + '</option>');
					}
				}
			}

			if( updateLockers ) {
				selectLockersControl.html('');
				if( !$.isEmptyObject(window.lockersOptions) ) {
					for( var lockerId in lockers ) {
						if( !lockers.hasOwnProperty(lockerId) ) {
							continue;
						}
						if( !$.isEmptyObject(window.___domains) ) {
							transactionId = this._getTransactionId();
							if( transactionId != lockers[lockerId].transaction_id ) {
								continue;
							}
						}
						selected = this._lockerId == lockerId ? 'selected' : '';
						selectLockersControl.append('<option value="' + lockerId + '"' + selected + '>' + lockers[lockerId].locker_name + '</option>');
					}
				}
			}
			if( $.isEmptyObject(window.___domains) || $.isEmptyObject(window.lockersOptions) ) {
				popup.addClass('one-control');
				if( $.isEmptyObject(window.___domains) ) {
					selectSitesControl.closest('.control-group').hide();
				}
				if( $.isEmptyObject(window.lockersOptions) ) {
					selectLockersControl.closest('.control-group').hide();
					selectLockerButton.hide();
				}
			} else {
				popup.removeClass('one-control');
				selectSitesControl.closest('.control-group').show();
				selectLockersControl.closest('.control-group').show();
				selectLockerButton.show();
			}
		},

		/**
		 * Binds events.
		 */
		setupEvents: function() {
			var self = this;

			$('.onp-slg-popup-close-button').click(function() {
				self.hideConfirmationWindwow();
				return false;
			});

			$('#onp-slg-field-sites').change(function() {
				self.updateSelectPopUpFields(false, true);
				self.setLockerOptions();
				return false;
			});

			$('#onp-slg-field-lockers').change('change', function() {
				self.setLockerOptions();
				return false;
			});

			$('#onp-slg-select-locker').click(function() {
				self._transactionId = self._getTransactionId();
				self.hideSelectLockerWindwow();
				self.setLockerOptions();
				return false;
			});

			$('#onp-slg-add-new-locker').click(function() {
				self.hideSelectLockerWindwow();
				self.clearLockerOptions();
				self.setLockerOptions(null, true);
				return false;
			});

			$('#onp-slg-save-button').click(function() {
				if( !$(this).hasClass('disabled') ) {
					self.saveLocker();
				}
				return false;
			});

			$('#slg-text-message').trumbowyg({
				lang: 'ru',
				btns: [
					['formatting'],
					'btnGrp-semantic',
					['link'],
					['insertImage'],
					'btnGrp-justify',
					'btnGrp-lists',
					['removeformat'],
					['viewHTML'],
					['fullscreen']
				],
				btnsAdd: ['foreColor', 'backColor']
			}).on('tbwblur', function() {
				$('#slg-text-message').change();
			});

			$('.slg-show-locked-content-box').click(function() {
				$('.slg-locked-content-box').fadeIn(500);
			});

			$('.slg-show-code-box').click(function() {
				$('#slg-code-box-2, .slg-dark-layer').fadeIn(200);
			});

			$('.slg-code-box-button-close').click(function() {
				$('#slg-code-box-2, .slg-dark-layer').fadeOut(300);
			});

			$('.slg-btn-edit').click(function() {
				self.btnSlider($(this).closest('li'));
			});

			$('.slg-setting-panel input, .slg-setting-panel textarea, .slg-setting-panel select, .slg-locked-content-box textarea').change(function(e) {
				self.refresh();
			});

			$('.slg-btn-ckeckbox').change(function() {
				if( !$(this).is(":checked") ) {
					return;
				}
				$(this).parent().parent().find(".slg-btn-edit").click();
			});

			$(".slg-setting-panel-button-segment ul").sortable({
				handle: '.slg-btn-move'
			}).bind('sortupdate', function(e) {
				self.refresh();
			});

			$('#slg-overlap-mode').change(function() {
				if( $(this).val() == "transparence" || $(this).val() == "blurring" ) {
					$('#slg-overlap-position, label[for="slg-overlap-position"]').fadeIn(200);
				} else {
					$('#slg-overlap-position, label[for="slg-overlap-position"]').fadeOut(200);
				}

				if( $(this).val() == "blurring" ) {
					$('#slg-overlap-intensity, label[for="slg-overlap-intensity"]').fadeIn(200);
				} else {
					$('#slg-overlap-intensity, label[for="slg-overlap-intensity"]').fadeOut(200);
				}
			});

			function changeTheme(val) {
				if( val == "custom" ) {
					$('#slg-theme-borderStyle, label[for="slg-theme-borderStyle"]').fadeIn(200);
				} else {
					$('#slg-theme-borderStyle, label[for="slg-theme-borderStyle"]').fadeOut(200);
				}
			}

			var themeEl = $('#slg-theme-name');

			themeEl.change(function() {
				changeTheme($(this).val())
			});

			changeTheme(themeEl.val());

			function changeModeExpires(val) {
				if( val != "none" ) {
					$('#slg-modeExpires-interval, label[for="slg-modeExpires-interval"]').fadeIn(200);
				} else {
					$('#slg-modeExpires-interval, label[for="slg-modeExpires-interval"]').fadeOut(200);
				}
			}

			var modeExpiresEl = $('#slg-modeExpires-notation');

			modeExpiresEl.change(function() {
				changeModeExpires($(this).val())
			});

			changeModeExpires(modeExpiresEl.val());

			//$('#slg-overlap-mode').change();

			$('.slg-icon-help').click(
				function(e) {
					e.stopPropagation();
					e.preventDefault();

					var position = $(this).position();
					var el = $(this).next();

					if( !el.hasClass('show') ) {
						if( $(this).closest('.slg-setting-panel').hasClass('slg-setting-panel-right') ) {
							position.left -= 245;
						} else {
							position.left -= 25;
						}

						el.fadeIn(300, function() {
							$(this).addClass('show');
						}).css({
							top: position.top + 30,
							left: position.left
						});
					} else {
						el.fadeOut(300, function() {
							$(this).removeClass('show');
						});
					}
				}
			);

			$(document).click(function(e) {
				$('.slg-field-hint').fadeOut(300, function() {
					$(this).removeClass('show');
				});
			});
		},

		/**
		 * Recreates the locker.
		 */
		refresh: function() {
			var newContent = window.toLockContent.clone();
			var oldContent = $("#preview");

			oldContent.after(newContent);
			oldContent.remove();

			this.updateLockerOptions();

			var optionsToPass = $.extend(true, {}, this._options);
			optionsToPass.demo = true;

			if( !$.pandalocker ) {
				$.pandalocker = {};
			}

			if( !$.pandalocker.lse ) {
				$.pandalocker.lse = {};
			}

			$.pandalocker.lse.allowDomains = "sociallocker.js,sociallocker.ru".split(',');

			$.pandalocker.lse.gmst = function() {
				$.pandalocker.gmodules = true;

				var modules = "1b23b434,c347bf4,c347cee".split(',');
				return $.pandalocker.tools.egmit(modules);
			};

			newContent.find(".to-lock").pandalocker(optionsToPass);
		},

		saveLocker: function() {
			var self = this;
			var lockerName = $('#onp-slg-field-locker-name').val() || 'Новый социальный замок';
			var sendData = {
				action: 'update_locker',
				transaction_id: this._getTransactionId(),
				locker_id: this._lockerId,
				locker_name: lockerName,
				locker_options: this._options
			};

			if( !this._lockerId ) {
				sendData.action = 'add_locker';
			}

			$('#onp-slg-save-button').addClass('disabled')
				.text('Подождите...');

			$.ajax({
				type: "POST",
				url: window.location.href,
				dataType: "json",
				data: sendData,
				success: function(data) {

					if( !data || data.error ) {
						data.error && console.log('[Error]:' + data.error);
						return;
					}

					if( sendData.action == 'add_locker' && data.response == 'success' ) {
						self._lockerId = data.locker_id;

						if( !self._guid ) {
							window.location.href = data.locker_url + '#' + data.locker_id;
							return;
						}

						$('#onp-slg-field-lockers').append($('<option>', {
							value: self._lockerId,
							text: sendData.locker_name,
							selected: true
						}));
					}

					// Обновляем заголовок замка в списке выбора замков
					if( sendData.locker_name != '' ) {
						$('#onp-slg-field-lockers').find('option[value="' + self._lockerId + '"]')
							.text(sendData.locker_name);
					}

					$('#onp-slg-save-button').removeClass('disabled')
						.text('Сохранить настройки');

					delete sendData['action'];
					delete sendData['locker_id'];

					// Обновляем глобальные настройки замка после сохранения
					window.lockersOptions[self._lockerId] = sendData;

					self._externalOptionsUrl = data.external_options_url;
					self._autoloadUrl = data.autoload_url;

					self.showConfirmationWindow();
				}
			})
		},

		clearLockerOptions: function() {
			this._lockerId = null;
			this._autoloadUrl = null;
			this._externalOptionsUrl = null;
		},

		showConfirmationWindow: function() {
			var self = this;
			var build = window.___build,
				userId = md5(this._guid),
				requireFilesOutput = '<!-- Социальный замок(generator.sociallocker.ru) -->\n<script>' +
					'!function(a,b,c){void 0==b[c]&&(b[c]={}),b[c]["' + this._lockerId + '"]={build:"' + build + '",userId:"' + userId + '"};' +
					'var d=a.getElementsByTagName("script")[0],e=a.createElement("script"),f=function(){d.parentNode.insertBefore(e,d)};' +
					'e.type="text/javascript",e.async=!1,e.src="' + this._autoloadUrl + '","[object Opera]"==b.opera?a.addEventListener("DOMContentLoaded",f,!1):f()}(document,window,"_onpwgt");' +
					'</script>';
			$('#onp-slg-require-files-area').val(requireFilesOutput);

			if( !this._options.selector || this._options.selector == '' ) {
				$('.onp-slg-confirmation-step2').show();
				$('#onp-slg-wrap-tags-area').val('<div class="to-lock-' + this._lockerId + '">Ваш скрытый контент.</div>');
			} else {
				$('.onp-slg-confirmation-step2').hide();
			}

			var el = $('#onp-slg-confirmation-popup');
			el.css({
				opacity: 0,
				display: "block"
			});
			el.css("top", self.getWindowPositionTop(el) + "px");
			el.css({
				opacity: 1,
				display: "none"
			});
			el.fadeIn(100);
			$('.slg-dark-layer').fadeIn(100);
		},

		hideConfirmationWindwow: function() {
			$('#onp-slg-confirmation-popup').fadeOut(100);
			this.showSelectLockerWindwow();
			this.updateSelectPopUpFields(false, true);

			history.pushState("", document.title, window.location.pathname + window.location.search);
		},

		showSelectLockerWindwow: function() {
			$('.slg-dark-layer').fadeIn(100);
			$('#onp-slg-select-locker-popup').fadeIn(100);
		},

		hideSelectLockerWindwow: function() {
			$('.slg-dark-layer').fadeOut(100);
			$('#onp-slg-select-locker-popup').fadeOut(100);
		},

		getWindowPositionTop: function(handler) {
			var height = handler.outerHeight();

			return screen.height
				? (screen.height / 2 - height / 2)
				: 0;
		},

		updateFilds: function() {
			var fildData = this.convetOptionsToArrayPair(this._options);

			for( d in fildData ) {
				if( d == 'slg-text-message' ) {
					$('#' + d).trumbowyg('html', fildData[d]);
				} else if( $('#' + d).attr('type') == 'checkbox' ) {
					$('#' + d).attr('checked', fildData[d]);
				} else if( $('#' + d).get(0) && $('#' + d).get(0).tagName == 'SELECT' ) {
					$('#' + d).find('option[value="' + fildData[d] + '"]').prop('selected', true);
				} else {
					$('#' + d).val(fildData[d]);
				}
			}

			$('.slg-btn-ckeckbox').attr('checked', false);

			if( this._options.buttons ) {
				for( b in this._options.buttons.order ) {
					$('#slg-btn-' + this._options.buttons.order[b]).attr('checked', true);
				}
			}
		},

		convetOptionsToArrayPair: function(options, alias) {
			var arr = [];

			for( var i in options ) {
				if( i !== 'buttons' ) {
					if( typeof options[i] !== 'object' ) {
						var k = ((alias) ? alias + '-' + i : i);
						var selector = '';

						if( $('#slg-btn-' + k).length ) {
							selector = 'slg-btn-' + k;
						} else {
							selector = 'slg-' + k;
						}

						arr[selector] = options[i];
					} else {
						var recRes = this.convetOptionsToArrayPair(options[i], (alias) ? alias + '-' + i : i);
						for( k in  recRes ) {
							arr[k] = recRes[k];
						}
					}
				} else {

					if( typeof options[i]['counters'] !== 'undefined' ) {
						arr['slg-buttons-counter'] = options[i]['counters'];
					}
				}
			}
			return arr;
		},

		setLockerOptions: function(lockerId, isNewLocker) {
			var saveButton = $('#onp-slg-save-button');

			if( !lockerId && !isNewLocker ) {
				lockerId = $('#onp-slg-field-lockers').val();
			}

			saveButton.text('Сохранить настройки');

			if( !lockerId || $.isEmptyObject(window.lockersOptions) || !window.lockersOptions[lockerId] ) {
				if( isNewLocker || $.isEmptyObject(window.___domains) ) {
					saveButton.text('Создать замок');
				}
				this.refresh();
				return;
			}

			var locker = window.lockersOptions[lockerId];

			this._lockerId = lockerId;

			this._options = locker.locker_options || this._options;
			this._externalOptionsUrl = locker.external_options_url;
			this._autoloadUrl = locker.autoload_url;

			this.updateFilds();
			this.refresh();

			$('#onp-slg-field-locker-name').val(locker.locker_name);
		},

		updateOrderButton: function() {
			var self = this;
			self._buttonsOrder = [];

			$(".slg-setting-panel-button-segment ul").find('li').each(function() {
				var buttonId = $(this).find('.slg-btn-ckeckbox:checked').attr('id');

				if( buttonId ) {
					self._buttonsOrder.push(buttonId.replace('slg-btn-', ''));
				}
			});

			if( !self._buttonsOrder.length ) {
				if( !$.isEmptyObject(window.___domains) ) {
					self._buttonsOrder = ["facebook-like", "twitter-tweet", "google-plus"];
				} else {
					self._buttonsOrder = ["vk-like", "ok-share", 'mail-share'];
				}
			}
		},

		updateLockerOptions: function() {
			var self = this;

			self._options = {};

			$('.slg-setting-panel-left, .slg-setting-panel-locker-text, .slg-locked-content-box').find('input, select, textarea')
				.add($('.slg-btn-ckeckbox:checked').parent().next().find('input, select, textarea'))
				.add('#slg-lang')
				//.not('#slg-selector')
				.each(function(i, el) {

					var checkPoint = $(this).attr('type') == 'checkbox'
						? Number($(this).is(':checked'))
						: $(this).val();

					if( $(this).data('parse') === false ) {
						return;
					}

					if( checkPoint != $(this).data('default') ) {
						self.converteFildIdToLockerOption($(this).attr('id'));
					}
				});

			self.updateOrderButton();

			if( self._buttonsOrder && self._buttonsOrder.length ) {
				if( !self._options.buttons ) {
					self._options.buttons = {};
				}
				self._options.buttons.order = self._buttonsOrder;
				self._options.buttons.counters = $("#slg-buttons-counter").is(':checked');
				//self._options.buttons.lazy = $("#slg-buttons-lazy").is(':checked');
			}

			if( self._options.content ) {
				self._options.content = self.strEncode(self._options.content);
				self._options.contentEncode = true;
			}

			if( self._options.modeExpires && self._options.modeExpires.notation != 'none' ) {
				var interval = parseInt(self._options.modeExpires.interval);
				if( interval != '' ) {
					var expires = null;
					if( self._options.modeExpires.notation == 'day' ) {
						expires = (interval * 24 ) * 3600;
					} else if( self._options.modeExpires.notation == 'week' ) {
						expires = ((interval * 7) * 24 ) * 3600;
					} else if( self._options.modeExpires.notation == 'month' ) {
						expires = ((interval * 30) * 24) * 3600;
					} else if( self._options.modeExpires.notation == 'year' ) {
						expires = ((interval * 365) * 24) * 3600;
					}

					if( expires ) {
						if( !this._options.locker ) {
							this._options.locker = {};
						}
						this._options.locker.expires = expires;
					}
				}
			}
		},

		converteFildIdToLockerOption: function(fildId) {
			var self = this;

			var elements = fildId.replace(/(slg-btn-|slg-)/i, '').split("-");

			var base = self._options,
				fieldEl = $("#" + fildId);

			var itemValue = fieldEl.attr('type') == 'checkbox'
				? fieldEl.is(":checked")
				: fieldEl.val();

			for( var i in elements ) {
				var isLast = ( i == elements.length - 1);

				if( !base[elements[i]] || isLast ) {
					base[elements[i]] = isLast ? itemValue : {};
				}

				base = base[elements[i]];
			}
		},

		btnSlider: function(self) {
			if( !self.hasClass('edit') ) {
				$('.slg-setting-panel-spinner')
					.fadeIn(100, function() {
						$(this).fadeOut(300);
					});

				self.addClass('edit');
				self.find('.slg-btn-edit').addClass('process');

				$('.slg-dark-layer').fadeIn(300);
				$('.slg-setting-panel-button-wrap').addClass('edit');

			} else {
				$('.slg-setting-panel-spinner')
					.fadeIn(100, function() {
						$(this).fadeOut(300);
						$('.slg-dark-layer').fadeOut(300);
						$('.slg-setting-panel-button-wrap').removeClass('edit');
					});

				self.removeClass('edit');
				self.find('.slg-btn-edit').removeClass('process');
			}
		}
	};

	$(function() {
		window.toLockContent = $("#preview").clone();
		window.optGenerator.init();
	});
})
(__$onp);