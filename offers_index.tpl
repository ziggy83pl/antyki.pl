{% set category_icons = {
	'materialy-budowlane':      'bi-bricks icon-orange',
	'chemia-budowlana':         'bi-bricks icon-orange',
	'drewno-i-plyty':           'bi-bricks icon-orange',
	'materialy-scienne':        'bi-bricks icon-orange',
	'pokrycia-dachowe':         'bi-bricks icon-orange',
	'stal-i-zbrojenia':         'bi-bricks icon-orange',
	'inne-materialy':           'bi-bricks icon-orange',
	'maszyny-i-sprzet':         'bi-truck icon-yellow',
	'koparki-i-ladowarki':      'bi-truck icon-yellow',
	'betoniarki':               'bi-truck icon-yellow',
	'rusztowania-i-szalunki':   'bi-truck icon-yellow',
	'elektronarzedzia':         'bi-truck icon-yellow',
	'wynajem-sprzetu':          'bi-truck icon-yellow',
	'uslugi-budowlane':         'bi-tools icon-gray',
	'budowa-domow':             'bi-tools icon-gray',
	'remonty-i-wykonczenia':    'bi-tools icon-gray',
	'dekarstwo':                'bi-tools icon-gray',
	'instalacje-wod-kan-i-gaz': 'bi-tools icon-gray',
	'elektryka':                'bi-tools icon-gray',
	'prace-ziemne':             'bi-tools icon-gray',
	'zlecenia-budowlane':       'bi-clipboard-check icon-cyan',
	'inne':                     'bi-clipboard-check icon-cyan',
	'zlece-budowe':             'bi-clipboard-check icon-cyan',
	'zlece-remont':             'bi-clipboard-check icon-cyan',
	'szukam-podwykonawcy':      'bi-clipboard-check icon-cyan',
	'praca-w-budownictwie':     'bi-person-workspace icon-purple',
	'dam-prace':                'bi-person-workspace icon-purple',
	'szukam-pracy':             'bi-person-workspace icon-purple',
	'poszukuje-majstra':        'bi-person-workspace icon-purple',
	'poszukuje-pomocnika':      'bi-person-workspace icon-purple',
	'dzialki-budowlane':        'bi-map-fill icon-green',
	'dzialki-mieszkaniowe':     'bi-map-fill icon-green',
	'dzialki-uslugowe':         'bi-map-fill icon-green',
	'dzialki-przemyslowe':      'bi-map-fill icon-green',
	'dzialki-rolno-budowlane':  'bi-map-fill icon-green',
	'dzialki-rekreacyjne':      'bi-map-fill icon-green',
	'fotowoltanika':            'bi-sun-fill icon-yellow',
	'panele-fotowoltaiczne':    'bi-sun-fill icon-yellow',
	'inwertery-i-falowniki':    'bi-sun-fill icon-yellow',
	'akcesoria-i-konstrukcje':  'bi-sun-fill icon-yellow',
	'magazyny-energii':         'bi-sun-fill icon-yellow',
	'pompy-ciepla':             'bi-sun-fill icon-yellow',
	'uslugi-i-montaz':          'bi-sun-fill icon-yellow',
	'noclegi':                  'bi-house-door-fill icon-blue',
	'noclegi-polska':           'bi-house-door-fill icon-blue',
	'kwatery-pracownicze':      'bi-house-door-fill icon-blue',
	'design-i-antyki':          'bi-house-heart icon-orange',
	'sztuka-i-rekodzielo':       'bi-palette icon-orange',
	'kolekcje-hobby':           'bi-bookmark-star icon-orange'
} %}
{% set category_color_map = {
	'materialy-budowlane':      'orange',
	'chemia-budowlana':         'orange',
	'drewno-i-plyty':           'orange',
	'materialy-scienne':        'orange',
	'pokrycia-dachowe':         'orange',
	'stal-i-zbrojenia':         'orange',
	'inne-materialy':           'orange',
	'maszyny-i-sprzet':         'yellow',
	'koparki-i-ladowarki':      'yellow',
	'betoniarki':               'yellow',
	'rusztowania-i-szalunki':   'yellow',
	'elektronarzedzia':         'yellow',
	'wynajem-sprzetu':          'yellow',
	'uslugi-budowlane':         'gray',
	'budowa-domow':             'gray',
	'remonty-i-wykonczenia':    'gray',
	'dekarstwo':                'gray',
	'instalacje-wod-kan-i-gaz': 'gray',
	'elektryka':                'gray',
	'prace-ziemne':             'gray',
	'zlecenia-budowlane':       'cyan',
	'inne':                     'cyan',
	'zlece-budowe':             'cyan',
	'zlece-remont':             'cyan',
	'szukam-podwykonawcy':      'cyan',
	'praca-w-budownictwie':     'purple',
	'dam-prace':                'purple',
	'szukam-pracy':             'purple',
	'poszukuje-majstra':        'purple',
	'poszukuje-pomocnika':      'purple',
	'dzialki-budowlane':        'green',
	'dzialki-mieszkaniowe':     'green',
	'dzialki-uslugowe':         'green',
	'dzialki-przemyslowe':      'green',
	'dzialki-rolno-budowlane':  'green',
	'dzialki-rekreacyjne':      'green',
	'fotowoltanika':            'yellow',
	'panele-fotowoltaiczne':    'yellow',
	'inwertery-i-falowniki':    'yellow',
	'akcesoria-i-konstrukcje':  'yellow',
	'magazyny-energii':         'yellow',
	'pompy-ciepla':             'yellow',
	'uslugi-i-montaz':          'yellow',
	'noclegi':                  'blue',
	'noclegi-polska':           'blue',
	'kwatery-pracownicze':      'blue',
	'design-i-antyki':          'orange',
	'sztuka-i-rekodzielo':       'orange',
	'kolekcje-hobby':           'orange'
} %}

