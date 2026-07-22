$(function(){

	console.log("Inicjalizacja add.js...");

	let descriptionEditor;
	if (typeof ClassicEditor !== 'undefined' && $('#description').length) {
		ClassicEditor
			.create(document.querySelector('#description'), {
				language: 'pl'
			})
			.then(editor => {
				descriptionEditor = editor;
				descriptionEditor.model.document.on('change:data', () => {
					updateLivePreview();
				});
				checkAndShowDraftRestore();
				updateLivePreview();
			})
			.catch(error => {
				console.error("Błąd podczas ładowania CKEditor 5:", error);
				checkAndShowDraftRestore();
				updateLivePreview();
			});
	} else {
		console.warn("ClassicEditor nie został załadowany lub brak pola description.");
		checkAndShowDraftRestore();
		updateLivePreview();
	}

	function checkAndShowDraftRestore() {
		var draft = localStorage.getItem('offer_draft');
		if (draft) {
			try {
				var data = JSON.parse(draft);
				if (Date.now() - data.timestamp < 86400000) { // 24h
					// Append modal HTML to body
					var modalHtml = `
					<div class="modal fade" id="restore_draft_modal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
						<div class="modal-dialog modal-dialog-centered">
							<div class="modal-content text-start">
								<div class="modal-header border-0">
									<h5 class="modal-title fw-bold text-primary"><i class="bi bi-file-earmark-diff-fill me-2"></i>Przywracanie wersji roboczej</h5>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
								</div>
								<div class="modal-body py-3">
									<p class="mb-0">Wykryto niezapisane dane w formularzu dodawania ogłoszenia z dnia <strong>${new Date(data.timestamp).toLocaleString()}</strong>. Czy chcesz przywrócić te dane?</p>
								</div>
								<div class="modal-footer border-0 justify-content-center pb-4">
									<button type="button" class="btn btn-light" id="btn_discard_draft" data-bs-dismiss="modal">Odrzuć</button>
									<button type="button" class="btn btn-primary" id="btn_restore_draft"><i class="bi bi-arrow-counterclockwise me-2"></i>Przywróć dane</button>
								</div>
							</div>
						</div>
					</div>`;
					$('body').append(modalHtml);
					
					var restoreModal = new bootstrap.Modal(document.getElementById('restore_draft_modal'));
					restoreModal.show();
					
					$('#btn_restore_draft').click(function() {
						if (data.name) $('#name').val(data.name);
						if (data.email) $('#email').val(data.email);
						if (data.phone) $('#phone').val(data.phone);
						if (data.description) {
							if (descriptionEditor) {
								descriptionEditor.setData(data.description);
							} else {
								$('#description').val(data.description);
							}
						}
						restoreModal.hide();
						updateLivePreview();
					});
					
					$('#btn_discard_draft').click(function() {
						localStorage.removeItem('offer_draft');
						restoreModal.hide();
					});
				}
			} catch (e) {
				console.error("Błąd podczas parsowania wersji roboczej:", e);
			}
		}
	}

	// Auto-save draft every 30 seconds
	setInterval(function() {
		var descriptionVal = '';
		if (descriptionEditor) {
			descriptionVal = descriptionEditor.getData();
		} else {
			descriptionVal = $('#description').val() || '';
		}
		
		var nameVal = $('#name').val() || '';
		var emailVal = $('#email').val() || '';
		var phoneVal = $('#phone').val() || '';
		
		if (nameVal || descriptionVal || emailVal || phoneVal) {
			localStorage.setItem('offer_draft', JSON.stringify({
				name: nameVal,
				description: descriptionVal,
				email: emailVal,
				phone: phoneVal,
				timestamp: Date.now()
			}));
			console.log("Wersja robocza ogłoszenia została automatycznie zapisana.");
		}
	}, 30000);

	$("#preview_photos").sortable({
		containerSelector : "#preview_photos",
		itemSelector : ".img-thumbnail",
		handle: "img",
		placeholder	: "<div class='placeholder'></div>",
		onDrop: function ($item, container, _super) {
			_super($item, container);
			updateLivePreview();
		}
	});

	$("#form_add_offer").submit(function(){
		if (descriptionEditor) {
			$('#description').val(descriptionEditor.getData());
		} else {
			console.warn('CKEditor not loaded, using plain textarea');
		}
		localStorage.removeItem('offer_draft'); // Clear draft on successful submit
		$last = $("select[name=category_id]:enabled").last();
		if($last.val()==""){
			$last.attr("disabled", true);
		}
	});

	$("#input_select_photo").on("dragenter dragover", function(){
		$(this).closest(".upload-zone-wrapper").addClass("dragover");
	}).on("dragleave drop", function(){
		$(this).closest(".upload-zone-wrapper").removeClass("dragover");
	});

	$("#input_select_photo").change(function (){
		console.log("Wykryto zmianę w polu wyboru zdjęć.");
		var $this = $(this);
		var number_photos = $this[0].files.length;
		var photo_count = $("#preview_photos .img-thumbnail").length;
		var progress_bar_value = 0;
		
		console.log("Wybrano plików: " + number_photos + ", aktualnie zdjęć: " + photo_count + ", limit: " + photo_max);

		if(number_photos && (!photo_max || photo_max > photo_count)){
			$("#photos_progress").show();
			$("#preview_load").css("display", "inline-block");
			var $progress_bar = $("#photos_progress").find(".progress-bar");
			$("#photos_info").hide().html("");
			$("#box_add_offer input[type=submit]").prop("disabled", true);

			var uploadPromises = [];
			var flag = true;
			var added_count = 0;

			for (let i = 0; i < $this[0].files.length; i++) {
				const file = $this[0].files[i];
				if (!flag) break;
				
				// Photo limit check
				if (photo_max && (photo_count + added_count) >= photo_max) {
					console.warn("Przekroczono limit zdjęć podczas dodawania.");
					break;
				}

				// Size check
				if (typeof photo_max_size !== 'undefined' && photo_max_size > 0 && file.size > photo_max_size * 1024) {
					alert("Wybrany plik jest za duży! Maksymalny rozmiar to " + (photo_max_size / 1024).toFixed(1) + " MB.");
					flag = false;
					break;
				}

				const currentAddedIndex = added_count;
				uploadPromises.push((async function() {
					let fileToSend = file;
					if (file.type.match('image.*')) {
						try {
							console.log("Kompresowanie zdjęcia przed wysłaniem: " + file.name);
							fileToSend = await compressImage(file);
						} catch (e) {
							console.error("Błąd kompresji obrazu, wysyłanie oryginalnego:", e);
						}
					}
					
					var data_photo = new FormData();
					data_photo.append("action", "add_photo");
					data_photo.append("token", $("#form_add_offer [name=token]").val());
					data_photo.append("count_photo", photo_count + currentAddedIndex);
					
					// Rename extension to .jpg since canvas.toBlob output is image/jpeg
					const newName = file.name.replace(/\.[^.]+$/, '.jpg');
					data_photo.append("file", fileToSend, newName);
					
					console.log("Wysyłanie zdjęcia nr: " + i);
					
					return $.ajax({
						url: "/php/ajax.php",
						type: "POST",
						data: data_photo,
						dataType: "json",
						contentType: false,
						cache: false,
						processData: false,
						success: function(data){
							console.log("Otrzymano odpowiedź dla pojedynczego zdjęcia:", data);
							if(data){
								if(data.status){
									$("#preview_photos").append('<div class="img-thumbnail"><img src="upload/photos/'+data['thumb']+'" alt="'+data['url']+'"><a href="#" title="'+data['remove_title']+'" class="remove_photo remove_photo_btn"><i class="bi bi-x"></i></a><input type="hidden" name="photos[]" value="'+data['id']+'"></div>');
								}else{
									$("#photos_info").show().html(data.info);
								}
							}
							// Progress bar increment
							progress_bar_value += Math.round(100 / number_photos);
							$progress_bar.css("width", Math.min(progress_bar_value, 100) + "%")
								.attr("aria-valuenow", Math.min(progress_bar_value, 100))
								.text(Math.min(progress_bar_value, 100) + "%");
						}
					});
				})());
				
				added_count++;
			}

			if (uploadPromises.length > 0) {
				Promise.all(uploadPromises).then(function() {
					console.log("Wszystkie zapytania uploadu zostały zakończone.");
					$("#preview_load, #photos_progress").hide();
					$("#box_add_offer input[type=submit]").prop("disabled", false);
					$progress_bar.css("width", "0%").attr("aria-valuenow", "0").text("0%");
					updateLivePreview();
				}).catch(function(err) {
					console.error("Wystąpił błąd podczas jednego z wysyłań:", err);
					$("#preview_load, #photos_progress").hide();
					$("#box_add_offer input[type=submit]").prop("disabled", false);
					$progress_bar.css("width", "0%").attr("aria-valuenow", "0").text("0%");
					updateLivePreview();
				});
			} else {
				// No files uploaded (either invalid sizes or limit reached immediately)
				$("#preview_load, #photos_progress").hide();
				$("#box_add_offer input[type=submit]").prop("disabled", false);
			}

		} else {
			console.warn("Limit zdjęć przekroczony lub nie wybrano plików.");
			$("#photos_info").show().html(lang["Photo limit exceeded"]);
		}
		$this.val("");
	});

	$("#button_get_coordinates").click(function(){
		var address = $("input[name=address]").val();
		if($("[name=state_id]").length && $("[name=state_id]").val()!=""){address += " "+$("[name=state_id] option:selected").text();}
		if($("[name=state2_id]:enabled").length && $("[name=state2_id]:enabled").val()!=""){address += " "+$("[name=state2_id]:enabled option:selected").text();}
		if(address){
			const csrfToken = $('meta[name="csrf-token"]').attr('content') || $('#form_add_offer [name=token]').val();
			$.ajax({
				url: "php/ajax.php",
				type: "POST",
				data:{"action" : "getCoordinates","address" : address, "token": csrfToken},
				dataType :"json",
				success: function(data) {
					if(data.lat && data.long){
						var latlng = new google.maps.LatLng(data.lat, data.long);
						google_maps_marker.setPosition(latlng);
						google_maps.setCenter(latlng);
						$("input[name=address_lat]").val(data.lat);
						$("input[name=address_long]").val(data.long);
						if (typeof bounceMarker === 'function') {
							bounceMarker();
						}
					}
				}
			});
		}
        return false;
    });

	function updateLivePreview() {
		// 1. Title
		const name = $('#name').val() || '';
		$('.preview_live_title').text(name ? name : '(Wpisz tytuł ogłoszenia)');
		
		// 2. Type
		const typeVal = $('#type_id').val();
		if (typeVal) {
			const typeText = $('#type_id option:selected').text();
			$('.preview_live_type').text(typeText).removeClass('d-none');
		} else {
			$('.preview_live_type').addClass('d-none');
		}
		
		// 3. Price
		const currency = $('.input-group-text').first().text().trim() || 'PLN';
		const isFree = $('input[name="price_free"]').is(':checked');
		const priceVal = $('input[name="price"]').val();
		const isNegotiate = $('input[name="price_negotiate"]').is(':checked');
		
		if (isFree) {
			$('.preview_live_price').text('Za darmo');
		} else if (priceVal) {
			let priceText = parseFloat(priceVal).toFixed(2) + ' ' + currency;
			if (isNegotiate) {
				priceText += ' (do negocjacji)';
			}
			$('.preview_live_price').text(priceText);
		} else {
			$('.preview_live_price').text('Cena na zapytanie');
		}
		
		// 4. Category
		let categoryText = '';
		$('select[name="category_id"]').each(function() {
			const val = $(this).val();
			if (val) {
				categoryText = $(this).find('option:selected').text();
			}
		});
		$('.preview_live_category').html('<i class="bi bi-tag me-1"></i>' + (categoryText ? categoryText : '(Kategoria)'));
		
		// 5. Location
		let address = $('input[name="address"]').val() || '';
		let state = $('select[name="state_id"] option:selected').val() ? $('select[name="state_id"] option:selected').text() : '';
		let state2 = $('select[name="state2_id"]:enabled option:selected').val() ? $('select[name="state2_id"]:enabled option:selected').text() : '';
		
		let locationParts = [];
		if (address) locationParts.push(address);
		if (state2) locationParts.push(state2);
		else if (state) locationParts.push(state);
		
		const locationText = locationParts.join(', ');
		$('.preview_live_location').html('<i class="bi bi-geo-alt me-1"></i>' + (locationText ? locationText : '(Lokalizacja)'));
		
		// 6. Description
		let descHtml = descriptionEditor ? descriptionEditor.getData() : ($('#description').val() || '');
		let descText = descHtml.replace(/<[^>]*>/g, '').trim();
		$('.preview_live_description').html(descText ? descHtml : '(Wpisz opis ogłoszenia)');
		
		// 7. Phone
		const phone = $('#phone').val() || '';
		$('.preview_live_phone').text(phone ? phone : '(Brak telefonu)');
		
		// 8. Email
		const email = $('#email').val() || '';
		$('.preview_live_email').text(email);
		
		// 9. Photos
		const firstImgSrc = $('#preview_photos .img-thumbnail img').first().attr('src');
		if (firstImgSrc) {
			$('.preview_live_img').attr('src', firstImgSrc).removeClass('d-none');
			$('.preview_live_no_img').addClass('d-none');
		} else {
			$('.preview_live_img').addClass('d-none');
			$('.preview_live_no_img').removeClass('d-none');
		}
	}

	// Register event listeners
	$('#name, #phone, #email, #description, input[name="address"], input[name="price"]').on('keyup input change', updateLivePreview);
	$('#type_id, select[name="state_id"], select[name="state2_id"], input[name="price_free"], input[name="price_negotiate"]').on('change', updateLivePreview);
	$(document).on('change', 'select[name="category_id"]', updateLivePreview);
	
	// Initial update
	updateLivePreview();
	
	function compressImage(file, maxWidth = 1920, quality = 0.85) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onload = function(e) {
				const img = new Image();
				img.onload = function() {
					const canvas = document.createElement('canvas');
					const scale = Math.min(1, maxWidth / img.width);
					canvas.width = img.width * scale;
					canvas.height = img.height * scale;
					const ctx = canvas.getContext('2d');
					
					ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
					canvas.toBlob(function(blob) {
						if (blob) {
							resolve(blob);
						} else {
							reject(new Error("Błąd tworzenia blob z canvas"));
						}
					}, 'image/jpeg', quality);
				};
				img.onerror = function(err) {
					reject(err);
				};
				img.src = e.target.result;
			};
			reader.onerror = function(err) {
				reject(err);
			};
			reader.readAsDataURL(file);
		});
	}

	let loadedCategories = [];
	let showPrice = true;
	let requiredPrice = false;
	let activeAjaxRequest = null;

	// Title character counter
	$('#name').on('input change', function() {
		const len = $(this).val().length;
		$('#title_char_count').text(len);
	});

	// Helper to get current entered options values from the DOM
	function getCurrentOptionsValues() {
		const values = {};
		$('#options_container [name^="options["]').each(function() {
			const $el = $(this);
			const name = $el.attr('name');
			const match = name.match(/options\[(\d+)\]/);
			if (match) {
				const id = match[1];
				if ($el.is(':checkbox')) {
					if ($el.is(':checked')) {
						if (!values[id]) {
							values[id] = [];
						}
						values[id].push($el.val());
					}
				} else {
					values[id] = $el.val();
				}
			}
		});
		return values;
	}

	// Render option fields dynamically
	function renderOptions(options) {
		const $container = $('#options_container').empty();
		if (!options || $.isEmptyObject(options)) {
			return;
		}

		const selectPlaceholder = $('#state_id option[value=""]').first().text() || '-- select --';

		$.each(options, function(id, item) {
			const requiredAttr = (item.required === "1" || item.required === 1 || item.required === true) ? 'required' : '';
			const requiredStar = requiredAttr ? '<span class="text-danger">&nbsp;*</span>' : '';
			const $row = $('<div>', { class: 'form-group row mb-3 option-row' });
			const $label = $('<label>', {
				for: 'options_' + item.id,
				class: 'col-sm-3 col-form-label',
				html: item.name + ':' + requiredStar
			});
			const $col = $('<div>', { class: 'col-sm-9' });
			let $input = null;

			if (item.kind === 'text') {
				$input = $('<input>', {
					type: 'text',
					class: 'form-control',
					id: 'options_' + item.id,
					name: 'options[' + item.id + ']',
					value: item.value || ''
				});
				if (requiredAttr) $input.attr('required', 'required');
				$col.append($input);

			} else if (item.kind === 'number') {
				$input = $('<input>', {
					type: 'number',
					class: 'form-control',
					id: 'options_' + item.id,
					name: 'options[' + item.id + ']',
					value: (item.value !== undefined && item.value !== '') ? item.value : ''
				});
				if (requiredAttr) $input.attr('required', 'required');
				const $innerWrapper = $('<div>', { class: 'col-lg-4 col-md-6' });
				$innerWrapper.append($input);
				$col.append($innerWrapper);

			} else if (item.kind === 'select') {
				$input = $('<select>', {
					class: 'form-control',
					id: 'options_' + item.id,
					name: 'options[' + item.id + ']'
				});
				if (requiredAttr) $input.attr('required', 'required');
				$input.append($('<option>', { value: '', text: selectPlaceholder }));

				if (item.choices) {
					const choices = Array.isArray(item.choices) ? item.choices : Object.values(item.choices);
					choices.forEach(function(choice) {
						const $opt = $('<option>', { value: choice, text: choice });
						if (String(choice) === String(item.value)) {
							$opt.attr('selected', 'selected');
						}
						$input.append($opt);
					});
				}
				$col.append($input);

			} else if (item.kind === 'checkbox') {
				const $checkboxRow = $('<div>', { class: 'row' });
				if (item.choices) {
					const choices = Array.isArray(item.choices) ? item.choices : Object.values(item.choices);
					const selectedChoices = Array.isArray(item.value) ? item.value : (item.value ? [item.value] : []);
					choices.forEach(function(choice) {
						const $colCheckbox = $('<div>', { class: 'col-sm-6 col-md-4' });
						const $labelCheckbox = $('<label>', { class: 'checkbox-inline' });
						const $chk = $('<input>', {
							type: 'checkbox',
							name: 'options[' + item.id + '][]',
							value: choice
						});
						if (selectedChoices.indexOf(choice) > -1) {
							$chk.attr('checked', 'checked');
						}
						$chk.on('change', function() {
							updateLivePreview();
						});
						$labelCheckbox.append($chk).append(' ' + choice);
						$colCheckbox.append($labelCheckbox);
						$checkboxRow.append($colCheckbox);
					});
				}
				$col.append($checkboxRow);
			}

			if ($input) {
				$input.on('input change', function() {
					updateLivePreview();
				});
			}

			$row.append($label).append($col);
			$container.append($row);
		});
	}

	// Render select inputs for category levels
	function renderCategories() {
		const $loader = $('#icon_load_categories').detach();
		const $container = $('#categories_container').empty();
		const selectPlaceholder = $('#state_id option[value=""]').first().text() || '-- select --';

		loadedCategories.forEach(function(level) {
			const selectId = 'select_category_' + level.category_id;
			const requiredAttr = (level.required === "1" || level.required === 1 || level.required === true) ? 'required' : '';
			
			const $wrapper = $('<div>', {
				class: 'col-12 category-select-wrapper mb-2',
				id: selectId,
				'data-category_id': level.category_id
			});
			
			const $select = $('<select>', {
				class: 'form-control category-select',
				name: 'category_id',
				title: 'Select the appropriate category'
			});
			
			if (requiredAttr) {
				$select.attr('required', 'required');
			}
			
			$select.append($('<option>', { value: '', text: selectPlaceholder }));
			
			level.categories.forEach(function(item) {
				const $opt = $('<option>', { value: item.id, text: item.name });
				if (String(item.id) === String(level.selectCategory)) {
					$opt.attr('selected', 'selected');
				}
				$select.append($opt);
			});
			
			$select.on('change', function() {
				const val = $(this).val();
				loadCategories(level.category_id, val);
			});
			
			$wrapper.append($select);
			$container.append($wrapper);
		});

		$container.append($loader);
	}

	// Load category levels asynchronously
	function loadCategories(select_category_id = 0, category_id = 0, load_options = 1) {
		$('#icon_load_categories').removeClass('d-none');
		$('#btn_save_offer').prop('disabled', true);

		for (let i = 0; i < loadedCategories.length; i++) {
			if (loadedCategories[i].category_id == select_category_id) {
				loadedCategories[i].selectCategory = category_id;
				loadedCategories.splice(i + 1);
				break;
			}
		}

		if ((!category_id || category_id === "") && loadedCategories.length > 0) {
			renderCategories();
			if (load_options === 1 || load_options === "1") {
				renderOptions({});
				showPrice = false;
				requiredPrice = false;
				updatePriceFieldsState();
			}
			$('#icon_load_categories').addClass('d-none');
			$('#btn_save_offer').prop('disabled', false);
			updateLivePreview();
			return;
		}

		if (activeAjaxRequest) {
			activeAjaxRequest.abort();
		}

		const csrfToken = $('meta[name="csrf-token"]').attr('content') || $('#form_add_offer [name=token]').val();
		activeAjaxRequest = $.ajax({
			url: 'php/ajax.php',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'get_categories_and_options',
				category: category_id,
				load_options: load_options,
				token: csrfToken
			},
			success: function(response) {
				if (load_options === 1 || load_options === "1") {
					const prevValues = getCurrentOptionsValues();
					const newOptions = response.options || {};
					
					Object.keys(newOptions).forEach(function(id) {
						const opt = newOptions[id];
						if (window.list_options && window.list_options[id] !== undefined) {
							const dbVal = window.list_options[id];
							if (opt.kind === 'number') {
								opt.value = parseInt(dbVal[0], 10);
							} else if (opt.kind === 'checkbox') {
								opt.value = dbVal;
							} else {
								opt.value = dbVal[0];
							}
						} else if (prevValues[id] !== undefined) {
							opt.value = prevValues[id];
						} else {
							opt.value = (opt.kind === 'checkbox') ? [] : '';
						}
					});
					
					renderOptions(newOptions);
				}

				if (response.categories && !$.isEmptyObject(response.categories) && (parseInt(category_id) > 0 || loadedCategories.length === 0)) {
					let selectCategory = '';
					if (window.list_categories && window.list_categories.length > 0) {
						selectCategory = String(window.list_categories.shift());
					}
					
					const required = (parseInt(category_id) > 0) ? required_subcategory : required_category;
					
					loadedCategories.push({
						category_id: category_id,
						categories: response.categories,
						selectCategory: selectCategory,
						required: required
					});
					
					renderCategories();
					
					if (selectCategory) {
						const next_load_options = (window.list_categories && window.list_categories.length > 0) ? 0 : 1;
						loadCategories(category_id, selectCategory, next_load_options);
					}
				} else {
					renderCategories();
				}

				if (response.price && !$.isEmptyObject(response.price)) {
					showPrice = response.price.show;
					requiredPrice = response.price.required;
				} else if (load_options == 1) {
					showPrice = false;
					requiredPrice = false;
				}
				
				updatePriceFieldsState();
			},
			error: function(xhr, status, error) {
				if (status !== 'abort') {
					console.error('Błąd podczas ładowania kategorii:', error);
				}
			},
			complete: function() {
				$('#icon_load_categories').addClass('d-none');
				$('#btn_save_offer').prop('disabled', false);
				updateLivePreview();
			}
		});
	}

	// Toggle price fields visibility and disabled status
	function updatePriceFieldsState() {
		const isFree = $('#price_free').is(':checked');
		const priceVal = $('#price').val();
		
		if (!showPrice) {
			$('#price_group').hide();
			$('#price, #price_negotiate, #price_free').prop('disabled', true);
		} else {
			$('#price_group').show();
			$('#price_free').prop('disabled', false);
			
			if (isFree) {
				$('#price_input_wrapper').hide();
				$('#price').prop('disabled', true).val('').removeAttr('required');
				$('#price_negotiate').prop('disabled', true).prop('checked', false);
				$('#price_negotiate_wrapper').hide();
				$('#price_required_star').hide();
			} else {
				$('#price_input_wrapper').show();
				$('#price').prop('disabled', false);
				
				if (requiredPrice) {
					$('#price_required_star').show();
					$('#price').attr('required', 'required');
				} else {
					$('#price_required_star').hide();
					$('#price').removeAttr('required');
				}
				
				if (priceVal && priceVal !== '') {
					$('#price_negotiate_wrapper').show();
					$('#price_negotiate').prop('disabled', false);
				} else {
					$('#price_negotiate_wrapper').hide();
					$('#price_negotiate').prop('disabled', true).prop('checked', false);
				}
			}
		}
		
		updateLivePreview();
	}

	// Price input event handlers
	$('#price').on('input change', updatePriceFieldsState);
	$('#price_free').on('change', updatePriceFieldsState);

	// Initial category load
	const initial_load_options = (window.list_categories && window.list_categories.length > 0) ? 0 : 1;
	loadCategories(0, 0, initial_load_options);

	// Photo removal handler
	$(document).on("click", ".remove_photo", function(){
		$(this).parents(".img-thumbnail").remove();
		$("#photos_info").html("");
		updateLivePreview();
		return false;
	});

});
