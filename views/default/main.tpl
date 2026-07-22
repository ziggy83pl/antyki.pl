<!doctype html>
<html lang="{{ settings.lang }}">
<head>
  <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	{% set meta_title = settings.seo_title|default(settings.title) %}
	{% set meta_desc = settings.seo_description|default(settings.description) %}
	{% set og_type = "website" %}
	
	{% if controller == 'index' %}
		{% set meta_desc = "Giełda Antyków, Militariów i Znalezisk - lokalny portal ogłoszeniowy (C2C)" %}
	{% elseif controller == 'offer' and offer %}
		{% set meta_title = offer.name ~ " - " ~ settings.title %}
		{% set meta_desc = offer.description|striptags|slice(0, 200) %}
	{% elseif controller == 'article' and article %}
		{% set meta_title = article.name ~ " - " ~ settings.title %}
		{% set meta_desc = article.description_short|default(article.content|striptags|slice(0, 200)) %}
		{% set og_type = "article" %}
	{% endif %}

	{% set og_image = settings.base_url ~ "/upload/images/building_materials.png" %}
	{% if profile.avatar %}
		{% set og_image = settings.base_url ~ "/upload/avatars/" ~ profile.avatar %}
	{% elseif controller == 'offer' and offer.photos %}
		{% set og_image = settings.base_url ~ "/upload/photos/" ~ offer.photos[0].folder ~ offer.photos[0].url %}
	{% elseif controller == 'article' and article.thumb %}
		{% set og_image = settings.base_url ~ "/" ~ article.thumb %}
	{% elseif settings.og_image %}
		{% set og_image = settings.base_url ~ "/" ~ settings.og_image %}
	{% endif %}

	<title>{{ meta_title }}</title>
	<meta name="description" content="{{ meta_desc }}">
	<meta name="keywords" content="{{ settings.seo_keywords|default(settings.keywords) }}">

	<!-- Geo targeting -->
	<meta name="geo.region" content="PL-PD" />
	<meta name="geo.placename" content="Łomża" />

	<!-- Open Graph / Facebook -->
	<meta property="og:type" content="{{ og_type }}">
	<meta property="og:url" content="{{ canonical_url }}">
	<meta property="og:title" content="{{ meta_title }}">
	<meta property="og:description" content="{{ meta_desc }}">
	<meta property="og:image" content="{{ og_image }}">
	<meta property="og:site_name" content="{{ settings.title }}">
	<meta property="og:locale" content="{{ settings.facebook_lang|default('pl_PL') }}">
	{% if settings.facebook_api %}<meta property="fb:app_id" content="{{ settings.facebook_api }}">{% endif %}

	<!-- Twitter -->
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="{{ meta_title }}">
	<meta name="twitter:description" content="{{ meta_desc }}">
	<meta name="twitter:image" content="{{ og_image }}">

	<link rel="canonical" href="{{ canonical_url }}">
	<script>
		(function() {
			const theme = localStorage.getItem('theme') || 'light';
			document.documentElement.setAttribute('data-bs-theme', theme);
			window.isLoggedIn = {{ user.id ? 'true' : 'false' }};
		})();
	</script>
	<base href="{{ settings.base_url }}/">

	<!-- CSS style -->
	{% block css %}
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

		<link rel="stylesheet" href="views/{{ settings.template }}/css/style.css?{{ settings.assets_version }}"/>
		{% if settings.favicon %}<link rel="shortcut icon" href="{{ settings.favicon }}">{% endif %}
		{% if settings.code_style %}<style>{{ settings.code_style|raw }}</style>{% endif %}
		<style>
			#footer_top ul.list-unstyled li {
				margin-bottom: 0.65rem;
			}
			#footer_top ul.list-unstyled a {
				display: inline-flex;
				align-items: center;
				gap: 0.5rem;
				color: #4b5563 !important;
				text-decoration: none;
				transition: color 0.2s ease, transform 0.2s ease;
			}
			#footer_top ul.list-unstyled a:hover {
				color: #f97316 !important;
				transform: translateX(5px);
			}
			#footer_top ul.list-unstyled a i {
				transition: transform 0.2s ease;
			}
			#footer_top ul.list-unstyled a:hover i {
				transform: scale(1.15);
			}

			/* Styling for mobile bottom navigation */
			.mobile-bottom-nav {
				position: fixed;
				bottom: 0;
				left: 0;
				right: 0;
				height: 65px;
				background: rgba(255, 255, 255, 0.95) !important;
				backdrop-filter: blur(10px);
				-webkit-backdrop-filter: blur(10px);
				border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
				z-index: 1040;
			}

			/* Add padding to body on mobile to prevent content clipping under bottom nav */
			@media (max-width: 767.98px) {
				body.site-body {
					padding-bottom: 75px !important;
				}
			}

			.col-nav {
				display: flex;
				justify-content: center;
				align-items: center;
				height: 100%;
			}

			.nav-item-mobile {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				color: #6b7280 !important;
				text-decoration: none !important;
				font-size: 0.75rem;
				width: 100%;
				height: 100%;
				transition: color 0.15s ease-in-out;
			}

			.nav-item-mobile:hover, .nav-item-mobile.active {
				color: #f97316 !important;
			}

			.nav-item-mobile.active i {
				transform: scale(1.1);
				transition: transform 0.2s ease;
			}

			.nav-label-mobile {
				font-size: 0.65rem;
				font-weight: 500;
				margin-top: 2px;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				max-width: 100%;
				display: block;
			}

			/* Prominent Add Button styling */
			.col-nav-add {
				position: relative;
			}

			.nav-item-mobile-add {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				text-decoration: none !important;
				color: #6b7280 !important;
				width: 100%;
				height: 100%;
				position: relative;
				top: -10px;
			}

			.nav-add-btn {
				width: 48px;
				height: 48px;
				border-radius: 50%;
				background: linear-gradient(135deg, #f97316 0%, #facc15 100%) !important;
				display: flex;
				align-items: center;
				justify-content: center;
				box-shadow: 0 4px 10px rgba(249, 115, 22, 0.35) !important;
				transition: transform 0.2s ease-in-out;
			}

			.nav-add-btn i {
				font-size: 1.55rem;
				line-height: 1;
				display: inline-flex;
				align-items: center;
				justify-content: center;
			}

			.nav-item-mobile-add:hover .nav-add-btn {
				transform: scale(1.08) rotate(90deg);
			}

			.nav-item-mobile-add:hover {
				color: #f97316 !important;
			}
		</style>
	{% endblock %}



	<!-- Schema.org JSON-LD Base -->
	{% block json_ld %}
	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "WebSite",
	  "name": "{{ settings.title|e('js') }}",
	  "url": "{{ settings.base_url }}/",
	  "description": "{{ settings.description|e('js') }}"
	}
	</script>
	{% if controller == 'offer' and offer %}
	<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "Offer",
	  "name": "{{ offer.name|e('js') }}",
	  "url": "{{ canonical_url }}",
	  "priceCurrency": "{{ settings.currency|default('PLN') }}",
	  "price": "{{ offer.price }}",
	  {% if offer.photos %}
	  "image": "{{ settings.base_url }}/upload/photos/{{ offer.photos[0].folder }}{{ offer.photos[0].url }}",
	  {% endif %}
	  "availability": "https://schema.org/InStock"
	}
	</script>
	{% endif %}
	{% endblock %}

	<!-- other -->
	{% if settings.rss %}<link rel="alternate" type="application/rss+xml" href="{{ path('feed') }}{% if pagination.page_url.page %}?{{ pagination.page_url.page }}{% endif %}">{% endif %}
	{{ settings.code_head|raw }}
