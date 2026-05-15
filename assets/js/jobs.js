/*!
 * Apqrinu Job Board - Frontend script
 * Handles AJAX filtering, related-jobs pagination, modal, and apply form.
 */
(function () {
	'use strict';

	if (typeof window.ApqrinuData === 'undefined') {
		return;
	}

	var data = window.ApqrinuData;
	var i18n = (data && data.i18n) || {};

	// ----- Filtering -----
	(function initFilters() {
		var resultsEl = document.getElementById('apqrinu-jobs-results');
		if (!resultsEl) return;

		function getStablePathname(pathname) {
			var p = pathname || window.location.pathname;
			return p.replace(/\/page\/\d+\/?$/, '/');
		}

		function safeDecode(str) {
			try {
				var decoded = decodeURIComponent(str);
				if (decoded !== str && decoded.indexOf('%') !== -1) {
					decoded = decodeURIComponent(decoded);
				}
				return decoded;
			} catch (e) {
				return str;
			}
		}

		function getCurrentFiltersFromURL() {
			var params = new URLSearchParams(window.location.search);
			var out = {};
			(data.taxes || []).forEach(function (tax) {
				var v = params.get(tax);
				if (v) out[tax] = safeDecode(v);
			});
			var paged = parseInt(params.get('paged') || '0', 10);
			if (!paged) {
				var m = window.location.pathname.match(/\/page\/(\d+)\/?$/);
				if (m) paged = parseInt(m[1], 10);
			}
			out.paged = paged && !isNaN(paged) ? paged : 1;
			return out;
		}

		function buildURL(filters) {
			var params = new URLSearchParams();
			(data.taxes || []).forEach(function (tax) {
				if (filters[tax]) params.set(tax, filters[tax]);
			});
			if (filters.paged && filters.paged > 1) {
				params.set('paged', String(filters.paged));
			}
			var qs = params.toString();
			return getStablePathname() + (qs ? '?' + qs : '');
		}

		function getPagedFromHref(href) {
			try {
				var url = new URL(href, window.location.origin);
				var p = parseInt(url.searchParams.get('paged') || '0', 10);
				if (!p) {
					var m = url.pathname.match(/\/page\/(\d+)\/?$/);
					if (m) p = parseInt(m[1], 10);
				}
				return p && !isNaN(p) ? p : 1;
			} catch (e) {
				return 1;
			}
		}

		function fetchJobs(filters, pushState) {
			resultsEl.classList.add('is-loading');
			var body = new URLSearchParams();
			body.set('action', 'apqrinu_filter_jobs');
			body.set('nonce', data.nonce);
			(data.taxes || []).forEach(function (tax) {
				if (filters[tax]) body.set(tax, filters[tax]);
			});
			body.set('paged', String(filters.paged || 1));
			body.set(
				'base_url',
				window.location.origin +
					window.location.pathname.replace(/\/page\/\d+\/?$/, '/')
			);

			return fetch(data.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type':
						'application/x-www-form-urlencoded; charset=UTF-8',
				},
				credentials: 'same-origin',
				body: body.toString(),
			})
				.then(function (r) {
					return r.json();
				})
				.then(function (json) {
					if (
						json &&
						json.success &&
						json.data &&
						typeof json.data.html === 'string'
					) {
						resultsEl.innerHTML = json.data.html;
						if (pushState) {
							window.history.pushState(
								filters,
								'',
								buildURL(filters)
							);
						}
						resultsEl.scrollIntoView({
							behavior: 'smooth',
							block: 'start',
						});
					}
				})
				.catch(function () {
					/* noop */
				})
				.then(function () {
					resultsEl.classList.remove('is-loading');
				});
		}

		function syncSelectActiveState(select) {
			if (!select) return;
			if (select.value) {
				select.classList.add('is-active');
			} else {
				select.classList.remove('is-active');
			}
		}

		document.addEventListener('change', function (e) {
			var t = e.target;
			if (!t || !t.classList || !t.classList.contains('apqrinu-filter-select')) {
				return;
			}
			syncSelectActiveState(t);
			var tax = t.getAttribute('data-tax');
			var value = t.value;
			var current = getCurrentFiltersFromURL();
			if (value) {
				current[tax] = value;
			} else {
				delete current[tax];
			}
			current.paged = 1;
			fetchJobs(current, true);
		});

		// Sync active class on initial render.
		document.querySelectorAll('.apqrinu-filter-select').forEach(syncSelectActiveState);

		document.addEventListener('click', function (e) {
			var clear = e.target.closest('.apqrinu-job-filters a.apqrinu-clear-filters');
			if (clear) {
				e.preventDefault();
				document
					.querySelectorAll('.apqrinu-filter-select')
					.forEach(function (s) {
						s.value = '';
						syncSelectActiveState(s);
					});
				fetchJobs({ paged: 1 }, true);
				return;
			}

			var pageLink = e.target.closest(
				'#apqrinu-jobs-results .apqrinu-job-pagination a'
			);
			if (pageLink) {
				e.preventDefault();
				var current = getCurrentFiltersFromURL();
				current.paged = getPagedFromHref(pageLink.href);
				fetchJobs(current, true);
			}
		});

		window.addEventListener('popstate', function () {
			var filters = getCurrentFiltersFromURL();
			(data.taxes || []).forEach(function (tax) {
				var s = document.querySelector(
					'.apqrinu-filter-select[data-tax="' + tax + '"]'
				);
				if (s) {
					s.value = filters[tax] || '';
					syncSelectActiveState(s);
				}
			});
			fetchJobs(filters, false);
		});

		(function initSelects() {
			var current = getCurrentFiltersFromURL();
			(data.taxes || []).forEach(function (tax) {
				var s = document.querySelector(
					'.apqrinu-filter-select[data-tax="' + tax + '"]'
				);
				if (s && current[tax]) s.value = current[tax];
				syncSelectActiveState(s);
			});
		})();
	})();

	// ----- Related jobs pager -----
	(function initRelated() {
		var section = document.querySelector('[data-apqrinu-related]');
		if (!section) return;
		var results = document.getElementById('apqrinu-jobs-related-results');
		var prevBtn = section.querySelector('[data-apqrinu-related-prev]');
		var nextBtn = section.querySelector('[data-apqrinu-related-next]');
		var pageEl = section.querySelector('[data-apqrinu-related-page]');
		var jobId = section.getAttribute('data-job-id');
		var max = parseInt(section.getAttribute('data-max') || '1', 10);

		function setButtons(current) {
			if (prevBtn) prevBtn.disabled = current <= 1;
			if (nextBtn) nextBtn.disabled = current >= max;
			if (pageEl) pageEl.textContent = String(current);
			section.setAttribute('data-current', String(current));
		}

		function load(page) {
			if (!results) return;
			results.classList.add('is-loading');
			var body = new URLSearchParams();
			body.set('action', 'apqrinu_related_page');
			body.set('nonce', data.nonce);
			body.set('job_id', jobId);
			body.set('paged', String(page));

			fetch(data.ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type':
						'application/x-www-form-urlencoded; charset=UTF-8',
				},
				credentials: 'same-origin',
				body: body.toString(),
			})
				.then(function (r) {
					return r.json();
				})
				.then(function (json) {
					if (
						json &&
						json.success &&
						json.data &&
						typeof json.data.html === 'string'
					) {
						results.innerHTML = json.data.html;
						setButtons(json.data.current || page);
						section.scrollIntoView({
							behavior: 'smooth',
							block: 'start',
						});
					}
				})
				.catch(function () {
					/* noop */
				})
				.then(function () {
					results.classList.remove('is-loading');
				});
		}

		setButtons(1);

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				var current = parseInt(
					section.getAttribute('data-current') || '1',
					10
				);
				if (current > 1) load(current - 1);
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				var current = parseInt(
					section.getAttribute('data-current') || '1',
					10
				);
				if (current < max) load(current + 1);
			});
		}
	})();

	// ----- Modal -----
	(function initModal() {
		var modal = document.getElementById('apqrinu-job-apply-modal');
		if (!modal) return;
		var openBtns = document.querySelectorAll('[data-apqrinu-open-apply]');
		var closeEls = document.querySelectorAll('[data-apqrinu-close-apply]');

		function open() {
			modal.setAttribute('aria-hidden', 'false');
			document.body.style.overflow = 'hidden';
		}
		function close() {
			modal.setAttribute('aria-hidden', 'true');
			document.body.style.overflow = '';
		}

		openBtns.forEach(function (b) {
			b.addEventListener('click', open);
		});
		closeEls.forEach(function (b) {
			b.addEventListener('click', close);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') close();
		});
	})();

	// ----- Apply form -----
	(function initApply() {
		var forms = document.querySelectorAll('[data-apqrinu-apply-form]');
		if (!forms.length) return;

		forms.forEach(function (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();

				var status = form.querySelector('.apqrinu-form-status');
				var btn = form.querySelector('button[type="submit"]');

				// Honeypot check.
				var hp = form.querySelector('[name="apqrinu_hp"]');
				if (hp && hp.value) {
					return;
				}

				// Basic client-side validation.
				var nameEl = form.querySelector('[name="applicant_name"]');
				var emailEl = form.querySelector('[name="applicant_email"]');
				if (!nameEl || !nameEl.value.trim()) {
					setStatus(
						status,
						i18n.requiredField || 'This field is required.',
						'is-error'
					);
					if (nameEl) nameEl.focus();
					return;
				}
				if (
					!emailEl ||
					!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value.trim())
				) {
					setStatus(
						status,
						i18n.invalidEmail ||
							'Please enter a valid email address.',
						'is-error'
					);
					if (emailEl) emailEl.focus();
					return;
				}

				if (btn) btn.disabled = true;
				setStatus(status, i18n.submitting || 'Submitting…', '');

				// FormData already includes the apqrinu_apply_nonce hidden
				// field rendered by wp_nonce_field() in apply-form.php —
				// that is the nonce the PHP handler validates. Do NOT
				// overwrite it with the generic ApqrinuData.nonce (that one
				// is scoped to the listing/related AJAX actions).
				var fd = new FormData(form);
				fd.set('action', 'apqrinu_apply_submit');

				fetch(data.ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: fd,
				})
					.then(function (r) {
						return r.json();
					})
					.then(function (json) {
						if (json && json.success) {
							setStatus(
								status,
								(json.data && json.data.message) ||
									i18n.success ||
									'Sent.',
								'is-success'
							);
							form.reset();
						} else {
							setStatus(
								status,
								(json &&
									json.data &&
									json.data.message) ||
									i18n.error ||
									'Error.',
								'is-error'
							);
						}
					})
					.catch(function () {
						setStatus(
							status,
							i18n.error || 'Error.',
							'is-error'
						);
					})
					.then(function () {
						if (btn) btn.disabled = false;
					});
			});
		});

		function setStatus(el, text, cls) {
			if (!el) return;
			el.textContent = text;
			el.classList.remove('is-error', 'is-success');
			if (cls) el.classList.add(cls);
		}
	})();
})();
