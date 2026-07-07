(function () {
	'use strict';

	if (typeof bspSettings === 'undefined') {
		return;
	}

	function bspEscapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function bspShowToast(msg, type) {
		var toast = document.getElementById('bsp-toast');
		if (!toast) {
			return;
		}
		toast.textContent = msg;
		toast.className = (type || 'success') + ' show';
		setTimeout(function () {
			toast.className = toast.className.replace('show', '');
		}, 3000);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var scanBtn = document.getElementById('bsp-btn-scan');
		if (scanBtn) {
			scanBtn.addEventListener('click', function () {
				var btn = this;
				var logText = document.getElementById('bsp-scan-log-text');
				var originalBtnText = btn.textContent;

				btn.textContent = bspSettings.i18n.scanning;
				btn.style.pointerEvents = 'none';
				logText.textContent = bspSettings.i18n.scanInProgress;

				var data = new FormData();
				data.append('action', bspSettings.scanAction);
				data.append('_wpnonce', bspSettings.nonce);

				fetch(bspSettings.ajaxUrl, { method: 'POST', body: data })
					.then(function (response) {
						return response.json();
					})
					.then(function (res) {
						if (res.success) {
							logText.innerHTML = bspEscapeHtml(res.data.log).replace(/\n/g, '<br>');
							document.querySelectorAll('textarea[id^="scanned-map-"]').forEach(function (ta) {
								ta.value = bspSettings.i18n.noPagesDetected;
							});
							for (var postType in res.data.map) {
								if (!Object.prototype.hasOwnProperty.call(res.data.map, postType)) {
									continue;
								}
								var urls = res.data.map[postType];
								var textarea = document.getElementById('scanned-map-' + postType);
								if (textarea && urls.length > 0) {
									textarea.value = urls.join('\n');
								}
							}
							bspShowToast(bspSettings.i18n.scanComplete, 'success');
						} else {
							logText.innerHTML = '<span class="bsp-scan-log-error">' + bspEscapeHtml(bspSettings.i18n.scanFailed) + '</span>';
							bspShowToast(bspSettings.i18n.scanFailedShort, 'error');
						}
						btn.textContent = originalBtnText;
						btn.style.pointerEvents = 'auto';
					})
					.catch(function () {
						bspShowToast(bspSettings.i18n.serverError, 'error');
						btn.textContent = originalBtnText;
						btn.style.pointerEvents = 'auto';
					});
			});
		}

		document.querySelectorAll('.bsp-btn-save').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var currentBtn = this;
				var originalBtnText = currentBtn.textContent;

				currentBtn.textContent = bspSettings.i18n.saving;
				currentBtn.style.pointerEvents = 'none';

				var form = document.getElementById('bsp-settings-form');
				var data = new FormData(form);
				data.append('action', bspSettings.saveAction);

				fetch(bspSettings.ajaxUrl, { method: 'POST', body: data })
					.then(function (response) {
						return response.json();
					})
					.then(function (res) {
						if (res.success) {
							bspShowToast(bspSettings.i18n.saveSuccess, 'success');
						} else {
							bspShowToast(bspSettings.i18n.saveError, 'error');
						}
						currentBtn.textContent = originalBtnText;
						currentBtn.style.pointerEvents = 'auto';
					})
					.catch(function () {
						bspShowToast(bspSettings.i18n.serverError, 'error');
						currentBtn.textContent = originalBtnText;
						currentBtn.style.pointerEvents = 'auto';
					});
			});
		});
	});
})();
