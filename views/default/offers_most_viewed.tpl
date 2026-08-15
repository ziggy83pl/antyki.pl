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

<div class="row g-3">
{% for offer in most_viewed_offers %}
	{% set col_size = 'col-lg-4 col-md-6 col-12' %}
	{% if most_viewed_offers|length == 4 %}
		{% set col_size = 'col-lg-3 col-md-6 col-6' %}
	{% elseif most_viewed_offers|length >= 5 %}
		{% set col_size = 'col-lg-2 col-md-4 col-sm-6 col-6' %}
	{% endif %}

	<div class="{{ col_size }}">
		<div class="card offer-card h-100 shadow-sm position-relative overflow-hidden {% if offer.promoted %}offer-card-promoted{% endif %}" itemscope itemtype="https://schema.org/Product">
			<div class="position-relative">
				<a href="{{ path('offer',offer.id,offer.slug) }}" title="{{ offer.name }}" itemprop="url" class="offer-card-image d-block">
					{% if offer.thumb %}
						<img src="upload/photos/{{ offer.thumb }}" alt="{{ offer.name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" itemprop="image" loading="lazy" width="300" height="300">
					{% endif %}
					{% set cat_slug = offer.category_slug|lower %}
					{% set icon_data = category_icons[cat_slug]|default('bi-tag-fill icon-gray')|split(' ') %}
					{% set color_class = category_color_map[cat_slug]|default('gray') %}
					<div class="offer-no-image-placeholder placeholder-{{ color_class }} d-flex align-items-center justify-content-center w-100 h-100" style="{% if offer.thumb %}display: none !important;{% endif %} min-height: unset; aspect-ratio: 1/1;">
						<i class="bi {{ icon_data[0] }}" style="font-size: 3rem !important;"></i>
					</div>

					<span class="badge bg-danger text-white position-absolute bottom-0 start-0 m-2 shadow-sm rounded-pill px-2 py-1 fw-bold" style="font-size: 0.72rem; z-index: 5;">
						<i class="bi bi-fire me-1"></i>TOP
					</span>

					{% if offer.promoted %}<span class="offer-badge">{{ 'Promoted'|lang }}</span>{% endif %}
				</a>

				<button type="button" class="btn btn-light rounded-circle shadow-sm position-absolute bottom-0 end-0 m-2 wishlist-btn d-flex align-items-center justify-content-center" data-id="{{ offer.id }}" style="width: 32px; height: 32px; z-index: 10; border: none; padding: 0;" title="{{ 'Add to clipboard'|lang }}">
					<i class="bi {% if offer.clipboard %}bi-heart-fill{% else %}bi-heart{% endif %} text-danger fs-5"></i>
				</button>
			</div>

			<div class="card-body d-flex flex-column">
				<div class="d-flex justify-content-between align-items-center mb-2 gap-1 flex-wrap">
					{% if offer.category_name %}
						<span class="badge bg-light text-secondary border text-truncate" style="max-width: 140px; font-size: 0.7rem;">
							<i class="bi bi-folder2-open me-1"></i>{{ offer.category_name }}
						</span>
					{% endif %}
					<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill ms-auto" style="font-size: 0.7rem;" title="Liczba wyświetleń">
						<i class="bi bi-eye-fill me-1"></i>{{ offer.view_all|default(0) }}
					</span>
				</div>

				<h3 class="h6 card-title mb-2 offer-title-clamp">
					<a href="{{ path('offer',offer.id,offer.slug) }}" title="{{ offer.name }}" class="main-color-2 text-decoration-none fw-bold">
						<span itemprop="name">{{ offer.name }}</span>
					</a>
				</h3>

				{% if offer.state_name %}
					<div class="small text-muted mb-2">
						<i class="bi bi-geo-alt me-1 text-danger"></i>{{ offer.state_name }}{% if offer.state2_name %}, {{ offer.state2_name }}{% endif %}
					</div>
				{% endif %}

				<div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
					<div class="offer-card-meta mb-0" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
						{% if offer.price_free %}
							<span class="badge bg-success fs-6">{{ 'For free'|lang }}</span>
							<meta itemprop="price" content="0" />
						{% elseif offer.price > 0 %}
							<span class="fw-bold fs-6 text-dark" itemprop="price" content="{{ offer.price }}">{{ offer.price|showCurrency }}</span>
							{% if offer.price_negotiate %}<span class="text-muted small ms-1">({{ 'to negotiate'|lang }})</span>{% endif %}
						{% else %}
							<span class="text-muted small">{{ 'Price on request'|lang }}</span>
							<meta itemprop="price" content="0" />
						{% endif %}
						<meta itemprop="priceCurrency" content="{{ settings.currency_code|default('PLN') }}"/>
						<link itemprop="availability" href="https://schema.org/InStock" />
						<link itemprop="itemCondition" href="https://schema.org/UsedCondition" />
					</div>
					<a href="{{ path('offer',offer.id,offer.slug) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1" style="font-size: 0.78rem;">
						Zobacz <i class="bi bi-arrow-right-short"></i>
					</a>
				</div>
			</div>
		</div>
	</div>
{% endfor %}
</div>