</head>
<body class="site-body">
	{% set share_url = canonical_url %}
	{% if controller == 'index' %}
		{% set share_title = settings.seo_title|default(settings.title) %}
		{% set share_text = "Giełda Antyków, Militariów i Znalezisk - lokalny portal ogłoszeniowy (C2C)." %}
	{% elseif controller == 'offer' and offer %}
		{% set share_title = offer.name %}
		{% set share_text = "Sprawdź ogłoszenie: " ~ offer.name ~ " na Giełdzie Antyków i Militariów!" %}
	{% elseif controller == 'article' and article %}
		{% set share_title = article.name %}
		{% set share_text = "Przeczytaj artykuł: " ~ article.name ~ " na Giełdzie Antyków i Militariów!" %}
	{% elseif controller == 'add' %}
		{% set share_title = "Dodaj ogłoszenie - Giełda Antyków & Militariów" %}
		{% set share_text = "Dodaj darmowe ogłoszenie na Giełdzie Antyków i Militariów!" %}
	{% else %}
		{% set share_title = settings.seo_title|default(settings.title) %}
		{% set share_text = settings.seo_description|default(settings.description)|slice(0, 150) %}
	{% endif %}

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

	<div id="top" class="container-fluid">
		<p class="text-end small text-white-50 mb-0"><span class="fw-semibold">Giełda Antyków & Militariów</span></p>
	</div>
	<nav class="navbar fixed-top navbar-expand-lg navbar-dark site-nav shadow-sm" id="menu_box">
		<div class="container">
			<a class="navbar-brand d-flex align-items-center" href="{{ settings.base_url }}" title="{{ settings.title }}">
				<span class="logo-text">Giełda <span class="logo-accent">Antyków & Militariów</span></span>
			</a>
			<div class="d-flex align-items-center gap-2 d-lg-none ms-auto me-2">
				<a href="{{ path('add') }}" title="{{ 'Add offer'|lang }}" class="btn btn-accent btn-sm p-0 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; border-radius: 50%;"><i class="bi bi-plus-lg" style="font-size: 1.1rem; line-height: 1;"></i></a>
				<button type="button" class="btn btn-outline-light btn-sm p-0 d-flex align-items-center justify-content-center share_page_btn" style="width: 34px; height: 34px; border-radius: 50%;" data-title="{{ share_title }}" data-text="{{ share_text }}" data-url="{{ share_url }}" title="Udostępnij stronę"><i class="bi bi-share-fill" style="font-size: 0.95rem; line-height: 1;"></i></button>
			</div>
			<button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="menu">
			<ul class="navbar-nav ms-auto align-items-lg-center gap-2 gap-lg-1">
				<li class="nav-item {% if controller=='index' %}active{% endif %}"><a href="{{ settings.base_url }}" title="{{ settings.title }}" class="nav-link">{{ 'Home'|lang }}</a></li>
				<li class="nav-item {% if controller=='offers' %}active{% endif %}"><a href="{{ path('offers') }}" title="{{ 'Search the best offers'|lang }}" class="nav-link">{{ 'Offers'|lang }}</a></li>
				<li class="nav-item d-lg-none">
					<a href="#" class="nav-link share_page_btn" data-title="{{ share_title }}" data-text="{{ share_text }}" data-url="{{ share_url }}"><i class="bi bi-share-fill me-2"></i> Udostępnij stronę</a>
				</li>
				{% if settings.enable_articles %}<li class="nav-item {% if controller=='articles' %}active{% endif %}"><a href="{{ path('articles') }}" title="{{ 'Articles'|lang }}" class="nav-link">{{ 'Articles'|lang }}</a></li>{% endif %}
				<li class="nav-item {% if controller=='info' %} active{% endif %}"><a href="{{ path('info') }}" title="{{ 'Info about us'|lang }}" class="nav-link">{{ 'Info'|lang }}</a></li>
				<li class="nav-item">
					<a href="{{ path('add') }}" title="{{ 'Add offer'|lang }}" class="btn btn-accent btn-pulse btn-sm px-3"><i class="bi bi-plus-lg me-1"></i> {{ 'Add offer'|lang }}</a>
				</li>
				{% if user.id %}
					<li class="nav-item dropdown mt-3 mt-lg-0">
						<a class="nav-link dropdown-toggle d-inline-flex align-items-center" href="#" id="menuMyAccount" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle me-2"></i>{{ 'My account'|lang }}</a>
						<div class="dropdown-menu dropdown-menu-end" aria-labelledby="menuMyAccount">
							<a href="{{ path('add') }}" title="{{ 'Add offer'|lang }}" class="dropdown-item"><i class="bi bi-plus-circle me-2"></i> {{ 'Add offer'|lang }}</a>
							<a href="{{ path('my_offers') }}" title="{{ 'My offers'|lang }}" class="dropdown-item"><i class="bi bi-list-ul me-2"></i> {{ 'My offers'|lang }}</a>
							<a href="{{ path('profile', 0, user.username) }}" title="{{ 'My profile'|lang }}" class="dropdown-item"><i class="bi bi-person me-2"></i> {{ 'My profile'|lang }}</a>
							<a href="{{ path('chat') }}" title="{{ 'Inbox'|lang }}" class="dropdown-item d-flex align-items-center justify-content-between">
								<span><i class="bi bi-chat-left-text me-2"></i> {{ 'Inbox'|lang }}</span>
								{% if unread_chat_count > 0 %}
									<span class="badge bg-danger rounded-pill">{{ unread_chat_count }}</span>
								{% endif %}
							</a>
							<a href="{{ path('clipboard') }}" title="{{ 'Clipboard'|lang }}" class="dropdown-item"><i class="bi bi-clipboard-check me-2"></i> {{ 'Clipboard'|lang }}</a>
							<a href="{{ path('settings') }}" title="{{ 'Settings'|lang }}" class="dropdown-item"><i class="bi bi-gear me-2"></i> {{ 'Settings'|lang }}</a>
							<a href="{{ path('suggestions') }}" title="{{ 'Suggestions'|lang }}" class="dropdown-item"><i class="bi bi-lightbulb me-2"></i> {{ 'Suggestions'|lang }}</a>
							<div class="dropdown-divider"></div>
							<a href="{{ settings.base_url }}/?log_out&token={{ generateToken('logout') }}" title="{{ 'Log out'|lang }}" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> {{ 'Log out'|lang }}</a>
						</div>
					</li>
				{% else %}
					<li class="nav-item mt-3 mt-lg-0">
						<a href="{{ path('login') }}" title="{{ 'Log in on the website'|lang }}" class="btn btn-outline-light btn-sm px-3"><i class="bi bi-person-fill me-1"></i> {{ 'Log in'|lang }}</a>
					</li>
				{% endif %}
				<li class="nav-item ms-lg-3 d-flex align-items-center mt-3 mt-lg-0">
					<button id="dark-mode-toggle" class="btn btn-outline-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Przełącz tryb ciemny" type="button">
						<i class="bi bi-moon-fill" id="dark-mode-icon" style="font-size: 1.1rem; line-height: 1;"></i>
					</button>
				</li>
			</ul>
		</div>
	</nav>

	{% if controller == 'offers' %}
		<div class="container d-lg-none mt-5 pt-4 mb-2">
			<button class="btn btn-primary w-100 py-3 shadow-sm d-flex align-items-center justify-content-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#search_box" aria-expanded="false" aria-controls="search_box">
				<i class="bi bi-sliders"></i> {{ 'Filter'|lang }}
			</button>
		</div>
	{% endif %}

	{% block content %}

	{% endblock %}

	{% if settings.ads_4 %}<div class="ads">{{ settings.ads_4|raw }}</div>{% endif %}
	<footer>
		<div id="footer_top">
			<br>
			<div class="container">
				<div class="row d-none d-md-block">
					<div class="col-md-6">
						<h4>{{ settings.title }}</h4>
						<p class="text-white-50 mb-2"><strong>Dedykowana Giełda Antyków & Militariów</strong> – zlecaj i znajduj fachowców szybciej i precyzyjniej niż na ogólnych portalach wielobranżowych.</p>
						{{ settings.footer_top|raw }}
						<br><br>
						<h5 class="text-white-50">Polecamy</h5>
						<a href="https://zamow-budowe.pages.dev/" target="_blank" rel="noopener" class="d-inline-flex align-items-center bg-white text-dark px-3 py-2 rounded text-decoration-none shadow-sm mb-4" style="border: 2px solid #facc15;">
							<i class="bi bi-cone-striped fs-3 me-2" style="color: #ea580c;"></i>
							<div class="text-start">
								<span class="d-block fw-bold" style="line-height: 1;">Zamów Budowę</span>
								<small class="text-muted" style="font-size: 0.75rem;">Profesjonalne usługi</small>
							</div>
						</a>
					</div>
					<div class="col-md-6">
						<h4>{{ 'Sitemap'|lang }}</h4>
						<ul class="list-unstyled row">
							<li class="col-sm-6"><a class="main-color-2" href="{{ settings.base_url }}" title="{{ settings.title }}"><i class="bi bi-house-door"></i> {{ 'Home'|lang }}</a></li>
							<li class="col-sm-6"><a class="main-color-2" href="{{ path('add') }}" title="{{ 'Add offer'|lang }}"><i class="bi bi-plus-circle"></i> {{ 'Add offer'|lang }}</a></li>
							<li class="col-sm-6"><a class="main-color-2" href="{{ path('offers') }}" title="{{ 'Search the best offers'|lang }}"><i class="bi bi-search"></i> {{ 'Offers'|lang }}</a></li>
							{% if user.id %}
								<li class="col-sm-6"><a class="main-color-2" href="{{ path('my_offers') }}" title="{{ 'My offers'|lang }}"><i class="bi bi-person-workspace"></i> {{ 'My offers'|lang }}</a></li>
								<li class="col-sm-6"><a class="main-color-2" href="{{ path('profile', 0, user.username) }}" title="{{ 'My profile'|lang }}"><i class="bi bi-person"></i> {{ 'My profile'|lang }}</a></li>
							{% endif %}
							<li class="col-sm-6"><a class="main-color-2" href="{{ path('login') }}" title="{{ 'Log in on the website'|lang }}"><i class="bi bi-box-arrow-in-right"></i> {{ 'Log in'|lang }}</a></li>
			   			    <li class="col-sm-6"><a class="main-color-2" href="{{ path('rules') }}" title="{{ 'Terms of service'|lang }}"><i class="bi bi-file-earmark-text"></i> {{ 'Terms of service'|lang }}</a></li>
			        		<li class="col-sm-6"><a class="main-color-2" href="{{ path('privacy_policy') }}" title="{{ 'Privacy policy'|lang }}"><i class="bi bi-shield-lock"></i> {{ 'Privacy policy'|lang }}</a></li>
							<li class="col-sm-6"><a class="main-color-2" href="{{ path('contact') }}" title="{{ 'Contact us'|lang }}"><i class="bi bi-envelope"></i> {{ 'Contact'|lang }}</a></li>
							<li class="col-sm-6"><a class="main-color-2" href="{{ path('info') }}" title="{{ 'Info about us'|lang }}"><i class="bi bi-info-circle"></i> {{ 'Info'|lang }}</a></li>
							{% if settings.enable_articles %}<li class="col-sm-6"><a class="main-color-2" href="{{ path('articles') }}" title="{{ 'Articles'|lang }}"><i class="bi bi-newspaper"></i> {{ 'Articles'|lang }}</a></li>{% endif %}
							<li class="col-sm-6"><a class="main-color-2" href="{{ path('suggestions') }}" title="{{ 'Suggestions'|lang }}"><i class="bi bi-lightbulb"></i> {{ 'Suggestions'|lang }}</a></li>
							{% if settings.rss %}<li class="col-sm-6"><a class="main-color-2" href="{{ path('feed') }}{% if pagination.page_url.page %}?{{ pagination.page_url.page }}{% endif %}" title="{{ 'RSS feed'|lang }}" target="_blank"><i class="bi bi-rss"></i> {{ 'RSS feed'|lang }}</a></li>{% endif %}
							<li class="col-sm-6"><a class="main-color-2" href="#" id="shareWebsiteLink" title="Udostępnij giełdę"><i class="bi bi-share"></i> Udostępnij giełdę</a></li>
						</ul>
						<br><br>
					</div>
				</div>
				
				{# Wklejenie skryptu zaufanych partnerów dla Łomży i okolic (do 30km) #}
				{% set is_lomza_region = false %}
				{% set lomza_towns_slugs = ['lomza', 'piatnica', 'nowogrod', 'miastkowo', 'sniadowo', 'jedwabne', 'wizna', 'stawiski', 'zbojna', 'maly-plock', 'rutki', 'zambrow', 'kolno', 'radzilow', 'szumowo'] %}
				{% set lomza_towns_names = ['łomża', 'lomza', 'piątnica', 'nowogród', 'miastkowo', 'śniadowo', 'jedwabne', 'wizna', 'stawiski', 'zbójna', 'mały płock', 'rutki', 'zambrów', 'kolno', 'radziłów', 'szumowo'] %}

				{% if get.state2 in lomza_towns_slugs %}
					{% set is_lomza_region = true %}
				{% elseif get.address %}
					{% set address_lower = get.address|lower %}
					{% for town in lomza_towns_names %}
						{% if town in address_lower %}
							{% set is_lomza_region = true %}
						{% endif %}
					{% endfor %}
				{% elseif offer is defined and offer %}
					{% if offer.state2_slug in lomza_towns_slugs %}
						{% set is_lomza_region = true %}
					{% else %}
						{% set state2_name_lower = offer.state2_name|lower %}
						{% set address_lower = offer.address|lower %}
						{% for town in lomza_towns_names %}
							{% if town in state2_name_lower or town in address_lower %}
								{% set is_lomza_region = true %}
							{% endif %}
						{% endfor %}
					{% endif %}
				{% endif %}

				{% if is_lomza_region %}
					<div class="row mt-4 pt-4 border-top" style="border-color: rgba(0,0,0,0.08) !important;">
						<div class="col-12 text-center pb-4">
							<div id="global-trusted-logos"></div>
						</div>
					</div>
					<script src="https://ziggy83pl.github.io/zasoby/portfolio-logos.js" defer></script>
				{% endif %}
			</div>
		</div>
		<div id="footer_bottom" class="text-center d-none d-md-block">
			{{ settings.footer_bottom|raw }}
			{{ settings.footer_text|raw }}
			<div class="mt-3 text-white-50" style="font-size: 0.85em;">
				Projekt i realizacja: <a href="https://wizytowka-online.pages.dev/" target="_blank" rel="noopener" class="text-white-50 text-decoration-none border-bottom border-secondary pb-1">Enterprise</a>
			</div>
		</div>
	</footer>

	<div id="cookies-message" class="text-center"><div class="container"></div>{{ 'This site uses cookies, so that our service may work better.'|lang }} <a href="javascript:closeCookiesWindow();" id="accept-cookies-checkbox" class="btn btn-outline-light btn-sm">{{ 'I accept'|lang }}</a></div></div>

	<a href="#" title="{{ 'Back to top'|lang }}" id="back_to_top" class="back_to_top_hidden"><img src="{{ settings.base_url }}/views/{{ settings.template }}/images/back_to_top.png" alt="Back to top" width="48" height="48"></a>

	{% if settings.facebook_side_panel %}
		<div id="facebook_side" class="hidden-xs">
			<div id="facebook_side_image"><img src="{{ settings.base_url }}/views/{{ settings.template }}/images/facebook-side.png" alt="Facebook" width="10" height="21"></div>
			<div class="fb-page" data-href="{{ settings.url_facebook }}" data-tabs="timeline" data-width="300" data-height="350" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"><blockquote cite="{{ settings.url_facebook }}" class="fb-xfbml-parse-ignore"><a href="{{ settings.url_facebook }}">Facebook</a></blockquote></div>
		</div>
	{% endif %}

  {% if settings.rodo_alert %}
    <div class="modal fade" id="rodo-message">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-body">
          {{ settings.rodo_alert_text|raw }}
          </div>
          <div class="modal-footer">
            <a href="javascript:closeRodoWindow();" class="btn btn-1">{{ 'I agree to the processing my personal data'|lang }}</a>
          </div>
        </div>
      </div>
    </div>
  {% endif %}

	<div id="fb-root"></div>

	<!-- JS javascript -->
	{% block javascript %}

		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
		<script src="views/{{ settings.template }}/js/engine.js?{{ settings.assets_version }}"></script>

		{% if settings.facebook_side_panel or controller in ['article', 'offer', 'profile'] %}
		<script>(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/{{ settings.facebook_lang|default(en_US) }}/all.js#xfbml=1";
			fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));
		</script>
		{% endif %}

		{{ settings.analytics|raw }}

	{% endblock %}

	{{ settings.code_body|raw }}

