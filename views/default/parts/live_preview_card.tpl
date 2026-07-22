<div class="offer-preview-card border rounded-4 overflow-hidden shadow-sm bg-white text-start">
	<!-- Photo box -->
	<div class="position-relative bg-light d-flex align-items-center justify-content-center" style="aspect-ratio: 4/3; max-height: 250px;">
		<img src="views/{{ settings.template }}/images/no_image.png" class="preview_live_img w-100 h-100 object-fit-cover d-none" alt="Podgląd zdjęcia">
		<div class="preview_live_no_img text-muted d-flex flex-column align-items-center">
			<i class="bi bi-image" style="font-size: 3rem;"></i>
			<span class="small mt-1">Brak zdjęć</span>
		</div>
		<span class="preview_live_type badge bg-primary position-absolute top-0 start-0 m-3">Typ ogłoszenia</span>
	</div>
	
	<!-- Content box -->
	<div class="p-3">
		<div class="d-flex justify-content-between align-items-start mb-2 gap-2">
			<h5 class="preview_live_title fw-bold mb-0 text-dark text-truncate" style="font-size: 1.15rem;">(Wpisz tytuł ogłoszenia)</h5>
			<span class="preview_live_price fw-bold text-primary fs-5 text-nowrap">Cena na zapytanie</span>
		</div>
		
		<div class="d-flex flex-wrap gap-2 mb-3">
			<span class="preview_live_category badge bg-light text-secondary border small"><i class="bi bi-tag me-1"></i>(Kategoria)</span>
			<span class="preview_live_location badge bg-light text-secondary border small"><i class="bi bi-geo-alt me-1"></i>(Lokalizacja)</span>
		</div>
		
		<p class="preview_live_description text-muted small mb-3 text-break" style="display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;min-height:60px;">
			(Wpisz opis ogłoszenia)
		</p>
		
		<!-- Contact box -->
		<div class="p-3 bg-light rounded-3 small">
			<div class="mb-2 d-flex align-items-center gap-2">
				<i class="bi bi-telephone-fill text-success"></i>
				<span class="preview_live_phone">(Brak telefonu)</span>
			</div>
			<div class="d-flex align-items-center gap-2">
				<i class="bi bi-envelope-fill text-info"></i>
				<span class="preview_live_email text-truncate" style="max-width: 100%;">{{ user.email }}</span>
			</div>
		</div>
	</div>
</div>
