{# views/default/search_main.tpl #}
<style>
	.search-type-btn {
		border: 1px solid #e5e7eb !important;
		background-color: #f9fafb !important;
		color: #4b5563 !important;
		transition: all 0.2s ease-in-out;
	}
	.search-type-btn:hover {
		background-color: #f3f4f6 !important;
		color: #1f2937 !important;
	}
	.search-type-btn.active {
		background: linear-gradient(135deg, #f97316 0%, #facc15 100%) !important;
		color: #ffffff !important;
		border-color: transparent !important;
		box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25) !important;
	}
</style>

<div class="d-flex flex-wrap justify-content-center gap-2 mb-4 px-2 search-type-toggle">
	<button type="button" class="btn btn-sm search-type-btn px-4 py-2.5 rounded-pill fw-bold {% if not get.type %}active{% endif %}" onclick="setSearchType('')">
		<i class="bi bi-grid-fill me-1"></i> Wszystkie ogłoszenia
	</button>
	<button type="button" class="btn btn-sm search-type-btn px-4 py-2.5 rounded-pill fw-bold {% if get.type == 'kupie' %}active{% endif %}" onclick="setSearchType('kupie')">
		<i class="bi bi-briefcase-fill me-1"></i> Szukam podwykonawców (Zlecenia)
	</button>
	<button type="button" class="btn btn-sm search-type-btn px-4 py-2.5 rounded-pill fw-bold {% if get.type == 'uslugi' %}active{% endif %}" onclick="setSearchType('uslugi')">
		<i class="bi bi-tools me-1"></i> Szukam zleceń (Oferty usług)
	</button>
</div>

<script>
function setSearchType(typeVal) {
	const hiddenInput = document.getElementById('search_type_hidden');
	if (hiddenInput) {
		hiddenInput.value = typeVal;
	}
	
	// Update active class on buttons
	const toggleDiv = document.querySelector('.search-type-toggle');
	if (toggleDiv) {
		const buttons = toggleDiv.querySelectorAll('.search-type-btn');
		buttons.forEach(btn => {
			if (btn.getAttribute('onclick').includes("'" + typeVal + "'")) {
				btn.classList.add('active');
			} else {
				btn.classList.remove('active');
			}
		});
	}
}
</script>

<div class="search-bar-modern shadow border rounded-4 bg-white p-2">
	<form action="{{ path('offers') }}" method="get" class="mb-0">
		<input type="hidden" name="search">
		<input type="hidden" name="type" id="search_type_hidden" value="{{ get.type }}">
		<div class="row g-2 align-items-center">
			
			<!-- Keywords -->
			<div class="col-12 col-md-5 border-end-md position-relative">
				<div class="input-group border-0">
					<span class="input-group-text bg-white border-0 text-muted fs-5"><i class="bi bi-search"></i></span>
					<input class="form-control border-0 ps-1 fs-5 py-2" type="text" name="keywords" id="search_keywords" placeholder="{{ 'Enter your keywords...'|lang }}" title="{{ 'Enter your keywords...'|lang }}" value="{{ get.keywords }}" autocomplete="off">
				</div>
				<div id="search_keywords_suggestions" class="search-suggestions-dropdown"></div>
			</div>
			
			<!-- Location Selector -->
			<div class="col-12 col-md-4 mt-2 mt-md-0">
				<div class="position-relative w-100" id="location_selector_container">
					<div class="input-group border-0">
						<span class="input-group-text bg-white border-0 text-muted fs-5"><i class="bi bi-geo-alt"></i></span>
						<input type="text" class="form-control border-0 ps-1 fs-5 py-2" id="location_input_display" placeholder="{{ 'Whole Poland'|lang }}" readonly style="cursor: pointer; background-color: #fff !important;" value="{% if get.address %}{{ get.address }}{% elseif get.state2 %}{{ get.state2 }}{% elseif get.state %}{{ get.state }}{% endif %}">
						<span class="input-group-text bg-white border-0 text-muted fs-5 cursor-pointer px-2" id="location_clear_btn" style="{% if get.state or get.state2 or get.address %}display: flex;{% else %}display: none;{% endif %}"><i class="bi bi-x-lg"></i></span>
					</div>
					
					<input type="hidden" name="state" id="search_state_hidden" value="{{ get.state }}">
					<input type="hidden" name="state2" id="search_state2_hidden" value="{{ get.state2 }}">
					<input type="hidden" name="address" id="search_address_hidden" value="{{ get.address }}">
					
					<!-- Custom Dropdown Menu -->
					<div class="location-dropdown shadow-lg border rounded-4 bg-white" id="location_dropdown_menu" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; max-height: 320px; overflow-y: auto; margin-top: 8px;">
						<!-- Cała Polska Button -->
						<button type="button" class="btn-all-poland w-100 text-start p-3 border-bottom d-flex flex-column align-items-start" id="btn_all_poland" data-testid="all-regions" data-cy="all-regions" aria-label="Cała Polska - Wszystkie w całym kraju" role="option" aria-selected="false" tabindex="-1" data-nx-name="UnstyledButton">
							<span class="fw-bold fs-6 text-dark">Cała Polska</span>
							<span class="text-muted small">Wszystkie w całym kraju</span>
						</button>
						
						<!-- States list -->
						<div class="states-list-wrapper">
							{% if states %}
								{% for state in states %}
									<div class="state-item border-bottom">
										<div class="d-flex align-items-center justify-content-between p-3 state-header cursor-pointer" data-state-slug="{{ state.slug }}" data-state-name="{{ state.name }}">
											<span class="fw-semibold text-dark">{{ state.name }}</span>
											{% if state.states %}
												<button type="button" class="btn btn-sm toggle-substates-btn p-0" data-state-slug="{{ state.slug }}" title="Rozwiń miasta">
													<i class="bi bi-chevron-right fs-5"></i>
												</button>
											{% endif %}
										</div>
										
										<!-- Substates/Cities list -->
										{% if state.states %}
											<div class="substates-list bg-light" id="substates_list_{{ state.slug }}" style="display: none;">
												<div class="p-3 border-bottom cursor-pointer substate-item fw-bold text-primary" data-state-slug="{{ state.slug }}" data-state-name="{{ state.name }}" data-substate-slug="" data-substate-name="">
													Wszystkie w {{ state.name }}
												</div>
												{% for state2 in state.states %}
													<div class="p-3 border-bottom cursor-pointer substate-item ps-4 text-dark" data-state-slug="{{ state.slug }}" data-state-name="{{ state.name }}" data-substate-slug="{{ state2.slug }}" data-substate-name="{{ state2.name }}">
														{{ state2.name }}
													</div>
												{% endfor %}
											</div>
										{% endif %}
									</div>
								{% endfor %}
							{% else %}
								<div class="p-3 text-muted text-center small">Brak zdefiniowanych województw.</div>
							{% endif %}
						</div>
					</div>
				</div>
			</div>
			
			<!-- Search Button -->
			<div class="col-12 col-md-3 mt-2 mt-md-0">
				<button type="submit" class="btn btn-accent btn-lg w-100 py-3 text-uppercase fw-bold rounded-3 fs-6 d-flex align-items-center justify-content-center gap-2 shadow-sm"><i class="bi bi-search"></i> {{ 'Search'|lang }}</button>
			</div>
			
		</div>
	</form>
</div>