<!-- Modal Udostępniania Giełdy (Share Website Modal) -->
<div class="modal fade" id="shareWebsiteModal" tabindex="-1" aria-labelledby="shareWebsiteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow-lg rounded-4" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="shareWebsiteModalLabel">Udostępnij Giełdę</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-grid gap-2 mb-3">
          <!-- Kopiowanie linku -->
          <button type="button" class="btn btn-light d-flex align-items-center justify-content-between p-3 rounded-3 border-0" id="copyWebsiteLinkBtn" style="transition: all 0.2s;">
            <span class="fw-semibold text-dark"><i class="bi bi-link-45deg me-2 text-primary fs-5"></i> Kopiuj link</span>
            <i class="bi bi-clipboard text-muted" id="copyWebsiteIcon"></i>
          </button>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
          <!-- Facebook -->
          <a href="https://www.facebook.com/sharer/sharer.php?u={{ settings.base_url|url_encode }}" target="_blank" rel="noopener noreferrer" class="d-flex flex-column align-items-center text-decoration-none share-website-social-btn" style="width: 60px;">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 45px; height: 45px; transition: transform 0.2s;">
              <i class="bi bi-facebook fs-5"></i>
            </div>
            <span class="small text-muted" style="font-size: 0.7rem;">Facebook</span>
          </a>
          <!-- X (Twitter) -->
          <a href="https://x.com/intent/tweet?url={{ settings.base_url|url_encode }}&text={{ settings.title|url_encode }}" target="_blank" rel="noopener noreferrer" class="d-flex flex-column align-items-center text-decoration-none share-website-social-btn" style="width: 60px;">
            <div class="bg-dark bg-opacity-10 text-dark rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 45px; height: 45px; transition: transform 0.2s;">
              <i class="bi bi-twitter-x fs-5"></i>
            </div>
            <span class="small text-muted" style="font-size: 0.7rem;">X / Twitter</span>
          </a>
          <!-- WhatsApp -->
          <a href="https://api.whatsapp.com/send?text={{ ('Zobacz portal ogłoszeniowy: ' ~ settings.title ~ ' ' ~ settings.base_url)|url_encode }}" target="_blank" rel="noopener noreferrer" class="d-flex flex-column align-items-center text-decoration-none share-website-social-btn" style="width: 60px;">
            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 45px; height: 45px; transition: transform 0.2s;">
              <i class="bi bi-whatsapp fs-5"></i>
            </div>
            <span class="small text-muted" style="font-size: 0.7rem;">WhatsApp</span>
          </a>
          <!-- E-mail -->
          <a href="mailto:?subject={{ settings.title|url_encode }}&body={{ ('Zobacz portal ogłoszeniowy Giełda Antyków & Militariów - darmowe ogłoszenia: ' ~ settings.base_url)|url_encode }}" class="d-flex flex-column align-items-center text-decoration-none share-website-social-btn" style="width: 60px;">
            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 45px; height: 45px; transition: transform 0.2s;">
              <i class="bi bi-envelope-fill fs-5"></i>
            </div>
            <span class="small text-muted" style="font-size: 0.7rem;">E-mail</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	function showWebsiteToast(message) {
		const toast = document.createElement('div');
		toast.innerText = message;
		toast.style.cssText = `
			position: fixed;
			bottom: 30px;
			left: 50%;
			transform: translateX(-50%);
			background: #10b981;
			color: #fff;
			padding: 12px 24px;
			border-radius: 30px;
			box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
			z-index: 9999;
			font-weight: 600;
			font-size: 0.9rem;
			opacity: 0;
			transition: opacity 0.3s ease, bottom 0.3s ease;
		`;
		document.body.appendChild(toast);
		toast.offsetHeight; // force reflow
		toast.style.opacity = '1';
		toast.style.bottom = '40px';
		
		setTimeout(() => {
			toast.style.opacity = '0';
			toast.style.bottom = '30px';
			setTimeout(() => toast.remove(), 300);
		}, 3000);
	}

	const shareWebsiteLink = document.getElementById('shareWebsiteLink');
	if (shareWebsiteLink) {
		shareWebsiteLink.addEventListener('click', function(e) {
			e.preventDefault();
			const title = "{{ settings.title|escape('js') }}";
			const text = "Sprawdź Giełdę Antyków i Militariów - darmowe ogłoszenia!";
			const url = window.location.origin;

			const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
			const isStandalone = window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;
			const useNativeShare = navigator.share && !(isIOS && isStandalone);

			const fallbackModal = () => {
				const shareWebsiteModal = new bootstrap.Modal(document.getElementById('shareWebsiteModal'));
				shareWebsiteModal.show();
			};

			if (useNativeShare) {
				navigator.share({
					title: title,
					text: text,
					url: url
				}).then(() => {
					showWebsiteToast('Dziękujemy za udostępnienie!');
				}).catch(err => {
					console.error('Sharing failed:', err);
					if (err.name !== 'AbortError') {
						fallbackModal();
					}
				});
			} else {
				fallbackModal();
			}
		});
	}

	const copyWebsiteLinkBtn = document.getElementById('copyWebsiteLinkBtn');
	if (copyWebsiteLinkBtn) {
		copyWebsiteLinkBtn.addEventListener('click', function() {
			const url = window.location.origin;
			
			navigator.clipboard.writeText(url).then(function() {
				const copyWebsiteIcon = document.getElementById('copyWebsiteIcon');
				copyWebsiteIcon.className = 'bi bi-check2 text-success fs-5';
				copyWebsiteLinkBtn.classList.add('bg-success-subtle');
				showWebsiteToast('Link został skopiowany!');
				
				setTimeout(() => {
					copyWebsiteIcon.className = 'bi bi-clipboard text-muted';
					copyWebsiteLinkBtn.classList.remove('bg-success-subtle');
				}, 2000);
			}).catch(err => {
				console.error('Could not copy text: ', err);
			});
		});
	}

	document.querySelectorAll('.share-website-social-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const modalEl = document.getElementById('shareWebsiteModal');
			const modal = bootstrap.Modal.getInstance(modalEl);
			if (modal) {
				modal.hide();
			}
			showWebsiteToast('Dziękujemy za udostępnienie!');
		});
	});
});
</script>
	<!-- Dolny pasek nawigacyjny na telefonach (Mobile Bottom Navigation) -->
	<div class="mobile-bottom-nav d-block d-md-none border-top shadow-lg">
		<div class="container-fluid h-100 px-1">
			<div class="row h-100 align-items-center text-center flex-nowrap g-0">
				
				<!-- Główna -->
				<div class="col col-nav">
					<a href="{{ settings.base_url }}" class="nav-item-mobile {% if controller == 'index' %}active{% endif %}">
						<i class="bi bi-house fs-5"></i>
						<span class="nav-label-mobile">Główna</span>
					</a>
				</div>

				<!-- Szukaj -->
				<div class="col col-nav">
					<a href="{{ path('offers') }}" class="nav-item-mobile {% if controller == 'offers' %}active{% endif %}">
						<i class="bi bi-search fs-5"></i>
						<span class="nav-label-mobile">Szukaj</span>
					</a>
				</div>
				
				<!-- Dodaj (Wyróżniony) -->
				<div class="col col-nav col-nav-add">
					<a href="{{ path('add') }}" class="nav-item-mobile-add">
						<div class="nav-add-btn shadow-sm">
							<i class="bi bi-plus-lg text-white"></i>
						</div>
						<span class="nav-label-mobile mt-1">Dodaj</span>
					</a>
				</div>
				
				<!-- Wiadomości (Moje oferty / czat) -->
				<div class="col col-nav">
					<a href="{{ path('chat') }}" class="nav-item-mobile {% if controller == 'chat' %}active{% endif %}">
						<div class="position-relative d-inline-block">
							<i class="bi bi-chat-left-text fs-5"></i>
							{% if unread_chat_count > 0 %}
								<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.65rem; padding: 0.25em 0.4em; transform: translate(25%, -25%) !important;">
									{{ unread_chat_count }}
								</span>
							{% endif %}
						</div>
						<span class="nav-label-mobile">Wiadomości</span>
					</a>
				</div>
				
				<!-- Konto -->
				<div class="col col-nav">
					{% if user.id %}
						<a href="{{ path('profile', 0, user.username) }}" class="nav-item-mobile {% if controller == 'profile' and profile.username == user.username %}active{% endif %}">
							<i class="bi bi-person fs-5"></i>
							<span class="nav-label-mobile">Konto</span>
						</a>
					{% else %}
						<a href="{{ path('login') }}" class="nav-item-mobile {% if controller == 'login' %}active{% endif %}">
							<i class="bi bi-person fs-5"></i>
							<span class="nav-label-mobile">Konto</span>
						</a>
					{% endif %}
			</div>
		</div>
	</div>
</div>

	{% if trigger_cron_10min or trigger_cron_daily or trigger_cron_scraper or trigger_cron_scraper_mylomza %}
	<!-- Pseudo-Cron triggers for InfinityFree hosting -->
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		{% if trigger_cron_10min %}
		fetch('{{ settings.base_url }}/cron-10min.php?token={{ settings.cron_token }}').catch(() => {});
		{% endif %}
		{% if trigger_cron_daily %}
		fetch('{{ settings.base_url }}/cron-daily.php?token={{ settings.cron_token }}').catch(() => {});
		{% endif %}
		{% if trigger_cron_scraper %}
		fetch('{{ settings.base_url }}/cron-scraper.php?token={{ settings.cron_token }}').catch(() => {});
		{% endif %}
		{% if trigger_cron_scraper_mylomza %}
		fetch('{{ settings.base_url }}/cron-mylomza.php?token={{ settings.cron_token }}').catch(() => {});
		{% endif %}
	});
	</script>
	{% endif %}

</body>
</html>