{% for offer in offers %}
	{% if loop.index%6 == 1 %} 
		<div class="row g-3">
	{% endif %}
	<div class="col-lg-2 col-md-4 col-sm-6 col-6">
		<div class="card offer-card h-100 {% if offer.promoted %}offer-card-promoted{% endif %}" itemscope itemtype="http://schema.org/Product">
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
			<div class="card-body">
				{% if offer.type_name %}
					<div class="mb-2">
						{% if offer.type_slug == 'kupie' %}
							<span class="badge bg-primary text-white" style="font-size: 0.7rem;"><i class="bi bi-briefcase me-1"></i>Zlecenie</span>
						{% elseif offer.type_slug == 'uslugi' %}
							<span class="badge bg-success text-white" style="font-size: 0.7rem;"><i class="bi bi-tools me-1"></i>Oferta</span>
						{% else %}
							<span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">{{ offer.type_name }}</span>
						{% endif %}
					</div>
				{% endif %}
				<h3 class="h6 card-title mb-2"><a href="{{ path('offer',offer.id,offer.slug) }}" title="{{ offer.name }}" class="main-color-2 text-decoration-none"><span itemprop="name">{{ offer.name }}</span></a></h3>
				<div class="offer-card-meta">
					{% if offer.price_free %}
						<span class="badge bg-success">{{ 'For free'|lang }}</span>
					{% elseif offer.price>0 %}
						<span class="fw-semibold">{{ offer.price|showCurrency }}</span>
						{% if offer.price_negotiate %}<span class="text-muted small d-block">{{ 'to negotiate'|lang }}</span>{% endif %}
					{% else %}
						<span class="text-muted">&nbsp;</span>
					{% endif %}
				</div>
			</div>
		</div>
	</div>
	{% if loop.index%6 == 0 %} 
		</div><br>
	{% endif %}
{% endfor %}

{% if (offers|length)%6 > 0 %}</div>{% endif %}
