/* PPSPB realtime scan polling — cache-bust touch 2026-07-07 */
(function () {
	'use strict';

	if (typeof ppspbSettings === 'undefined') {
		return;
	}

	var ppspbScanPollTimer = null;

	function ppspbEscapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function ppspbShowToast(msg, type) {
		var toast = document.getElementById('ppspb-toast');
		if (!toast) {
			return;
		}
		toast.textContent = msg;
		toast.className = (type || 'success') + ' show';
		setTimeout(function () {
			toast.className = toast.className.replace('show', '');
		}, 3000);
	}

	function ppspbGetScanLogContainer(logText) {
		return logText.closest('[style*="overflow-y"]') || logText.parentElement;
	}

	function ppspbRenderScanLog(logText, log, progress) {
		var html = ppspbEscapeHtml(log || '');
		if (progress) {
			html += (html ? '<br>' : '') + '<span class="ppspb-scan-progress">' + ppspbEscapeHtml(progress) + '</span>';
		}
		logText.innerHTML = html.replace(/\n/g, '<br>');

		var container = ppspbGetScanLogContainer(logText);
		if (container) {
			container.scrollTop = container.scrollHeight;
		}
	}

	function ppspbStopScanPoll() {
		if (ppspbScanPollTimer) {
			clearInterval(ppspbScanPollTimer);
			ppspbScanPollTimer = null;
		}
	}

	function ppspbStartScanPoll(logText) {
		ppspbStopScanPoll();

		ppspbScanPollTimer = setInterval(function () {
			var statusData = new FormData();
			statusData.append('action', ppspbSettings.statusAction);
			statusData.append('_wpnonce', ppspbSettings.nonce);

			fetch(ppspbSettings.ajaxUrl, { method: 'POST', body: statusData })
				.then(function (response) {
					return response.json();
				})
				.then(function (res) {
					if (!res.success || !res.data) {
						return;
					}

					if (res.data.status === 'running') {
						ppspbRenderScanLog(logText, res.data.log, res.data.progress);
					}
				})
				.catch(function () {
					// Polling errors are non-fatal; the main scan request still runs.
				});
		}, 500);
	}

	function ppspbApplyScannedMap(map) {
		document.querySelectorAll('textarea[id^="scanned-map-"]').forEach(function (ta) {
			ta.value = ppspbSettings.i18n.noPagesDetected;
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

	function ppspbInitSettingsUi() {
		var scanBtn = document.getElementById('ppspb-btn-scan');
		if (scanBtn) {
			scanBtn.addEventListener('click', function () {
				var btn = this;
				var logText = document.getElementById('ppspb-scan-log-text');
				var originalBtnText = btn.textContent;

				if (!logText) {
					return;
				}

				btn.textContent = ppspbSettings.i18n.scanning;
				btn.style.pointerEvents = 'none';
				ppspbRenderScanLog(logText, '', ppspbSettings.i18n.scanStarting);
				ppspbStartScanPoll(logText);

				var data = new FormData();
				data.append('action', ppspbSettings.scanAction);
				data.append('_wpnonce', ppspbSettings.nonce);

				fetch(ppspbSettings.ajaxUrl, { method: 'POST', body: data })
					.then(function (response) {
						return response.json();
					})
					.then(function (res) {
						ppspbStopScanPoll();

						if (res.success) {
							ppspbRenderScanLog(logText, res.data.log, '');
							ppspbApplyScannedMap(res.data.map);
							ppspbShowToast(ppspbSettings.i18n.scanComplete, 'success');
						} else {
							logText.innerHTML = '<span class="ppspb-scan-log-error">' + ppspbEscapeHtml(ppspbSettings.i18n.scanFailed) + '</span>';
							ppspbShowToast(ppspbSettings.i18n.scanFailedShort, 'error');
						}
						btn.textContent = originalBtnText;
						btn.style.pointerEvents = 'auto';
					})
					.catch(function () {
						ppspbStopScanPoll();
						ppspbShowToast(ppspbSettings.i18n.serverError, 'error');
						btn.textContent = originalBtnText;
						btn.style.pointerEvents = 'auto';
					});
			});
		}

		document.querySelectorAll('.ppspb-btn-save').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var currentBtn = this;
				var originalBtnText = currentBtn.textContent;

				currentBtn.textContent = ppspbSettings.i18n.saving;
				currentBtn.style.pointerEvents = 'none';

				var form = document.getElementById('ppspb-settings-form');
				var data = new FormData(form);
				data.append('action', ppspbSettings.saveAction);

				fetch(ppspbSettings.ajaxUrl, { method: 'POST', body: data })
					.then(function (response) {
						return response.json();
					})
					.then(function (res) {
						if (res.success) {
							ppspbShowToast(ppspbSettings.i18n.saveSuccess, 'success');
						} else {
							ppspbShowToast(ppspbSettings.i18n.saveError, 'error');
						}
						currentBtn.textContent = originalBtnText;
						currentBtn.style.pointerEvents = 'auto';
					})
					.catch(function () {
						ppspbShowToast(ppspbSettings.i18n.serverError, 'error');
						currentBtn.textContent = originalBtnText;
						currentBtn.style.pointerEvents = 'auto';
					});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', ppspbInitSettingsUi);
	} else {
		ppspbInitSettingsUi();
	}
})();
