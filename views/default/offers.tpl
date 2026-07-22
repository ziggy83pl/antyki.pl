{% set category_icons = {
    'militaria-do-1945': 'bi-shield-shaded',
    'militaria-wspolczesne': 'bi-shield-fill',
    'numizmatyka-i-falerystyka': 'bi-coin',
    'starodruki-i-dokumenty': 'bi-book-half',
    'znaleziska-wykrywacz': 'bi-compass',
    'sztuka-i-rekodzielo': 'bi-palette icon-orange',
    'design-i-antyki': 'bi-house-heart icon-orange',
    'kolekcje-hobby': 'bi-bookmark-star icon-orange'
} %}
{% set category_color_map = {
    'militaria-do-1945': 'gray',
    'militaria-wspolczesne': 'gray',
    'numizmatyka-i-falerystyka': 'yellow',
    'starodruki-i-dokumenty': 'blue',
    'znaleziska-wykrywacz': 'green',
    'sztuka-i-rekodzielo': 'purple',
    'design-i-antyki': 'orange',
    'kolekcje-hobby': 'orange'
} %}


{% if offers %}
	<div class="d-flex justify-content-between align-items-center mb-3">
		<span class="text-muted small">{{ 'Found'|lang }}: {{ offers|length }}</span>
		<form method="get" class="d-flex align-items-center gap-2">
			{% for key,item in pagination.page_url.sort %}
				{% if item|length %}
					{% for key2,item2 in item %}
						{% if item2|length %}
							{% for key3,item3 in item2 %}
								<input type="hidden" name="{{ key }}[{{ key2 }}][{{ key3 }}]" value="{{ item3 }}">
							{% endfor %}
						{% else %}
							<input type="hidden" name="{{ key }}[{{ key2 }}]" value="{{ item2 }}">
						{% endif %}
					{% endfor %}
				{% else %}
					<input type="hidden" name="{{ key }}" value="{{ item }}">
				{% endif %}
			{% endfor %}
			<label class="small text-nowrap mb-0 d-none d-sm-block" for="sort_select">{{ 'Sort by'|lang }}:</label>
			<select name="sort" id="sort_select" onchange="this.form.submit()" title="{{ 'Select sort method'|lang }}" class="form-select form-select-sm" style="width: auto">
				<option value="newest" {% if get.sort=="newest" %}selected{% endif %}>{{ 'Newest'|lang }}</option>
				<option value="oldest" {% if get.sort=="oldest" %}selected{% endif %}>{{ 'Oldest'|lang }}</option>
				<option value="cheapest" {% if get.sort=="cheapest" %}selected{% endif %}>{{ 'Cheapest'|lang }}</option>
				<option value="most_expensive" {% if get.sort=="most_expensive" %}selected{% endif %}>{{ 'Most expensive'|lang }}</option>
				<option value="a-z" {% if get.sort=="a-z" %}selected{% endif %}>{{ 'A - Z'|lang }}</option>
				<option value="z-a" {% if get.sort=="z-a" %}selected{% endif %}>{{ 'Z - A'|lang }}</option>
				{% if get.distance>0 %}
					<option value="nearest" {% if get.sort=="nearest" %}selected{% endif %}>{{ 'Nearest'|lang }}</option>
					<option value="farthest" {% if get.sort=="farthest" %}selected{% endif %}>{{ 'Farthest'|lang }}</option>
				{% endif %}
			</select>
		</form>
	</div>

	<div class="row g-3">
	{% for offer in offers %}
		<div class="col-6 col-sm-12">
			<div class="card offer-list-card h-100 border-0 shadow-sm {% if offer.promoted %}offer-card-promoted{% endif %}" itemscope itemtype="https://schema.org/Product">
				<div class="row g-0 h-100">
				<div class="col-md-3 col-sm-4 position-relative offer-list-img-wrapper">
					<a href="{{ path('offer',offer.id,offer.slug) }}" title="{{ offer.name }}" itemprop="url" class="d-block h-100">
						{% if offer.thumb %}
							<img src="upload/photos/{{ offer.thumb }}" alt="{{ offer.name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" itemprop="image" class="img-fluid h-100 w-100 object-fit-cover" loading="lazy" width="300" height="300">
						{% endif %}
						{% set cat_slug = offer.category_slug|lower %}
						{% set icon_data = category_icons[cat_slug]|default('bi-tag-fill icon-gray')|split(' ') %}
						{% set color_class = category_color_map[cat_slug]|default('gray') %}
						<div class="offer-no-image-placeholder placeholder-{{ color_class }} d-flex align-items-center justify-content-center w-100 h-100" style="{% if offer.thumb %}display: none !important;{% endif %} min-height: unset; border-radius: 0;">
							<i class="bi {{ icon_data[0] }}" style="font-size: 3.5rem !important;"></i>
						</div>
					</a>
					<button type="button" class="btn btn-light rounded-circle shadow-sm position-absolute bottom-0 end-0 m-2 wishlist-btn d-flex align-items-center justify-content-center" data-id="{{ offer.id }}" style="width: 32px; height: 32px; z-index: 10; border: none; padding: 0;" title="{{ 'Add to clipboard'|lang }}">
						<i class="bi {% if offer.clipboard %}bi-heart-fill{% else %}bi-heart{% endif %} text-danger fs-5"></i>
					</button>
					{% if offer.promoted %}
						<span class="offer-badge">{{ 'Promoted'|lang }}</span>
					{% endif %}
				</div>
				<div class="col-md-9 col-sm-8 d-flex flex-column justify-content-between p-3 p-md-4">
					<div>
						<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start mb-2 gap-2">
							<h4 class="h5 card-title mb-0"><a href="{{ path('offer',offer.id,offer.slug) }}" title="{{ offer.name }}" class="text-dark text-decoration-none fw-bold"><span itemprop="name" class="offer-title-clamp">{{ offer.name }}</span></a></h4>
							<div class="text-sm-end text-start" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
								{% if offer.price_free %}
									<span class="badge bg-success fs-6">{{ 'For free'|lang }}</span>
									<meta itemprop="price" content="0" />
								{% elseif offer.price>0 %}
									<span class="text-primary fw-bold fs-5" itemprop="price" content="{{ offer.price }}">{{ offer.price|showCurrency }}</span>
									{% if offer.price_negotiate %}<span class="text-muted small d-block">({{ 'to negotiate'|lang }})</span>{% endif %}
								{% else %}
									<span class="text-muted small">{{ 'Price on request'|lang }}</span>
									<meta itemprop="price" content="0" />
								{% endif %}
								<meta itemprop="priceCurrency" content="{{ settings.currency_code|default('PLN') }}"/>
								<link itemprop="availability" href="https://schema.org/InStock" />
								<link itemprop="itemCondition" href="https://schema.org/UsedCondition" />
							</div>
						</div>
						<p class="text-muted small mb-3 d-none d-md-block" itemprop="disambiguatingDescription" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;">{{ offer.description|striptags|slice(0,180) }}{% if offer.description|striptags|length > 180 %}...{% endif %}</p>
					</div>
					
					<div class="d-flex flex-column gap-2 pt-2 border-top">
						<div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
							<div class="d-flex flex-wrap gap-1">
								{% if settings.show_types and offer.type_name %}
									{% if offer.type_slug == 'kupie' %}
										<span class="badge bg-primary text-white text-wrap" style="white-space:normal;overflow-wrap:break-word;"><i class="bi bi-briefcase me-1"></i>{{ offer.type_name }}</span>
									{% elseif offer.type_slug == 'uslugi' %}
										<span class="badge bg-success text-white text-wrap" style="white-space:normal;overflow-wrap:break-word;"><i class="bi bi-tools me-1"></i>{{ offer.type_name }}</span>
									{% else %}
										<span class="badge bg-light text-secondary border text-wrap" style="white-space:normal;overflow-wrap:break-word;">{{ offer.type_name }}</span>
									{% endif %}
								{% endif %}
								{% if offer.user_id > 0 %}
									{% if offer.verified_company %}
										<span class="badge bg-dark text-white text-wrap" style="white-space:normal;overflow-wrap:break-word;" title="Oferta wystawiona przez zweryfikowaną firmę"><i class="bi bi-building me-1"></i>B2B</span>
									{% else %}
										<span class="badge bg-secondary text-white text-wrap" style="white-space:normal;overflow-wrap:break-word;" title="Oferta osoby prywatnej"><i class="bi bi-person me-1"></i>B2C</span>
									{% endif %}
								{% endif %}
								{% if offer.category_name %}<span class="badge bg-light text-secondary border"><i class="bi bi-tag me-1"></i>{{ offer.category_name }}</span>{% endif %}
								{% if offer.state_name %}<span class="badge bg-light text-secondary border"><i class="bi bi-geo-alt me-1"></i>{{ offer.state_name }}{% if offer.state2_name %}, {{ offer.state2_name }}{% endif %}</span>{% endif %}
								{% if offer.distance %}<span class="badge bg-light text-secondary border"><i class="bi bi-compass me-1"></i>{{ offer.distance|number_format(1, '.', ',') }} km</span>{% endif %}
							</div>
							<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-1 gap-sm-3">
								{% if offer.username %}
									<div class="small text-muted d-flex align-items-center gap-1 me-2">
										<i class="bi bi-person me-1"></i><a href="{{ path('profile', 0, offer.username) }}" class="text-decoration-none text-secondary fw-semibold">{{ offer.username }}</a>
										{% if settings.enable_verification_badges %}
											{% if offer.verified_email %}<i class="bi bi-envelope-check-fill text-success ms-1" data-bs-toggle="tooltip" title="Zweryfikowany e-mail"></i>{% endif %}
											{% if offer.verified_phone %}<i class="bi bi-telephone-fill text-info ms-1" data-bs-toggle="tooltip" title="Zweryfikowany telefon"></i>{% endif %}
											{% if offer.verified_company %}<i class="bi bi-building-fill-check text-primary ms-1" data-bs-toggle="tooltip" title="Firma zweryfikowana"></i>{% endif %}
										{% endif %}
									</div>
								{% endif %}
								<div class="small text-muted">
									<i class="bi bi-calendar3 me-1"></i>{{ offer.date_start|date("d-m-Y") }}
								</div>
							</div>
						</div>

						{% if controller=='my_offers' or controller=='clipboard' %}
							<div class="d-flex flex-wrap gap-2 mt-2 pt-2 border-top">
								{% if controller=='my_offers' %}
									<a href="{{ path('add') }}/?add_similar={{ offer.id }}" title="{{ 'Add similar'|lang }}: {{ offer.name }}" class="btn btn-sm btn-outline-success"><i class="bi bi-plus-lg me-1"></i>{{ 'Add similar'|lang }}</a>
									<a href="{{ path('edit',offer.id,offer.slug) }}" title="{{ 'Edit offer'|lang }}: {{ offer.name }}" class="btn btn-sm btn-outline-info"><i class="bi bi-pencil me-1"></i>{{ 'Edit'|lang }}</a>
									<button title="{{ 'Finish offer'|lang }}: {{ offer.name }}" class="btn btn-sm btn-outline-warning {% if not offer.active %}disabled{% endif %}" data-bs-toggle="modal" data-bs-target="#finish_offer_{{ offer.id }}"><i class="bi bi-check-circle me-1"></i>{{ 'Finish'|lang }}</button>
									<button title="{{ 'Delete offer'|lang }}: {{ offer.name }}" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#remove_offer_{{ offer.id }}"><i class="bi bi-trash me-1"></i>{{ 'Delete'|lang }}</button>
									{% if settings.allow_refresh_offer %}
										<button title="{{ 'Refresh offer'|lang }}: {{ offer.name }}" class="btn btn-sm btn-outline-primary {% if not offer.refresh.active %}disabled{% endif %}" data-bs-toggle="modal" data-bs-target="#refresh_offer_{{ offer.id }}"><i class="bi bi-arrow-clockwise me-1"></i>{{ 'Refresh offer'|lang }}</button>
										{% if not offer.refresh.active %}
											<span class="small align-self-center">
												{% if offer.refresh.not_confirmed %}
													<span class="text-danger">{{ 'Offer is not approved'|lang }}</span>
												{% elseif offer.refresh.must_payed %}
													<span class="text-danger">{{ 'You must pay for offer'|lang }}</span>
												{% else %}
													<span class="text-muted">{{ 'Available for'|lang }} {{ offer.refresh.days }} {{ 'days'|lang }}</span>
												{% endif %}
											</span>
										{% elseif not offer.active %}
											<span class="text-danger small align-self-center">{{ 'Offer is inactive'|lang }}</span>
										{% endif %}
									{% endif %}
								{% elseif controller=='clipboard' %}
									<form method="post" class="mb-0">
										<input type="hidden" name="action" value="clipboard_remove">
										<input type="hidden" name="id" value="{{ offer.id }}">
										<input type="hidden" name="token" value="{{ generateToken('clipboard_remove') }}">
										<button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-bookmark-dash me-1"></i>{{ 'Remove from clipboard'|lang }}</button>
									</form>
								{% endif %}
							</div>
							{% if controller=='my_offers' %}
								<div class="w-100 mt-3 p-3 bg-light rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-3 text-secondary">
									<div class="d-flex flex-wrap gap-3 align-items-center">
										<span class="badge bg-white text-dark border p-2 d-flex align-items-center gap-2" title="{{ 'Views'|lang }}: {{ offer.view_all|default(0) }} / {{ 'Unique'|lang }}: {{ offer.view_unique|default(0) }}">
											<i class="bi bi-eye-fill text-primary fs-5"></i>
											<span>
												<strong>{{ offer.view_all|default(0) }}</strong>
												<span class="text-muted small">({{ offer.view_unique|default(0) }} {{ 'unique'|lang }})</span>
											</span>
										</span>
										<span class="badge bg-white text-dark border p-2 d-flex align-items-center gap-2" title="{{ 'Phone clicks'|lang }}">
											<i class="bi bi-telephone-fill text-success fs-5"></i>
											<strong>{{ offer.clicks_phone|default(0) }}</strong>
										</span>
										<span class="badge bg-white text-dark border p-2 d-flex align-items-center gap-2" title="{{ 'Email reveals'|lang }}">
											<i class="bi bi-envelope-at-fill text-info fs-5"></i>
											<strong>{{ offer.clicks_email|default(0) }}</strong>
										</span>
										<span class="badge bg-white text-dark border p-2 d-flex align-items-center gap-2" title="{{ 'Website clicks'|lang }}">
											<i class="bi bi-globe text-warning fs-5"></i>
											<strong>{{ offer.clicks_website|default(0) }}</strong>
										</span>
									</div>
									<div class="small text-muted d-none d-md-block">
										<i class="bi bi-graph-up-arrow me-1 text-accent"></i> {{ 'Statistics'|lang }}
									</div>
								</div>
							{% endif %}
						{% endif %}
					</div>
				</div>
			</div>
		</div>

		{% if controller=='my_offers' %}
			{% if offer.active %}
				<div class="modal fade" id="finish_offer_{{ offer.id }}" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title fw-bold">{{ 'Finish offer'|lang }}: {{ offer.name }}</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<form method="post" class="form">
								<input type="hidden" name="action" value="finish_offer">
								<input type="hidden" name="id" value="{{ offer.id }}">
								<input type="hidden" name="token" value="{{ generateToken('finish_offer') }}">
								<div class="modal-body">
									<p>{{ 'Are you sure you want to finish offer'|lang }} <strong>{{ offer.name }}</strong>?</p>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ 'Cancel'|lang }}</button>
									<input type="submit" class="btn btn-warning" value="{{ 'Finish'|lang }}">
								</div>
							</form>
						</div>
					</div>
				</div>
			{% endif %}
			<div class="modal fade" id="remove_offer_{{ offer.id }}" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title fw-bold text-danger">{{ 'Delete offer'|lang }}: {{ offer.name }}</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<form method="post" class="form">
							<input type="hidden" name="action" value="remove_offer">
							<input type="hidden" name="id" value="{{ offer.id }}">
							<input type="hidden" name="token" value="{{ generateToken('remove_offer') }}">
							<div class="modal-body">
								<p>{{ 'Are you sure you want to delete offer'|lang }} <strong>{{ offer.name }}</strong>?</p>
								<p class="text-danger small mb-0"><i class="bi bi-exclamation-triangle me-1"></i>{{ 'This operation cannot be undone!'|lang }}</p>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ 'Cancel'|lang }}</button>
								<input type="submit" class="btn btn-danger" value="{{ 'Remove'|lang }}">
							</div>
						</form>
					</div>
				</div>
			</div>
			{% if offer.refresh.active %}
				<div class="modal fade" id="refresh_offer_{{ offer.id }}" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title fw-bold">{{ 'Refresh offer'|lang }}: {{ offer.name }}</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<form method="post" class="form">
								<input type="hidden" name="action" value="refresh_offer">
								<input type="hidden" name="id" value="{{ offer.id }}">
								<input type="hidden" name="token" value="{{ generateToken('refresh_offer') }}">
								<div class="modal-body">
									<p>{{ 'Are you sure to refresh offer'|lang }} <strong>{{ offer.name }}</strong>?</p>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ 'Cancel'|lang }}</button>
									<input type="submit" class="btn btn-primary" value="{{ 'Refresh offer'|lang }}">
								</div>
							</form>
						</div>
					</div>
				</div>
			{% endif %}
		{% endif %}
		</div>
	{% endfor %}
	</div>
	
	<div class="mt-4 d-flex justify-content-center">
		{% include 'pagination.tpl' %}
	</div>
{% else %}
	<div class="text-center py-5">
		<i class="bi bi-search display-3 text-muted mb-3 d-block"></i>
		<h3 class="text-danger fw-bold">{{ 'Nothing found'|lang }}</h3>
		<p class="text-muted">{{ 'Try changing your search filters.'|lang }}</p>
	</div>
{% endif %}
