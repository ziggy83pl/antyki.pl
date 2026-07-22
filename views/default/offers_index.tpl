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

{% for offer in offers %}
	{% if loop.index%6 == 1 %} 
		<div class="row g-3">
	{% endif %}
	<div class="col-lg-2 col-md-4 col-sm-6 col-6">
		<div class="card offer-card h-100 {% if offer.promoted %}offer-card-promoted{% endif %}" itemscope itemtype="https://schema.org/Product">
			<div class="position-relative">
				<a href="{{ path('offer',offer.id,offer.slug) }}" title="{{ offer.name }}" itemprop="url" class="offer-card-image">
					{% if offer.thumb %}
						<img src="upload/photos/{{ offer.thumb }}" alt="{{ offer.name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" itemprop="image" loading="lazy" width="300" height="300">
					{% endif %}
					{% set cat_slug = offer.category_slug|lower %}
					{% set icon_data = category_icons[cat_slug]|default('bi-tag-fill icon-gray')|split(' ') %}
					{% set color_class = category_color_map[cat_slug]|default('gray') %}
					<div class="offer-no-image-placeholder placeholder-{{ color_class }} d-flex align-items-center justify-content-center w-100 h-100" style="{% if offer.thumb %}display: none !important;{% endif %} min-height: unset; aspect-ratio: 1/1;">
						<i class="bi {{ icon_data[0] }}" style="font-size: 3rem !important;"></i>
					</div>
					{% if offer.promoted %}<span class="offer-badge">{{ 'Promoted'|lang }}</span>{% endif %}
					{% if offer.type_name == 'Pilne' %}<span class="offer-badge-urgent">Pilne</span>{% endif %}
					{% set time_diff = "now"|date("U") - offer.date|date("U") %}
					{% if time_diff < 259200 %}
						{% set hours_diff = time_diff // 3600 %}
						{% set days_diff = hours_diff // 24 %}
						<span class="offer-badge-new">{{ 'New'|lang }} <small class="fw-normal" style="opacity: 0.85;">({% if days_diff == 1 %}1 {{ 'day'|lang }}{% elseif days_diff > 1 %}{{ days_diff }} {{ 'days'|lang }}{% elseif hours_diff > 0 %}{{ hours_diff }}h{% else %}<1h{% endif %})</small></span>
					{% endif %}
				</a>
				<button type="button" class="btn btn-light rounded-circle shadow-sm position-absolute bottom-0 end-0 m-2 wishlist-btn d-flex align-items-center justify-content-center" data-id="{{ offer.id }}" style="width: 32px; height: 32px; z-index: 10; border: none; padding: 0;" title="{{ 'Add to clipboard'|lang }}">
					<i class="bi {% if offer.clipboard %}bi-heart-fill{% else %}bi-heart{% endif %} text-danger fs-5"></i>
				</button>
			</div>
			<div class="card-body">
				{% if offer.type_name %}
					<div class="mb-2 d-flex flex-wrap gap-1">
						{% if offer.type_slug == 'kupie' %}
							<span class="badge bg-primary text-white" style="font-size: 0.7rem;"><i class="bi bi-briefcase me-1"></i>Zlecenie</span>
						{% elseif offer.type_slug == 'uslugi' %}
							<span class="badge bg-success text-white" style="font-size: 0.7rem;"><i class="bi bi-tools me-1"></i>Oferta</span>
						{% else %}
							<span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">{{ offer.type_name }}</span>
						{% endif %}
						{% if offer.user_id > 0 %}
							{% if offer.verified_company %}
								<span class="badge bg-dark text-white" style="font-size: 0.7rem;" title="Oferta firmy"><i class="bi bi-building me-1"></i>B2B</span>
							{% else %}
								<span class="badge bg-secondary text-white" style="font-size: 0.7rem;" title="Oferta prywatna"><i class="bi bi-person me-1"></i>B2C</span>
							{% endif %}
						{% endif %}
					</div>
				{% else %}
					{% if offer.user_id > 0 %}
					<div class="mb-2 d-flex flex-wrap gap-1">
						{% if offer.verified_company %}
							<span class="badge bg-dark text-white" style="font-size: 0.7rem;" title="Oferta firmy"><i class="bi bi-building me-1"></i>B2B</span>
						{% else %}
							<span class="badge bg-secondary text-white" style="font-size: 0.7rem;" title="Oferta prywatna"><i class="bi bi-person me-1"></i>B2C</span>
						{% endif %}
					</div>
					{% endif %}
				{% endif %}
				<h3 class="h6 card-title mb-2 offer-title-clamp"><a href="{{ path('offer',offer.id,offer.slug) }}" title="{{ offer.name }}" class="main-color-2 text-decoration-none"><span itemprop="name">{{ offer.name }}</span></a></h3>
				{% if offer.state_name %}
					<div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>{{ offer.state_name }}{% if offer.state2_name %}, {{ offer.state2_name }}{% endif %}</div>
				{% endif %}
				<div class="offer-card-meta" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
					{% if offer.price_free %}
						<span class="badge bg-success">{{ 'For free'|lang }}</span>
						<meta itemprop="price" content="0" />
					{% elseif offer.price>0 %}
						<span class="fw-semibold" itemprop="price" content="{{ offer.price }}">{{ offer.price|showCurrency }}</span>
						{% if offer.price_negotiate %}<span class="text-muted small d-block">{{ 'to negotiate'|lang }}</span>{% endif %}
					{% else %}
						<span class="text-muted small">{{ 'Price on request'|lang }}</span>
						<meta itemprop="price" content="0" />
					{% endif %}
					<meta itemprop="priceCurrency" content="{{ settings.currency_code|default('PLN') }}"/>
					<link itemprop="availability" href="https://schema.org/InStock" />
								<link itemprop="itemCondition" href="https://schema.org/UsedCondition" />
				</div>
				{% if offer.username %}
					<div class="mt-2 pt-2 border-top text-muted small d-flex flex-wrap align-items-center gap-1">
						<i class="bi bi-person"></i>
						<a href="{{ path('profile', 0, offer.username) }}" class="text-decoration-none text-muted fw-semibold text-truncate" style="max-width: 80px;" title="{{ offer.username }}">{{ offer.username }}</a>
						{% if settings.enable_verification_badges %}
							{% if offer.verified_email %}<i class="bi bi-envelope-check-fill text-success" data-bs-toggle="tooltip" title="Zweryfikowany e-mail"></i>{% endif %}
							{% if offer.verified_phone %}<i class="bi bi-telephone-fill text-info" data-bs-toggle="tooltip" title="Zweryfikowany telefon"></i>{% endif %}
							{% if offer.verified_company %}<i class="bi bi-building-fill-check text-primary" data-bs-toggle="tooltip" title="Firma zweryfikowana"></i>{% endif %}
						{% endif %}
					</div>
				{% endif %}
			</div>
		</div>
	</div>
	{% if loop.index%6 == 0 %} 
		</div><br>
	{% endif %}
{% endfor %}

{% if (offers|length)%6 > 0 %}</div>{% endif %}
