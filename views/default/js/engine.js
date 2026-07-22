/**
 * Modernized engine.js - Vanilla JS (ES6+)
 * Refactored to eliminate jQuery dependency and modernise performance/SEO.
 */

document.addEventListener('DOMContentLoaded', () => {

	// Helper to resolve AJAX URLs using current browser origin and basePath to bypass CORS when using <base href>
	function getAjaxUrl(relativePath) {
		const baseEl = document.querySelector('base');
		let basePath = '/';
		if (baseEl && baseEl.href) {
			try {
				const baseUrl = new URL(baseEl.href);
				basePath = baseUrl.pathname;
				if (!basePath.endsWith('/')) {
					basePath += '/';
				}
			} catch (e) {
				console.error('Error parsing base href:', e);
			}
		}
		const cleanPath = relativePath.startsWith('/') ? relativePath.substring(1) : relativePath;
		return window.location.origin + basePath + cleanPath;
	}

	// 1. Select State Changes
	document.querySelectorAll('.select_state').forEach(selectEl => {
		selectEl.addEventListener('change', function() {
			const val = this.value;
			document.querySelectorAll('.substates').forEach(sub => {
				sub.style.display = 'none';
				sub.querySelectorAll('select').forEach(sel => sel.disabled = true);
			});
			const targetSub = document.querySelector('.substate_' + val);
			if (targetSub) {
				targetSub.style.display = '';
				targetSub.querySelectorAll('select').forEach(sel => sel.disabled = false);
			}
		});
	});

	// 2. Ajax Confirm buttons
	document.querySelectorAll('.ajax_confirm').forEach(el => {
		el.addEventListener('click', function(e) {
			e.preventDefault();
			const isConfirmed = confirm(this.getAttribute('data-title'));
			if (isConfirmed) {
				const mydata = Object.assign({}, this.dataset);
				const formData = new URLSearchParams();
				for (const key in mydata) {
					formData.append(key, mydata[key]);
				}
				fetch(getAjaxUrl('php/ajax.php'), {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: formData.toString()
				})
				.then(() => {
					window.location.reload();
				})
				.catch(err => {
					console.error('AJAX confirm error:', err);
					window.location.reload();
				});
			}
		});
	});

	// 3. Form Search cleaning (disabling empty inputs on submit to keep URLs clean)
	document.querySelectorAll('.form-search').forEach(form => {
		form.addEventListener('submit', function() {
			const addressInput = this.querySelector('[name="address"]');
			let flag = true;
			if (!addressInput || addressInput.value === '') {
				const distanceInput = this.querySelector('[name="distance"]');
				if (distanceInput) {
					distanceInput.disabled = true;
				}
			}
			const inputs = Array.from(this.querySelectorAll('input:not([type="submit"]):not([name="search"]), select, textarea'));
			inputs.forEach(input => {
				if (!input.disabled) {
					if (input.value === '' && (!input.defaultValue || input.defaultValue === '')) {
						input.disabled = true;
					} else {
						flag = false;
					}
				}
			});
			if (flag) {
				const searchBtn = this.querySelector('[name="search"]');
				if (searchBtn) {
					searchBtn.disabled = true;
				}
			}
		});
	});

	// 4. Scroll behavior for Sticky Menu & Back to top button
	const menuEl = document.getElementById('menu_box');
	const topEl = document.getElementById('top');
	const backToTopBtn = document.getElementById('back_to_top');
	const formSearchOffers = document.getElementById('form_search_offers');

	function scroll() {
		const scrollTop = window.scrollY || document.documentElement.scrollTop;
		const topHeight = topEl ? topEl.offsetHeight : 0;
		if (menuEl) {
			menuEl.style.top = topHeight + 'px';
		}
		if (backToTopBtn) {
			if (scrollTop > 150) {
				backToTopBtn.classList.remove('back_to_top_hidden');
			} else {
				backToTopBtn.classList.add('back_to_top_hidden');
			}
		}
	}
	scroll();
	window.addEventListener('scroll', scroll);

	// 5. Responsive Search forms
	function resize() {
		if (formSearchOffers) {
			if (window.innerWidth < 992) {
				formSearchOffers.classList.remove('show');
			} else {
				formSearchOffers.classList.add('show');
			}
		}
	}
	resize();
	window.addEventListener('resize', resize);

	// 6. Return false links
	document.querySelectorAll('.return_false a').forEach(a => {
		a.addEventListener('click', function(e) {
			e.preventDefault();
			this.blur();
		});
	});

	// 7. Smooth Scroll back to top
	if (backToTopBtn) {
		backToTopBtn.addEventListener('click', function(e) {
			e.preventDefault();
			window.scrollTo({ top: 0, behavior: 'smooth' });
			this.blur();
		});
	}

	// Helper to track clicks
	function trackClick(offerId, clickType, token) {
		if (!offerId || !clickType || !token) return;
		const formData = new URLSearchParams();
		formData.append('action', 'increment_click');
		formData.append('offer_id', offerId);
		formData.append('click_type', clickType);
		formData.append('token', token);

		fetch(getAjaxUrl('php/ajax.php'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: formData.toString()
		})
		.then(res => res.json())
		.then(data => {
			if (!data.status) {
				console.warn('Click tracking failed:', data.info);
			}
		})
		.catch(err => {
			console.error('Click tracking error:', err);
		});
	}

	// 8. Show hidden contact data (phones / emails)
	document.querySelectorAll('.show_hidden_data').forEach(el => {
		el.addEventListener('click', function(e) {
			e.preventDefault();
			const parent = this.closest('a');
			const type = this.getAttribute('data-type');
			let data = '';
			let href = '';
			if (type === 'phone') {
				data = this.getAttribute('data-data');
				href = 'tel:' + data;
			} else if (type === 'email') {
				data = this.getAttribute('data-data_0') + '@' + this.getAttribute('data-data_1');
				href = 'mailto:' + data;
			} else {
				data = this.getAttribute('data-data');
				href = data;
			}
			if (parent) {
				parent.setAttribute('href', href);
				this.outerHTML = data;
			} else {
				const a = document.createElement('a');
				a.setAttribute('href', href);
				a.className = this.className.replace('show_hidden_data', '').trim();
				if (type === 'phone') {
					a.innerHTML = '<i class="bi bi-telephone-fill me-2"></i>' + data;
				} else if (type === 'email') {
					a.innerHTML = '<i class="bi bi-envelope-at me-2"></i>' + data;
				} else {
					a.innerHTML = data;
				}
				this.parentNode.replaceChild(a, this);
			}

			const offerId = this.getAttribute('data-id');
			const token = this.getAttribute('data-token');
			if (offerId && token) {
				trackClick(offerId, type, token);
			}
		});
	});

	// Track website clicks
	document.querySelectorAll('.track_website_click').forEach(el => {
		el.addEventListener('click', function() {
			const offerId = this.getAttribute('data-id');
			const token = this.getAttribute('data-token');
			if (offerId && token) {
				trackClick(offerId, 'website', token);
			}
		});
	});

	// Track visible phone clicks
	document.querySelectorAll('.track_phone_click').forEach(el => {
		el.addEventListener('click', function() {
			const offerId = this.getAttribute('data-id');
			const token = this.getAttribute('data-token');
			if (offerId && token) {
				trackClick(offerId, 'phone', token);
			}
		});
	});

	// Global share page helper
	document.querySelectorAll('.share_page_btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const title = this.getAttribute('data-title') || document.title;
			const text = this.getAttribute('data-text') || '';
			const url = this.getAttribute('data-url') || window.location.href;
			
			const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
			const isStandalone = window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;
			const useNativeShare = navigator.share && !(isIOS && isStandalone);

			const fallbackCopy = () => {
				navigator.clipboard.writeText(url).then(() => {
					if (typeof showToast === 'function') {
						showToast('Link został skopiowany do schowka!');
					} else {
						alert('Link został skopiowany do schowka: ' + url);
					}
				}).catch(err => {
					console.error('Could not copy text: ', err);
				});
			};

			if (useNativeShare) {
				navigator.share({
					title: title,
					text: text,
					url: url
				}).catch(err => {
					console.warn('Sharing failed:', err);
					if (err.name !== 'AbortError') {
						fallbackCopy();
					}
				});
			} else {
				fallbackCopy();
			}
		});
	});

	// 9. Reset form helper
	document.querySelectorAll('.reset_form').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const form = this.closest('form');
			if (form) {
				form.querySelectorAll('input').forEach(input => {
					const type = input.getAttribute('type');
					if (type === 'text' || type === 'number') {
						input.value = '';
					} else if (type === 'radio' || type === 'checkbox') {
						input.checked = false;
					}
				});
				form.querySelectorAll('select').forEach(select => {
					select.selectedIndex = 0;
				});
			}
		});
	});

	// 10. Categories and Subcategories repositioning grid
	document.querySelectorAll('.index_show_subcategories').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const active = this.classList.contains('active');
			const id = this.getAttribute('data-id');
			const subcategories = document.getElementById('index_subcategory_' + id);

			document.querySelectorAll('.index_subcategories').forEach(el => el.style.display = 'none');
			document.querySelectorAll('.index_show_subcategories').forEach(el => el.classList.remove('active'));

			if (!active && subcategories) {
				const indexVal = parseInt(this.getAttribute('data-index'), 10);
				let eq = indexVal;
				const windowWidth = window.innerWidth;
				if (windowWidth < 540) {
					eq = indexVal - 1;
				} else if (windowWidth < 768) {
					if (indexVal % 2 === 0) {
						eq = indexVal - 1;
					}
				} else if (windowWidth < 992) {
					const mod = indexVal % 3;
					if (mod === 0) {
						eq = indexVal - 1;
					} else if (mod === 1) {
						eq = indexVal + 1;
					}
				} else {
					const mod = indexVal % 4;
					if (mod === 0) {
						eq = indexVal - 1;
					} else if (mod === 1) {
						eq = indexVal + 2;
					} else if (mod === 2) {
						eq = indexVal + 1;
					}
				}
				const categories = document.querySelectorAll('.index_categories');
				const targetCategory = categories[eq];
				if (targetCategory) {
					targetCategory.parentNode.insertBefore(subcategories, targetCategory.nextSibling);
					subcategories.style.display = 'block';
					this.classList.add('active');
				}
			}
			this.blur();
		});
	});

	// 11. Rodo modal popup
	if (!localStorage.rodo_accepted) {
		const rodoMessage = document.getElementById('rodo-message');
		if (rodoMessage && typeof bootstrap !== 'undefined') {
			bootstrap.Modal.getOrCreateInstance(rodoMessage).show();
		}
	}

	// 12. Dark Mode Toggle Logic
	const toggleBtn = document.getElementById('dark-mode-toggle');
	const modeIcon = document.getElementById('dark-mode-icon');
	
	function updateIcon(theme) {
		if (!modeIcon) return;
		if (theme === 'dark') {
			modeIcon.classList.remove('bi-moon-fill');
			modeIcon.classList.add('bi-sun-fill');
		} else {
			modeIcon.classList.remove('bi-sun-fill');
			modeIcon.classList.add('bi-moon-fill');
		}
	}
	
	// Initialize theme icon
	const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
	updateIcon(currentTheme);
	
	if (toggleBtn) {
		toggleBtn.addEventListener('click', () => {
			const theme = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
			document.documentElement.setAttribute('data-bs-theme', theme);
			localStorage.setItem('theme', theme);
			updateIcon(theme);
		});
	}

	// 13. Custom Live Search Autocomplete Function
	function setupLiveSearch(inputElement, suggestionsContainer) {
		if (!inputElement || !suggestionsContainer) return;

		let debounceTimer;

		inputElement.addEventListener('input', function() {
			clearTimeout(debounceTimer);
			const phrase = this.value.trim();

			if (phrase.length < 2) {
				suggestionsContainer.innerHTML = '';
				suggestionsContainer.style.display = 'none';
				return;
			}

			debounceTimer = setTimeout(() => {
				fetch(getAjaxUrl(`php/ajax.php?action=offers_sugested_keywords&keywords=${encodeURIComponent(phrase)}`))
					.then(response => response.json())
					.then(data => {
						suggestionsContainer.innerHTML = '';
						if (Array.isArray(data) && data.length > 0) {
							data.forEach(item => {
								const itemEl = document.createElement('div');
								itemEl.className = 'search-suggestions-item';
								itemEl.innerHTML = `<i class="bi bi-search"></i><span>${item}</span>`;
								itemEl.addEventListener('click', () => {
									inputElement.value = item;
									suggestionsContainer.innerHTML = '';
									suggestionsContainer.style.display = 'none';
									// Automatically submit parent form if exists
									const form = inputElement.closest('form');
									if (form) {
										form.submit();
									}
								});
								suggestionsContainer.appendChild(itemEl);
							});
							suggestionsContainer.style.display = 'block';
						} else {
							suggestionsContainer.style.display = 'none';
						}
					})
					.catch(err => {
						console.error('Live search autocomplete error:', err);
						suggestionsContainer.style.display = 'none';
					});
			}, 250);
		});

		// Hide dropdown on clicking outside or ESC key
		document.addEventListener('click', (e) => {
			if (!inputElement.contains(e.target) && !suggestionsContainer.contains(e.target)) {
				suggestionsContainer.style.display = 'none';
			}
		});

		inputElement.addEventListener('keydown', (e) => {
			if (e.key === 'Escape') {
				suggestionsContainer.style.display = 'none';
			}
		});
	}

	// Initialize Live Search for Homepage and Sidebar search inputs
	setupLiveSearch(document.getElementById('search_keywords'), document.getElementById('search_keywords_suggestions'));
	setupLiveSearch(document.getElementById('keywords'), document.getElementById('sidebar_keywords_suggestions'));

	// 14. Auto-submit search form on filter change (state, categories, options, etc.)
	const filterForm = document.getElementById('form_search_offers');
	if (filterForm) {
		filterForm.querySelectorAll('select, input[type="checkbox"], input[type="radio"]').forEach(el => {
			el.addEventListener('change', function() {
				// Only submit if the element is not inside a disabled or hidden container
				if (!this.disabled && this.offsetParent !== null) {
					// Clean up empty form fields before auto-submitting
					const addressInput = filterForm.querySelector('[name="address"]');
					if (!addressInput || addressInput.value === '') {
						const distanceInput = filterForm.querySelector('[name="distance"]');
						if (distanceInput) {
							distanceInput.disabled = true;
						}
					}
					
					// Temporarily disable empty values to keep URL query string clean
					filterForm.querySelectorAll('input:not([type="submit"]):not([name="search"]), select').forEach(input => {
						if (input.value === '' && !input.disabled) {
							input.disabled = true;
						}
					});

					filterForm.submit();
				}
			});
		});
	}

	// =========================================================================
	// 15. Compare Offers Business Feature
	// =========================================================================
	let compareIds = [];
	try {
		compareIds = JSON.parse(localStorage.getItem('compare_offers_ids') || '[]');
		if (!Array.isArray(compareIds)) compareIds = [];
	} catch (e) {
		compareIds = [];
	}
	compareIds = compareIds.map(x => parseInt(x, 10)).filter(x => !isNaN(x));

	function updateCompareWidget() {
		const widget = document.getElementById('compare-floating-widget');
		const countSpan = document.getElementById('compare-widget-count');
		if (widget && countSpan) {
			countSpan.textContent = compareIds.length;
			if (compareIds.length > 0) {
				widget.classList.remove('d-none');
			} else {
				widget.classList.add('d-none');
			}
		}

		// Update all compare checkboxes on the page
		document.querySelectorAll('.compare-checkbox').forEach(cb => {
			cb.checked = compareIds.includes(parseInt(cb.value, 10));
		});

		// Update single compare button (offer detail page)
		document.querySelectorAll('.compare-btn-single').forEach(btn => {
			const id = parseInt(btn.getAttribute('data-id'), 10);
			if (compareIds.includes(id)) {
				btn.classList.remove('btn-outline-warning');
				btn.classList.add('btn-warning');
				btn.innerHTML = `<i class="bi bi-arrow-left-right me-1"></i>Usuń z porównania`;
			} else {
				btn.classList.remove('btn-warning');
				btn.classList.add('btn-outline-warning');
				btn.innerHTML = `<i class="bi bi-arrow-left-right me-1"></i>Porównaj`;
			}
		});
	}

	function toggleCompareId(id) {
		id = parseInt(id, 10);
		if (compareIds.includes(id)) {
			compareIds = compareIds.filter(x => x !== id);
		} else {
			if (compareIds.length >= 4) {
				alert("Możesz porównać maksymalnie 4 oferty.");
				return false;
			}
			compareIds.push(id);
		}
		localStorage.setItem('compare_offers_ids', JSON.stringify(compareIds));
		updateCompareWidget();
		return true;
	}

	// Attach compare checkbox handlers
	document.addEventListener('change', e => {
		if (e.target.classList.contains('compare-checkbox')) {
			const id = parseInt(e.target.value, 10);
			const success = toggleCompareId(id);
			if (!success) {
				e.target.checked = !e.target.checked;
			}
		}
	});

	// Attach single compare button handlers
	document.querySelectorAll('.compare-btn-single').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const id = parseInt(this.getAttribute('data-id'), 10);
			toggleCompareId(id);
		});
	});

	// Initialize comparison UI on load
	updateCompareWidget();

	// Populate Compare Modal Table
	const compareModalEl = document.getElementById('compareOffersModal');
	if (compareModalEl) {
		compareModalEl.addEventListener('show.bs.modal', () => {
			const tableContainer = document.getElementById('compare-table-container');
			const emptyMsg = document.getElementById('compare-empty-message');
			const table = document.getElementById('compare-table');

			if (compareIds.length === 0) {
				if (tableContainer) tableContainer.classList.add('d-none');
				if (emptyMsg) emptyMsg.classList.remove('d-none');
				return;
			}

			if (tableContainer) tableContainer.classList.remove('d-none');
			if (emptyMsg) emptyMsg.classList.add('d-none');

			// Clear previous offer columns (keep first column)
			const rows = table.querySelectorAll('tr');
			rows.forEach(row => {
				const cells = row.querySelectorAll('th, td');
				for (let i = cells.length - 1; i >= 1; i--) {
					cells[i].remove();
				}
			});

			// Fetch offer details
			fetch(getAjaxUrl(`php/ajax.php?action=compare_offers&ids=${compareIds.join(',')}`))
				.then(res => res.json())
				.then(offers => {
					offers.forEach(offer => {
						// Header row
						const th = document.createElement('th');
						th.className = 'text-center';
						th.innerHTML = `<a href="${offer.url}" class="text-decoration-none fw-bold text-dark text-truncate d-block" style="max-width:200px">${offer.name}</a>`;
						rows[0].appendChild(th);

						// Image row
						const tdImg = document.createElement('td');
						tdImg.className = 'text-center';
						tdImg.innerHTML = `<img src="${offer.thumb_url}" class="rounded shadow-sm" style="width:100px; height:80px; object-fit:cover">`;
						rows[1].appendChild(tdImg);

						// Title link row
						const tdTitle = document.createElement('td');
						tdTitle.className = 'text-center';
						tdTitle.innerHTML = `<a href="${offer.url}" class="btn btn-sm btn-outline-primary rounded-pill">Zobacz ofertę</a>`;
						rows[2].appendChild(tdTitle);

						// Price row
						const tdPrice = document.createElement('td');
						tdPrice.className = 'text-center fw-bold text-primary';
						tdPrice.textContent = offer.formatted_price;
						rows[3].appendChild(tdPrice);

						// Location row
						const tdLoc = document.createElement('td');
						tdLoc.className = 'text-center text-muted small';
						tdLoc.textContent = offer.state_name || '—';
						rows[4].appendChild(tdLoc);

						// Rating row
						const tdRating = document.createElement('td');
						tdRating.className = 'text-center';
						
						let ratingHtml = '';
						if (offer.user_rating_count > 0) {
							const avg = parseFloat(offer.user_rating_avg).toFixed(1);
							ratingHtml += `<div class="text-warning small mb-1">`;
							for (let i = 1; i <= 5; i++) {
								if (avg >= i) {
									ratingHtml += `<i class="bi bi-star-fill"></i>`;
								} else if (avg >= i - 0.5) {
									ratingHtml += `<i class="bi bi-star-half"></i>`;
								} else {
									ratingHtml += `<i class="bi bi-star"></i>`;
								}
							}
							ratingHtml += `</div>`;
							ratingHtml += `<div class="small text-muted">${avg} / 5 (${offer.user_rating_count})</div>`;
							
							const positive = parseInt(offer.user_rating_positive_count || 0, 10);
							const count = parseInt(offer.user_rating_count, 10);
							const rec = Math.round((positive / count) * 100);
							ratingHtml += `<div class="text-success small fw-semibold mt-1"><i class="bi bi-hand-thumbs-up-fill me-1"></i>${rec}% poleceń</div>`;
							
							if (parseFloat(offer.user_rating_avg) >= 4.7 && count >= 3) {
								ratingHtml += `<div class="mt-1"><span class="badge bg-warning text-black border border-warning" style="font-size:0.7rem;"><i class="bi bi-trophy-fill me-1 text-danger"></i>Super Wykonawca</span></div>`;
							}
						} else {
							ratingHtml = `<span class="text-muted small"><i class="bi bi-star me-1"></i>Brak opinii</span>`;
						}
						tdRating.innerHTML = ratingHtml;
						rows[5].appendChild(tdRating);

						// Remove row
						const tdRem = document.createElement('td');
						tdRem.className = 'text-center';
						tdRem.innerHTML = `<button type="button" class="btn btn-sm btn-link text-danger compare-remove-col" data-id="${offer.id}"><i class="bi bi-trash"></i></button>`;
						rows[6].appendChild(tdRem);
					});

					// Attach remove col handlers
					table.querySelectorAll('.compare-remove-col').forEach(btn => {
						btn.addEventListener('click', function() {
							const id = parseInt(this.getAttribute('data-id'), 10);
							toggleCompareId(id);
							// Refresh modal table
							bootstrap.Modal.getInstance(compareModalEl).hide();
							setTimeout(() => {
								bootstrap.Modal.getInstance(compareModalEl).show();
							}, 300);
						});
					});
				})
				.catch(err => {
					console.error('Error loading compare offers:', err);
				});
		});
	}

	// Clear comparison button
	const clearCompareBtn = document.getElementById('compare-clear-btn');
	if (clearCompareBtn) {
		clearCompareBtn.addEventListener('click', () => {
			compareIds = [];
			localStorage.setItem('compare_offers_ids', JSON.stringify(compareIds));
			updateCompareWidget();
			if (compareModalEl) {
				bootstrap.Modal.getInstance(compareModalEl).hide();
			}
		});
	}

	// =========================================================================
	// 16. Guest Wishlist / Clipboard offline & sync
	// =========================================================================
	function getObservedOffers() {
		let list = [];
		try {
			const stored = localStorage.getItem('observed_offers');
			if (stored) {
				list = JSON.parse(stored);
				if (!Array.isArray(list)) {
					if (typeof list === 'number' || typeof list === 'string') {
						list = [parseInt(list, 10)];
					} else {
						list = [];
					}
				}
			}
		} catch (e) {
			const stored = localStorage.getItem('observed_offers');
			if (stored) {
				list = stored.split(',').map(x => parseInt(x, 10)).filter(x => !isNaN(x));
			}
		}
		return list.map(x => parseInt(x, 10)).filter(x => !isNaN(x));
	}

	let observedOffers = getObservedOffers();

	// Check if user is logged in and needs to sync offline clipboard
	if (window.isLoggedIn && observedOffers.length > 0) {
		const syncData = new URLSearchParams();
		syncData.append('action', 'sync_clipboard');
		syncData.append('ids', observedOffers.join(','));

		fetch(getAjaxUrl('php/ajax.php'), {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: syncData.toString()
		})
		.then(res => res.json())
		.then(data => {
			if (data.status) {
				localStorage.removeItem('observed_offers');
			}
		})
		.catch(err => console.error('Error syncing clipboard:', err));
	}

	// Unified Wishlist / Clipboard click handler (handles both guests and logged-in users, lists and detail pages)
	document.addEventListener('click', e => {
		const btn = e.target.closest('.wishlist-btn, .clipboard-btn-single');
		if (!btn) return;
		
		e.preventDefault();
		e.stopPropagation();
		const id = parseInt(btn.getAttribute('data-id'), 10);
		if (!id) return;

		if (!window.isLoggedIn) {
			// Guest user logic
			observedOffers = getObservedOffers();
			let added = false;
			if (observedOffers.includes(id)) {
				observedOffers = observedOffers.filter(x => x !== id);
			} else {
				observedOffers.push(id);
				added = true;
			}
			localStorage.setItem('observed_offers', JSON.stringify(observedOffers));
			updateWishlistButtonsUI(id, added);
		} else {
			// Logged-in user logic: send AJAX request
			const data = new URLSearchParams();
			data.append('action', 'clipboard_toggle');
			data.append('id', id);

			fetch(getAjaxUrl('php/ajax.php'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: data.toString()
			})
			.then(res => res.json())
			.then(res => {
				if (res.status) {
					updateWishlistButtonsUI(id, res.added);
				} else {
					console.error('Error toggling wishlist:', res.info);
				}
			})
			.catch(err => console.error('AJAX error toggling wishlist:', err));
		}
	});

	function updateWishlistButtonsUI(id, isAdded) {
		// Update all elements with data-id matching the toggled ID
		document.querySelectorAll(`.wishlist-btn[data-id="${id}"], .clipboard-btn-single[data-id="${id}"]`).forEach(el => {
			if (el.classList.contains('wishlist-btn')) {
				const icon = el.querySelector('i');
				if (icon) {
					if (isAdded) {
						icon.classList.remove('bi-heart');
						icon.classList.add('bi-heart-fill');
					} else {
						icon.classList.remove('bi-heart-fill');
						icon.classList.add('bi-heart');
					}
				}
			} else if (el.classList.contains('clipboard-btn-single')) {
				if (isAdded) {
					el.classList.remove('btn-outline-primary');
					el.classList.add('btn-warning');
					el.innerHTML = `<i class="bi bi-heart-fill me-1"></i>Obserwowane`;
				} else {
					el.classList.remove('btn-warning');
					el.classList.add('btn-outline-primary');
					el.innerHTML = `<i class="bi bi-heart me-1"></i>Dodaj do schowka`;
				}
			}
		});
	}

	// Initial wishlist UI sync for guests on page load
	if (!window.isLoggedIn) {
		const items = getObservedOffers();
		items.forEach(id => {
			updateWishlistButtonsUI(id, true);
		});
	}

	// Load Guest Wishlist (Clipboard Page)
	const guestClipboardList = document.getElementById('clipboard-guest-list');
	if (guestClipboardList && !window.isLoggedIn) {
		const emptyMsg = document.getElementById('clipboard-guest-empty');
		const loadingMsg = document.getElementById('clipboard-guest-loading');

		if (observedOffers.length === 0) {
			if (loadingMsg) loadingMsg.classList.add('d-none');
			if (emptyMsg) emptyMsg.classList.remove('d-none');
		} else {
			fetch(getAjaxUrl(`php/ajax.php?action=load_clipboard_offers&ids=${observedOffers.join(',')}`))
				.then(res => res.json())
				.then(offers => {
					if (loadingMsg) loadingMsg.classList.add('d-none');
					if (offers.length === 0) {
						if (emptyMsg) emptyMsg.classList.remove('d-none');
						return;
					}

					let html = '';
					offers.forEach(offer => {
						html += `
						<div class="col-12" id="guest-clipboard-item-${offer.id}">
							<div class="card offer-list-card border-0 shadow-sm">
								<div class="row g-0 h-100">
									<div class="col-md-3 col-sm-4 position-relative offer-list-img-wrapper">
										<a href="${offer.url}" class="d-block h-100">
											<img src="${offer.thumb_url}" class="img-fluid h-100 w-100 object-fit-cover" loading="lazy" style="min-height:150px">
										</a>
									</div>
									<div class="col-md-9 col-sm-8 d-flex flex-column justify-content-between p-3 p-md-4">
										<div>
											<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start mb-2 gap-2">
												<h4 class="h5 card-title mb-0">
													<a href="${offer.url}" class="text-dark text-decoration-none fw-bold">${offer.name}</a>
												</h4>
												<div>
													<span class="text-primary fw-bold fs-5">${offer.formatted_price}</span>
												</div>
											</div>
											<p class="text-muted small mb-3 d-none d-md-block">${offer.description ? offer.description.replace(/<[^>]*>/g, '').substring(0, 180) + '...' : ''}</p>
										</div>
										<div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
											<button type="button" class="btn btn-sm btn-outline-danger guest-clipboard-remove" data-id="${offer.id}">
												<i class="bi bi-trash me-1"></i>Usuń ze schowka
											</button>
											<div class="small text-muted">
												<i class="bi bi-geo-alt me-1"></i>${offer.state_name || ''}
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>`;
					});
					guestClipboardList.innerHTML = html;

					// Attach remove event handlers for guest wishlist items
					guestClipboardList.querySelectorAll('.guest-clipboard-remove').forEach(btn => {
						btn.addEventListener('click', function() {
							const id = parseInt(this.getAttribute('data-id'), 10);
							observedOffers = observedOffers.filter(x => x !== id);
							localStorage.setItem('observed_offers', JSON.stringify(observedOffers));
							
							const itemEl = document.getElementById(`guest-clipboard-item-${id}`);
							if (itemEl) itemEl.remove();

							if (observedOffers.length === 0) {
								if (emptyMsg) emptyMsg.classList.remove('d-none');
							}
						});
					});
				})
				.catch(err => {
					console.error('Error loading guest wishlist:', err);
					if (loadingMsg) loadingMsg.classList.add('d-none');
					if (emptyMsg) emptyMsg.classList.remove('d-none');
				});
		}
	}

	// =========================================================================
	// 17. Alert Notifications Subscription Widget
	// =========================================================================
	const subscribeForm = document.getElementById('subscribe-alert-form');
	if (subscribeForm) {
		subscribeForm.addEventListener('submit', function(e) {
			e.preventDefault();
			const email = document.getElementById('subscribe-email').value.trim();
			const categoryId = document.getElementById('subscribe-category-id').value;
			const messageDiv = document.getElementById('subscribe-alert-message');

			if (!email || !categoryId) {
				if (messageDiv) {
					messageDiv.className = 'mt-3 text-center small text-danger alert alert-danger p-2';
					messageDiv.textContent = 'Proszę podać e-mail i wybrać kategorię.';
					messageDiv.classList.remove('d-none');
				}
				return;
			}

			const formData = new URLSearchParams();
			formData.append('action', 'subscribe_alert');
			formData.append('email', email);
			formData.append('category_id', categoryId);

			fetch(getAjaxUrl('php/ajax.php'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: formData.toString()
			})
			.then(res => res.json())
			.then(data => {
				if (messageDiv) {
					if (data.status) {
						messageDiv.className = 'mt-3 text-center small text-success alert alert-success p-2';
						// Reset select but keep email
						document.getElementById('subscribe-category-id').selectedIndex = 0;
					} else {
						messageDiv.className = 'mt-3 text-center small text-danger alert alert-danger p-2';
					}
					messageDiv.textContent = data.info;
					messageDiv.classList.remove('d-none');
				}
			})
			.catch(err => {
				console.error('Error subscribing to alert:', err);
				if (messageDiv) {
					messageDiv.className = 'mt-3 text-center small text-danger alert alert-danger p-2';
					messageDiv.textContent = 'Wystąpił błąd. Spróbuj ponownie później.';
					messageDiv.classList.remove('d-none');
				}
			});
		});
	}

	// =========================================================================
	// 18. Abuse Reporting System
	// =========================================================================
	const reportBtn = document.getElementById('reportOfferBtn');
	const reportModalEl = document.getElementById('reportOfferModal');
	const reportForm = document.getElementById('report-abuse-form');

	if (reportBtn && reportModalEl) {
		reportBtn.addEventListener('click', function() {
			if (typeof bootstrap !== 'undefined') {
				const modal = bootstrap.Modal.getOrCreateInstance(reportModalEl);
				modal.show();
			}
		});
	}

	if (reportForm) {
		reportForm.addEventListener('submit', function(e) {
			e.preventDefault();
			const submitBtn = document.getElementById('submitReportBtn');
			const originalHtml = submitBtn ? submitBtn.innerHTML : '';

			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Wysyłanie...`;
			}

			const emailEl = document.getElementById('report-email');
			const reasonEl = document.getElementById('report-reason');
			const descEl = document.getElementById('report-description');
			const offerIdEl = reportForm.querySelector('input[name="offer_id"]');

			const email = emailEl ? emailEl.value.trim() : '';
			const reason = reasonEl ? reasonEl.value : '';
			const description = descEl ? descEl.value.trim() : '';
			const offerId = offerIdEl ? offerIdEl.value : '';

			const formData = new URLSearchParams();
			formData.append('action', 'report_offer');
			formData.append('offer_id', offerId);
			formData.append('reason', reason);
			formData.append('description', description);
			formData.append('email', email);

			fetch(getAjaxUrl('php/ajax.php'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: formData.toString()
			})
			.then(res => {
				return res.text().then(text => {
					try {
						return JSON.parse(text);
					} catch(e) {
						console.error('Raw response text:', text);
						throw new Error('Invalid JSON: ' + text.substring(0, 100));
					}
				});
			})
			.then(data => {
				if (data.status) {
					if (typeof window.showToast === 'function') {
						window.showToast(data.info);
					} else {
						alert(data.info);
					}
					if (typeof bootstrap !== 'undefined') {
						const modal = bootstrap.Modal.getInstance(reportModalEl);
						if (modal) modal.hide();
					}
					reportForm.reset();
				} else {
					alert(data.info);
				}
			})
			.catch(err => {
				console.error('Error submitting abuse report:', err);
				alert('Wystąpił błąd podczas wysyłania zgłoszenia: ' + err.message);
			})
			.finally(() => {
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.innerHTML = originalHtml;
				}
			});
		});
	}

});

// 13. Global modal control functions
function closeRodoWindow(){
	localStorage.rodo_accepted = true;
	const rodoMessage = document.getElementById('rodo-message');
	if (rodoMessage && typeof bootstrap !== 'undefined') {
		bootstrap.Modal.getOrCreateInstance(rodoMessage).hide();
	}
}

if (window.location.href.indexOf('#_=_') > 0) {
	window.location.replace(window.location.href.replace(/#.*/, ''));
}

function initGoogleMap() {
	if (typeof displayMap === 'function') {
		displayMap();
	} else {
		const input = document.getElementById('search_main_address');
		if (input && typeof google !== 'undefined') {
			new google.maps.places.Autocomplete(input, {types: ['geocode']});
		}
	}
}

function checkCookies(){
	if (!localStorage.cookies_accepted) {
		const cookies_message = document.getElementById("cookies-message");
		if (cookies_message) {
			cookies_message.style.display = "block";
		}
	}
}

function closeCookiesWindow(){
	localStorage.cookies_accepted = true;
	const cookie_window = document.getElementById("cookies-message");
	if (cookie_window) {
		cookie_window.parentNode.removeChild(cookie_window);
	}
}

// 14. Page Load Actions (Cookies alert & Smooth scrolls)
window.addEventListener('load', function() {
	checkCookies();
	const jsScrollPage = document.getElementById('js_scroll_page');
	if (jsScrollPage) {
		const rect = jsScrollPage.getBoundingClientRect();
		const scrollTop = window.scrollY || document.documentElement.scrollTop;
		const position = rect.top + scrollTop;
		if (scrollTop + window.innerHeight < position) {
			window.scrollTo({
				top: position - 110,
				behavior: 'smooth'
			});
		}
	}
});
