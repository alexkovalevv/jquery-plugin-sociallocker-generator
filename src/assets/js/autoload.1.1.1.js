(function() {
	if( window.__onpwgtInstaller != undefined ) {
		if( !__onpwgtInstaller.isReady ) {
			return;
		}

		if( __$onp && !__$onp.isEmptyObject(window._onpwgt) ) {
			__onpwgtInstaller.loadLibs();
			return;
		}

		if( window._onpwgt == undefined ) {
			__onpwgtInstaller.console('error', 'Не установлены настройки для загрузки виджетов.');
			return;
		}
		__onpwgtInstaller.init();
	}

	window.__onpwgtInstaller = {
		isReady: false,

		//todo: добавить поддержку новых версий и протестировать их работу с плагином
		_JQUERY_VERSIONS: [
			"1.10.2", "1.10.1", "1.10.0", "1.12.4", "1.9.1", "1.9.0", "1.8.3", "1.8.2", "1.8.1", "1.8.0"
		],

		_LIBS_URL: '//cdn.sociallocker.ru/sl-libs/',
		_LOCKER_URL: '//cdn.sociallocker.ru/lockers/',
		_CSS_DIR: 'css/',
		_IMG_DIR: 'img/',

		init: function() {
			var self = this;

			if( void 0 !== window.jQuery && 0 <= this._JQUERY_VERSIONS.indexOf(jQuery.fn.jquery) ) {
				this.console("alert", "jQuery " + jQuery.fn.jquery + " is present and meets our requirements.");
				window.__$onp = window.jQuery;
				this.loadLibs();
				return;
			}

			this.console("alert", "Библиотека jQuery не установлена!");

			this._getScripts('//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', function() {
				if( "undefined" !== typeof jQuery ) {
					window.__$onp = jQuery.noConflict(true);
					self.console("success", "jQuery версии " + __$onp.fn.jquery + " успешно загружена.");
					self.loadLibs();
				} else {
					self.console("error", "Ошибка загрузки jQuery!");
				}
			}, true);
		},

		loadLibs: function() {
			var self = this,
				$ = __$onp;

			var promise = $.when();
			var build = 'free';

			for( lockerId in window._onpwgt ) {
				if( !window._onpwgt.hasOwnProperty(lockerId) ) {
					continue;
				}

				if( window._onpwgt[lockerId].build == 'premium' ) {
					build = 'premium';
				}
			}

			for( lockerId in window._onpwgt ) {
				if( !window._onpwgt.hasOwnProperty(lockerId) ) {
					continue;
				}

				// Настройки замков
				var mktime = Math.round(new Date().getTime() / 1000);

				promise = (function(lockerId, promise) {
					return self._loadScript(promise, self._LOCKER_URL + window._onpwgt[lockerId].userId + '/locker' + lockerId + '-options.js?v=' + mktime,
						function(promise) {

							if( window.__onpwgt_global_concat_options == undefined ) {
								window.__onpwgt_global_concat_options = {};
							}

							if( !window.__onpwgt_global_concat_options[lockerId] || !$.isEmptyObject(window.__onpwgt_global_concat_options[lockerId]) ) {
								if( window.__onpwgt_global_options == undefined ) {
									self.console("error", "Не удалось загрузить настройки виджета!");
									promise.fail();
								}

								window.__onpwgt_global_concat_options[lockerId] = window.__onpwgt_global_options;
								delete window.__onpwgt_global_options;
							}
						});
				})(lockerId, promise);

				delete window._onpwgt[lockerId];
			}

			if( self.isReady ) {
				promise.then(function() {
					self._start();
				});
				return;
			}

			$("<link/>", {
				rel: "stylesheet",
				type: "text/css",
				href: self._LIBS_URL + self._CSS_DIR + 'pandalocker.' + build + '.min.css'
			}).appendTo("head");

			// Ядро
			promise = self._loadScript(promise, self._LIBS_URL + 'pandalocker.' + build + '.min.js', function() {
				if( !$.pandalocker.lse ) {
					$.pandalocker.lse = {};
				}
				$.pandalocker.lse.build = build;
			});

			// Блик эффект для плагина
			promise = self._loadScript(promise, self._LIBS_URL + 'libs/jquery.ui.highlight.min.js');

			promise.then(function() {
				self.console("success", "Все библиотеки успешно загружены!");
				self.isReady = true;
				window.__onpWgtAsynCallback != undefined && window.__onpWgtAsynCallback();
				self._start();
			});
		},

		_loadScript: function(promise, file, callback) {
			var self = this, $ = __$onp;

			return promise.then(function() {
				return $.ajax({
					url: file,
					dataType: "script",
					cache: true
				}).then(function() {
					callback && callback();
					//self.console("success", "Библиотека " + file + " успешно загружена!");
				});
			})
		},

		_start: function() {
			var self = this;
			var $ = __$onp;

			var options = window.__onpwgt_global_concat_options;

			if( options == undefined || $.isEmptyObject(options) ) {
				self.console('error', 'Не переданы настройки виджетов.');
				return;
			}

			for( var lockerId in options ) {
				if( !options.hasOwnProperty(lockerId) ) {
					continue;
				}
				var widgetOptions = options[lockerId];

				if( !widgetOptions || !widgetOptions.locker_options ) {
					self.console('error', 'Не переданы настройки виджетов.');
					return;
				}

				if( !$.pandalocker.lse ) {
					$.pandalocker.lse = {};
				}

				if( widgetOptions.build == 'premium' ) {
					$.pandalocker.lse.autoLoadUrl = widgetOptions.external_options_url;
					$.pandalocker.lse.externalOptionsUrl = widgetOptions.external_options_url;
					$.pandalocker.lse.allowDomains = widgetOptions.allow_domains;

					$.pandalocker.lse.gmst = function() {
						$.pandalocker.gmodules = true;

						var modules = window.__onpwgt_global_concat_options[lockerId].gmst_modules;
						return $.pandalocker.tools.egmit(modules);
					};
				}

				if( !widgetOptions.locker_options.id ) {
					widgetOptions.locker_options['id'] = lockerId;
				}

				$(widgetOptions.content_selector).sociallocker(widgetOptions.locker_options);
			}
		},

		// ---------------------------------------------------------
		//   Вспомогательные медоды
		// ---------------------------------------------------------

		console: function(type, message) {
			var color = 'black';

			if( type === 'alert' ) {
				color = 'orange';
			}
			if( type === 'success' ) {
				color = 'green';
			}
			if( type === 'error' ) {
				color = 'red';
			}

			console && console.log('%c[' + type + ']: ' + message, 'color: ' + color);
		},

		_getScripts: function(url, callback, async) {
			var script = document.createElement('script');
			var prior = document.getElementsByTagName('script')[0];

			script.async = async;
			prior.parentNode.insertBefore(script, prior);

			script.onload = script.onreadystatechange = function(_, isAbort) {
				if( isAbort || !script.readyState || /loaded|complete/.test(script.readyState) ) {
					script.onload = script.onreadystatechange = null;
					script = undefined;

					if( !isAbort ) {
						if( callback ) {
							callback();
						}
					}
				}
			};

			script.src = url;
		}
	};

	/**
	 * Here is a full substitute for jQuery's .ready() written in plain javascript:
	 * http://stackoverflow.com/questions/9899372/pure-javascript-equivalent-to-jquerys-ready-how-to-call-a-function-when-the
	 */
	(function(funcName, baseObj) {
		// The public function name defaults to window.docReady
		// but you can pass in your own object and own function name and those will be used
		// if you want to put them in a different namespace
		funcName = funcName || "__onpWidgetsLoaded";
		baseObj = baseObj || window;
		var readyList = [];
		var readyFired = false;
		var readyEventHandlersInstalled = false;

		// call this when the document is ready
		// this function protects itself against being called more than once
		function ready() {
			if( !readyFired ) {
				// this must be set to true before we start calling callbacks
				readyFired = true;
				for( var i = 0; i < readyList.length; i++ ) {
					// if a callback here happens to add new ready handlers,
					// the docReady() function will see that it already fired
					// and will schedule the callback to run right after
					// this event loop finishes so all handlers will still execute
					// in order and no new ones will be added to the readyList
					// while we are processing the list
					readyList[i].fn.call(window, readyList[i].ctx);
				}
				// allow any closures held by these functions to free
				readyList = [];
			}
		}

		function readyStateChange() {
			if( document.readyState === "complete" ) {
				ready();
			}
		}

		// This is the one public interface
		// docReady(fn, context);
		// the context argument is optional - if present, it will be passed
		// as an argument to the callback
		baseObj[funcName] = function(callback, context) {
			if( typeof callback !== "function" ) {
				throw new TypeError("callback for docReady(fn) must be a function");
			}
			// if ready has already fired, then just schedule the callback
			// to fire asynchronously, but right away
			if( readyFired ) {
				setTimeout(function() {
					callback(context);
				}, 1);
				return;
			} else {
				// add the function and context to the list
				readyList.push({
					fn: callback,
					ctx: context
				});
			}
			// if document already ready to go, schedule the ready function to run
			if( document.readyState === "complete" ) {
				setTimeout(ready, 1);
			} else if( !readyEventHandlersInstalled ) {
				// otherwise if we don't have event handlers installed, install them
				if( document.addEventListener ) {
					// first choice is DOMContentLoaded event
					document.addEventListener("DOMContentLoaded", ready, false);
					// backup is window load event
					window.addEventListener("load", ready, false);
				} else {
					// must be IE
					document.attachEvent("onreadystatechange", readyStateChange);
					window.attachEvent("onload", ready);
				}
				readyEventHandlersInstalled = true;
			}
		}
	})("__onpWidgetsLoaded", window);

	/**
	 * Инициализируем загрузку библиотек и установку виджетов
	 */
	__onpWidgetsLoaded(function() {
		if( window._onpwgt == undefined ) {
			__onpwgtInstaller.console('error', 'Не установлены настройки для загрузки виджетов.');
			return;
		}
		__onpwgtInstaller.init();
	});
})();
