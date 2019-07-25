if( !__onpwgtInstaller ) {
	var __$onp = null;
	var __onpwgtInstaller = {

		_JQUERY_VERSIONS: [
			"1.10.2", "1.10.1", "1.10.0", "1.9.1", "1.9.0", "1.8.3", "1.8.2", "1.8.1", "1.8.0", "1.7.2",
			"1.7.1", "1.7.0"
		],

		_CDN_URL: '//cdn.sociallocker.ru/sl-libs/',
		_CSS_DIR: 'css/',
		_IMG_DIR: 'img/',
		_build: null,

		init: function() {
			var self = this;

			this._build = window.__onpwgt_global_options.build;

			if( void 0 !== window.jQuery && 0 <= this._JQUERY_VERSIONS.indexOf.call(jQuery.fn.jquery) ) {
				this.console("alert", "jQuery " + jQuery.fn.jquery + " is present and meets our requirements.");
				__$onp = window.jQuery;
				this._loadLibs();
				return;
			}

			this.console("alert", "jQuery is not present.");

			this._getScripts('//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', function() {
				if( "undefined" !== typeof jQuery ) {
					__$onp = jQuery.noConflict(true);
					self.console("success", "jQuery version " + __$onp.fn.jquery + " successfully loaded.");
					self._loadLibs();
				} else {
					self.console("error", "Fail to load jQuery!");
				}
			}, true);
		},

		_loadLibs: function() {
			var self = this,
				$ = __$onp,
				css = ['pandalocker.' + this._build + '.min.css'];

			for( var k = 0; k < css.length; k++ ) {
				$("<link/>", {
					rel: "stylesheet",
					type: "text/css",
					href: self._CDN_URL + self._CSS_DIR + css[k]
				}).appendTo("head");
			}

			var promise = $.when();

			// Блик эффект для плагина
			promise = self._loadScript(promise, 'libs/jquery.ui.highlight.min.js');

			// Ядро
			promise = self._loadScript(promise, 'pandalocker.' + this._build + '.min.js');

			promise.then(function() {
				self.console("success", "all libs successfully loaded");
				self._start();
			});
		},

		_loadScript: function(promise, file, callback) {
			var self = this, $ = __$onp;

			return promise.then(function() {
				return $.ajax({
					url: self._CDN_URL + file,
					dataType: "script",
					cache: true
				}).then(function() {
					callback && callback();
					self.console("success", "Library " + file + " successfully loaded");
				});
			})
		},

		_start: function() {
			var self = this;
			var lockerOptions, $ = __$onp;

			var globalOptions = window.__onpwgt_global_options || {};

			if( !$.pandalocker.lse ) {
				$.pandalocker.lse = {};
			}

			$.pandalocker.lse.autoLoadUrl = globalOptions.external_options_url;
			$.pandalocker.lse.externalOptionsUrl = globalOptions.external_options_url;

			if( this._build == 'premium' ) {
				$.pandalocker.lse.allowDomains = globalOptions.allow_domains;

				$.pandalocker.lse.gmst = function() {
					$.pandalocker.gmodules = true;

					var modules = globalOptions.gmst_modules;
					return $.pandalocker.tools.egmit(modules);
				};
			}

			if( !globalOptions.locker_options ) {
				self.console('error', 'Не переданы настройки замка');
				return;
			}

			if( !globalOptions.locker_options.id && globalOptions.locker_id ) {
				globalOptions.locker_options['id'] = globalOptions.locker_id;
			}

			$('.' + globalOptions.content_selector).sociallocker(globalOptions.locker_options);
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
}

window.onload = function() {
	if( window.__onpwgt_global_options == undefined ) {
		__onpwgtInstaller.console('error', 'Не загружены настройки замка, пожалуйста, проверьте правильность подключения файла настроек.');
		return;
	}
	__onpwgtInstaller.init();
};