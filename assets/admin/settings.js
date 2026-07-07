/* BSP realtime scan polling — cache-bust touch 2026-07-07 */
(function () {
	'use strict';

	if (typeof bspSettings === 'undefined') {
		return;
	}

	var bspScanPollTimer = null;

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

	function bspGetScanLogContainer(logText) {
		return logText.closest('[style*="overflow-y"]') || logText.parentElement;
	}

	function bspRenderScanLog(logText, log, progress) {
		var html = bspEscapeHtml(log || '');
		if (progress) {
			html += (html ? '<br>' : '') + '<span class="bsp-scan-progress">' + bspEscapeHtml(progress) + '</span>';
		}
		logText.innerHTML = html.replace(/\n/g, '<br>');

		var container = bspGetScanLogContainer(logText);
		if (container) {
			container.scrollTop = container.scrollHeight;
		}
	}

	function bspStopScanPoll() {
		if (bspScanPollTimer) {
			clearInterval(bspScanPollTimer);
			bspScanPollTimer = null;
		}
	}

	function bspStartScanPoll(logText) {
		bspStopScanPoll();

		bspScanPollTimer = setInterval(function () {
			var statusData = new FormData();
			statusData.append('action', bspSettings.statusAction);
			statusData.append('_wpnonce', bspSettings.nonce);

			fetch(bspSettings.ajaxUrl, { method: 'POST', body: statusData })
				.then(function (response) {
					return response.json();
				})
				.then(function (res) {
					if (!res.success || !res.data) {
						return;
					}

					if (res.data.status === 'running') {
						bspRenderScanLog(logText, res.data.log, res.data.progress);
					}
				})
				.catch(function () {
					// Polling errors are non-fatal; the main scan request still runs.
				});
		}, 500);
	}

	function bspApplyScannedMap(map) {
		document.querySelectorAll('textarea[id^="scanned-map-"]').forEach(function (ta) {
			ta.value = bspSettings.i18n.noPagesDetected;
		});

		for (var postType in map) {
			if (!Object.prototype.hasOwnProperty.call(map, postType)) {
				continue;
			}
			var urls = map[postType];
			var textarea = document.getElementById('scanned-map-' + postType);
			if (textarea && urls.length > 0) {
				textarea.value = urls.join('\n');
			}
		}
	}

	function bspInitSettingsUi() {
		var scanBtn = document.getElementById('bsp-btn-scan');
		if (scanBtn) {
			scanBtn.addEventListener('click', function () {
				var btn = this;
				var logText = document.getElementById('bsp-scan-log-text');
				var originalBtnText = btn.textContent;

				if (!logText) {
					return;
				}

				btn.textContent = bspSettings.i18n.scanning;
				btn.style.pointerEvents = 'none';
				bspRenderScanLog(logText, '', bspSettings.i18n.scanStarting);
				bspStartScanPoll(logText);

				var data = new FormData();
				data.append('action', bspSettings.scanAction);
				data.append('_wpnonce', bspSettings.nonce);

				fetch(bspSettings.ajaxUrl, { method: 'POST', body: data })
					.then(function (response) {
						return response.json();
					})
					.then(function (res) {
						bspStopScanPoll();

						if (res.success) {
							bspRenderScanLog(logText, res.data.log, '');
							bspApplyScannedMap(res.data.map);
							bspShowToast(bspSettings.i18n.scanComplete, 'success');
						} else {
							logText.innerHTML = '<span class="bsp-scan-log-error">' + bspEscapeHtml(bspSettings.i18n.scanFailed) + '</span>';
							bspShowToast(bspSettings.i18n.scanFailedShort, 'error');
						}
						btn.textContent = originalBtnText;
						btn.style.pointerEvents = 'auto';
					})
					.catch(function () {
						bspStopScanPoll();
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
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bspInitSettingsUi);
	} else {
		bspInitSettingsUi();
	}
})();
