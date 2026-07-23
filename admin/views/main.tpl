{# views/main.tpl #}
<!doctype html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<meta name="author" content="PRODOM Budownictwo">
	<meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net cdn.ckeditor.com code.jquery.com; img-src 'self' data: blob:;">
	<title>{{ title }} — Panel Admina</title>
	<meta name="color-scheme" content="light dark">

	{# ── Wczesne wykrycie motywu – przed renderem (bez flash) ── #}
	<script>
		(function() {
			const t = localStorage.getItem('admin-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
			document.documentElement.setAttribute('data-theme', t);
		})();
	</script>

	{# ── CSS ── #}
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<link rel="stylesheet" href="views/css/style.css?v=1.6">
	<link rel="shortcut icon" href="images/favicon.png"/>

	{# ── Własne style layoutu ── #}
	<style>
		/* ── Tokeny kolorów ── */
		:root {
			--sidebar-bg: #ffffff;
			--sidebar-border: #e9ecef;
			--sidebar-width: 230px;
			--nav-link-color: #495057;
			--nav-link-hover-bg: #f0f4ff;
			--nav-link-hover-color: #2563eb;
			--nav-link-active-bg: #e8f0fe;
			--nav-link-active-color: #1d4ed8;
			--nav-link-active-border: #2563eb;
			--topbar-bg: #ffffff;
			--topbar-border: #e9ecef;
			--body-bg: #f6f7fb;
			--badge-bg: #ef4444;
			--submenu-indent: 0.5rem;
			--transition: 0.18s ease;
		}
		[data-theme="dark"] {
			--sidebar-bg: #1a1d23;
			--sidebar-border: #2d3139;
			--nav-link-color: #c9cdd6;
			--nav-link-hover-bg: #252932;
			--nav-link-hover-color: #93b4ff;
			--nav-link-active-bg: #1e2d4e;
			--nav-link-active-color: #93b4ff;
			--nav-link-active-border: #3b82f6;
			--topbar-bg: #1a1d23;
			--topbar-border: #2d3139;
			--body-bg: #13151a;
		}
		[data-theme="dark"] .form-control,
		[data-theme="dark"] .form-select {
			background-color: #252932;
			border-color: #3b4252;
			color: #f8fafc;
		}
		[data-theme="dark"] .form-control::placeholder {
			color: #64748b;
		}
		[data-theme="dark"] .form-control:focus,
		[data-theme="dark"] .form-select:focus {
			background-color: #2d3139;
			color: #ffffff;
			border-color: #3b82f6;
		}
		[data-theme="dark"] .card {
			background-color: #1a1d23 !important;
			border-color: #2d3139 !important;
			color: #e2e8f0;
		}
		[data-theme="dark"] .table {
			color: #e2e8f0;
			border-color: #2d3139;
		}
		[data-theme="dark"] .table-hover > tbody > tr:hover > * {
			color: #ffffff;
			background-color: #252932;
		}
		[data-theme="dark"] .modal-content {
			background-color: #1a1d23;
			border-color: #2d3139;
			color: #e2e8f0;
		}
		[data-theme="dark"] .modal-header,
		[data-theme="dark"] .modal-footer {
			border-color: #2d3139;
		}
		[data-theme="dark"] .text-dark {
			color: #f8fafc !important;
		}
		[data-theme="dark"] .bg-light {
			background-color: #252932 !important;
		}
		[data-theme="dark"] .border-light {
			border-color: #3b4252 !important;
		}
		[data-theme="dark"] .bg-warning-subtle.text-warning,
		[data-theme="dark"] .bg-secondary-subtle.text-muted {
			color: #000000 !important;
		}
		[data-theme="dark"] .breadcrumb-item.active {
			color: #e2e8f0 !important;
		}
		[data-theme="dark"] .text-warning {
			color: #fde047 !important;
		}

		/* ── Body ── */
		body {
			background: var(--body-bg);
			min-height: 100vh;
		}

		/* ── Topbar ── */
		.main-nav {
			background: var(--topbar-bg) !important;
			border-bottom: 1px solid var(--topbar-border) !important;
			height: 54px;
			z-index: 1030;
		}
		.logo-text {
			font-weight: 700;
			font-size: 1.1rem;
			letter-spacing: -0.3px;
			color: #1e293b;
		}
		[data-theme="dark"] .logo-text { color: #e2e8f0; }
		.logo-accent { color: #2563eb; }

		/* ── Sidebar ── */
		.sidebar {
			width: var(--sidebar-width);
			min-height: calc(100vh - 54px);
			background: var(--sidebar-bg);
			border-right: 1px solid var(--sidebar-border);
			flex-shrink: 0;
			transition: width var(--transition);
			overflow-y: auto;
			overflow-x: hidden;
		}
		.sidebar-nav { padding: 0.5rem 0 1rem; }

		/* ── Nav linki ── */
		#side-menu .nav-link,
		#side-menu a {
			display: flex;
			align-items: center;
			gap: 0.55rem;
			padding: 0.45rem 1rem;
			font-size: 0.875rem;
			color: var(--nav-link-color);
			border-radius: 6px;
			margin: 1px 8px;
			text-decoration: none;
			border-left: 3px solid transparent;
			transition: background var(--transition), color var(--transition);
			white-space: nowrap;
		}
		#side-menu .nav-link:hover,
		#side-menu a:hover {
			background: var(--nav-link-hover-bg);
			color: var(--nav-link-hover-color);
		}
		#side-menu .nav-item.active > .nav-link,
		#side-menu li.active > a {
			background: var(--nav-link-active-bg);
			color: var(--nav-link-active-color);
			border-left-color: var(--nav-link-active-border);
			font-weight: 600;
		}
		#side-menu .bi { font-size: 1rem; flex-shrink: 0; }

		/* ── Separator sekcji ── */
		.sidebar-section-label {
			font-size: 0.675rem;
			font-weight: 700;
			letter-spacing: 0.08em;
			text-transform: uppercase;
			color: #94a3b8;
			padding: 1rem 1.25rem 0.25rem;
			pointer-events: none;
			user-select: none;
		}
		[data-theme="dark"] .sidebar-section-label { color: #475569; }

		/* ── Submenu ── */
		.submenu-list {
			list-style: none;
			padding: 0;
			margin: 0 0 0.25rem;
		}
		.submenu-list a {
			padding: 0.35rem 1rem 0.35rem 2.5rem !important;
			font-size: 0.825rem !important;
			margin: 1px 8px !important;
		}
		.submenu-list li.active > a {
			background: var(--nav-link-active-bg) !important;
			color: var(--nav-link-active-color) !important;
			font-weight: 600;
		}

		/* ── Badge powiadomień ── */
		.nav-badge {
			background: var(--badge-bg);
			color: #fff;
			font-size: 0.65rem;
			font-weight: 700;
			padding: 1px 5px;
			border-radius: 10px;
			margin-left: auto;
			line-height: 1.4;
		}

		/* ── Zawartość główna ── */
		#page-wrapper {
			min-height: calc(100vh - 54px);
			padding: 1.5rem;
		}

		/* ── Stopka ── */
		.admin-footer {
			margin-top: auto;
			padding: 0.75rem 1rem;
			font-size: 0.72rem;
			color: #94a3b8;
			border-top: 1px solid var(--sidebar-border);
			text-align: center;
		}
		[data-theme="dark"] .admin-footer { color: #475569; }

		/* ── Alert demo ── */
		.admin-alert { border-radius: 8px; font-size: 0.875rem; }

		/* ── Mobile toggler ── */
		@media (max-width: 991.98px) {
			.sidebar { width: 100%; min-height: unset; border-right: none; border-bottom: 1px solid var(--sidebar-border); }
			#page-wrapper { padding: 1rem; }
		}
	</style>

	{% block extra_head %}{% endblock %}
</head>
<body>

{% set category_icons = {
	'materialy-budowlane': 'bi-bricks icon-orange',
	'maszyny-i-sprzet': 'bi-truck icon-yellow',
	'uslugi-budowlane': 'bi-tools icon-gray',
	'zlecenia-budowlane': 'bi-clipboard-check icon-cyan',
	'inne': 'bi-clipboard-check icon-cyan',
	'praca-w-budownictwie': 'bi-person-workspace icon-purple',
	'dzialki-budowlane': 'bi-map-fill icon-green',
	'fotowoltanika': 'bi-sun-fill icon-yellow',
	'noclegi': 'bi-house-door-fill icon-blue',
	'design-i-antyki': 'bi-house-heart icon-orange',
	'sztuka-i-rekodzielo': 'bi-palette icon-orange',
	'kolekcje-hobby': 'bi-bookmark-star icon-orange'
} %}

{% block wrapper %}
<div id="wrapper" class="d-flex flex-column" style="min-height:100vh;">

	{# ══════════════════════════════════════════
	   TOPBAR
	══════════════════════════════════════════ #}
	<nav class="main-nav navbar navbar-expand-lg sticky-top px-3" role="banner">
		<a class="navbar-brand me-3" href="?" title="Panel Admina - Antyki.pl" id="logo">
			<span class="logo-text">Antyki<span class="logo-accent">.pl</span></span>
		</a>

		<button class="navbar-toggler border-0" type="button"
			data-bs-toggle="collapse" data-bs-target="#left-navigation"
			aria-controls="left-navigation" aria-expanded="false"
			aria-label="Otwórz menu">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="top-navbar-content">
			<ul class="navbar-nav flex-row align-items-center ms-auto">
				<li class="nav-item me-3 d-none d-md-flex align-items-center">
					<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-2" title="Czas do końca sesji (30 minut bezczynności)" data-bs-toggle="tooltip">
						<i class="bi bi-stopwatch me-1"></i> <span id="session-time-display" class="fw-bold">30:00</span>
					</span>
				</li>
				<li class="nav-item me-3 d-flex align-items-center">
					<span class="text-secondary small">
						<i class="bi bi-person-fill text-primary me-1 fs-6"></i> Jesteś zalogowany jako: <strong class="text-dark">{{ admin.username }}</strong>
						{% if is_superadmin or admin.role == 'superadmin' %}
							<span class="badge bg-danger-subtle text-danger border border-danger ms-1" style="font-size: 10px;">SuperAdmin</span>
						{% else %}
							<span class="badge bg-primary-subtle text-primary border border-primary ms-1" style="font-size: 10px;">Moderator</span>
						{% endif %}
					</span>
				</li>
				<li class="nav-item d-none d-lg-block me-3">
					<button type="button"
						class="btn btn-link nav-link p-1 border-0 theme-toggle-btn"
						title="Przełącz motyw">
						<i class="bi bi-moon-fill fs-6 theme-toggle-icon"></i>
					</button>
				</li>
				<li class="nav-item me-2">
					<a class="nav-link text-primary fw-semibold" href="/" target="_blank" rel="noopener">
						<i class="bi bi-box-arrow-up-right me-1"></i>Strona główna
					</a>
				</li>
				<li class="nav-item me-2">
					<a class="nav-link" href="?controller=admin" title="Ustawienia konta {{ admin.username }}">
						<i class="bi bi-gear-fill me-1"></i>Konto
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link text-danger fw-semibold" href="?log_out&token={{ generateToken('admin_logout') }}">
						<i class="bi bi-box-arrow-right me-1"></i>Wyloguj
					</a>
				</li>
			</ul>
		</div>
	</nav>

	{# ══════════════════════════════════════════
	   MAIN LAYOUT
	══════════════════════════════════════════ #}
	<div class="d-flex flex-column flex-lg-row flex-grow-1" id="main-container">

		{# ── SIDEBAR ── #}
		<aside class="sidebar collapse d-lg-block" id="left-navigation" role="navigation" aria-label="Menu administratora">
			<div class="sidebar-nav d-flex flex-column h-100" id="sidebarCollapse">
				<ul class="nav flex-column" id="side-menu">

					{# ── Główne ── #}
					<li class="sidebar-section-label">Główne</li>

					<li class="nav-item {% if controller=='index' %}active{% endif %}">
						<a class="nav-link" href="?" title="Pulpit">
							<i class="bi bi-house-door"></i> Pulpit
						</a>
					</li>
					<li class="nav-item {% if controller=='statistics' %}active{% endif %}">
						<a class="nav-link" href="?controller=statistics">
							<i class="bi bi-graph-up"></i> {{ 'Statistics'|lang }}
						</a>
					</li>
					<li class="nav-item {% if controller=='offers' %}active{% endif %}">
						<a class="nav-link" href="?controller=offers">
							<i class="bi bi-list-ul"></i> {{ 'Offers'|lang }}
							{% if pending_offers_count is defined and pending_offers_count > 0 %}
								<span class="nav-badge">{{ pending_offers_count }}</span>
							{% endif %}
						</a>
					</li>
					<li class="nav-item {% if controller=='users' %}active{% endif %}">
						<a class="nav-link" href="?controller=users">
							<i class="bi bi-people"></i> {{ 'Users'|lang }}
						</a>
					</li>

					{# ── Komunikacja ── #}
					<li class="sidebar-section-label">Komunikacja</li>

					<li class="nav-item {% if controller=='mailing' %}active{% endif %}">
						<a class="nav-link" href="?controller=mailing">
							<i class="bi bi-envelope"></i> {{ 'Mailing'|lang }}
						</a>
					</li>
					<li class="nav-item {% if controller=='suggestions' %}active{% endif %}">
						<a class="nav-link" href="?controller=suggestions">
							<i class="bi bi-lightbulb"></i> Sugestie
						</a>
					</li>
					<li class="nav-item {% if controller=='opinions' %}active{% endif %}">
						<a class="nav-link" href="?controller=opinions">
							<i class="bi bi-star"></i> {{ 'Opinions'|lang }}
						</a>
					</li>
					<li class="nav-item {% if controller=='chat' %}active{% endif %}">
						<a class="nav-link" href="?controller=chat">
							<i class="bi bi-chat-left-dots"></i> Czat
						</a>
					</li>

					{# ── Dane ── #}
					<li class="sidebar-section-label">Dane</li>

					{# Dodatkowe (submenu) #}
					<li class="nav-item">
						<a class="nav-link dropdown-toggle" href="#submenu_additional"
							data-bs-toggle="collapse" role="button"
							aria-expanded="{{ controller in ['categories','states','types','options'] ? 'true' : 'false' }}">
							<i class="bi bi-grid-3x3-gap"></i> {{ 'Additional'|lang }}
						</a>
						<div class="collapse {% if controller in ['categories','states','types','options'] %}show{% endif %}" id="submenu_additional">
							<ul class="submenu-list">
								<li {% if controller=='categories' %}class="active"{% endif %}>
									<a href="?controller=categories"><i class="bi bi-tag me-1"></i>{{ 'Categories'|lang }}</a>
								</li>
								<li {% if controller=='states' %}class="active"{% endif %}>
									<a href="?controller=states"><i class="bi bi-geo me-1"></i>{{ 'States'|lang }}</a>
								</li>
								<li {% if controller=='types' %}class="active"{% endif %}>
									<a href="?controller=types"><i class="bi bi-collection me-1"></i>{{ 'Types'|lang }}</a>
								</li>
								<li {% if controller=='options' %}class="active"{% endif %}>
									<a href="?controller=options"><i class="bi bi-sliders me-1"></i>{{ 'Additional options'|lang }}</a>
								</li>
							</ul>
						</div>
					</li>

					{# Treści (submenu) #}
					<li class="nav-item">
						<a class="nav-link dropdown-toggle" href="#submenu_contents"
							data-bs-toggle="collapse" role="button"
							aria-expanded="{{ controller in ['index_page','login_page','mails','info','articles'] ? 'true' : 'false' }}">
							<i class="bi bi-pencil-square"></i> {{ 'Contents'|lang }}
						</a>
						<div class="collapse {% if controller in ['index_page','login_page','mails','info','articles'] %}show{% endif %}" id="submenu_contents">
							<ul class="submenu-list">
								<li {% if controller=='index_page' %}class="active"{% endif %}>
									<a href="?controller=index_page"><i class="bi bi-house me-1"></i>{{ 'Index page'|lang }}</a>
								</li>
								<li {% if controller=='login_page' %}class="active"{% endif %}>
									<a href="?controller=login_page"><i class="bi bi-lock me-1"></i>{{ 'Login page'|lang }}</a>
								</li>
								<li {% if controller=='mails' %}class="active"{% endif %}>
									<a href="?controller=mails"><i class="bi bi-envelope-open me-1"></i>{{ 'Mails'|lang }}</a>
								</li>
								<li {% if controller=='info' %}class="active"{% endif %}>
									<a href="?controller=info"><i class="bi bi-info-circle me-1"></i>{{ 'Info'|lang }}</a>
								</li>
								<li {% if controller=='articles' %}class="active"{% endif %}>
									<a href="?controller=articles"><i class="bi bi-newspaper me-1"></i>{{ 'Articles'|lang }}</a>
								</li>
							</ul>
						</div>
					</li>

					{# ── Finansowe ── #}
					<li class="sidebar-section-label">Finansowe</li>

					{# Płatności (submenu) – Dotpay usunięty #}
					<li class="nav-item">
						<a class="nav-link dropdown-toggle" href="#submenu_logs_payments"
							data-bs-toggle="collapse" role="button"
							aria-expanded="{{ controller=='logs_payments' ? 'true' : 'false' }}">
							<i class="bi bi-currency-euro"></i> {{ 'Logs payments'|lang }}
						</a>
						<div class="collapse {% if controller=='logs_payments' %}show{% endif %}" id="submenu_logs_payments">
							<ul class="submenu-list">
								<li {% if controller=='logs_payments' and payments_type=='przelewy24' %}class="active"{% endif %}>
									<a href="?controller=logs_payments&type=przelewy24"><i class="bi bi-credit-card me-1"></i>Przelewy24</a>
								</li>
								<li {% if controller=='logs_payments' and payments_type=='paypal' %}class="active"{% endif %}>
									<a href="?controller=logs_payments&type=paypal"><i class="bi bi-paypal me-1"></i>PayPal</a>
								</li>
							</ul>
						</div>
					</li>

					{# ── System ── #}
					<li class="sidebar-section-label">System</li>

					{# Logi (submenu) #}
					<li class="nav-item">
						<a class="nav-link dropdown-toggle" href="#submenu_logs"
							data-bs-toggle="collapse" role="button"
							aria-expanded="{{ controller in ['logs_offers','logs_users','logs_mails','logs_security','reset_password'] ? 'true' : 'false' }}">
							<i class="bi bi-database"></i> {{ 'Logs'|lang }}
						</a>
						<div class="collapse {% if controller in ['logs_offers','logs_users','logs_mails','logs_security','reset_password'] %}show{% endif %}" id="submenu_logs">
							<ul class="submenu-list">
								<li {% if controller=='logs_offers' %}class="active"{% endif %}>
									<a href="?controller=logs_offers"><i class="bi bi-list-ul me-1"></i>{{ 'Offers'|lang }}</a>
								</li>
								<li {% if controller=='logs_users' %}class="active"{% endif %}>
									<a href="?controller=logs_users"><i class="bi bi-people me-1"></i>{{ 'Users'|lang }}</a>
								</li>
								<li {% if controller=='logs_mails' %}class="active"{% endif %}>
									<a href="?controller=logs_mails"><i class="bi bi-envelope me-1"></i>{{ 'Mails'|lang }}</a>
								</li>
								<li {% if controller=='logs_security' %}class="active"{% endif %}>
									<a href="?controller=logs_security"><i class="bi bi-shield-exclamation me-1"></i>Zablokowane IP & Ataki</a>
								</li>
								<li {% if controller=='reset_password' %}class="active"{% endif %}>
									<a href="?controller=reset_password"><i class="bi bi-key me-1"></i>{{ 'Reset password'|lang }}</a>
								</li>
							</ul>
						</div>
					</li>

					{# Ustawienia (submenu) #}
					<li class="nav-item">
						<a class="nav-link dropdown-toggle" href="#submenu_settings"
							data-bs-toggle="collapse" role="button"
							aria-expanded="{{ controller in ['settings_black_list','settings_days','settings_appearance','settings_social_media','settings_ads','settings_payments','settings_security','settings'] ? 'true' : 'false' }}">
							<i class="bi bi-gear"></i> {{ 'Settings'|lang }}
						</a>
						<div class="collapse {% if controller in ['settings_black_list','settings_days','settings_appearance','settings_social_media','settings_ads','settings_payments','settings_security','settings'] %}show{% endif %}" id="submenu_settings">
							<ul class="submenu-list">
								<li {% if controller=='settings_black_list' %}class="active"{% endif %}>
									<a href="?controller=settings_black_list"><i class="bi bi-person-x me-1"></i>{{ 'Black list'|lang }}</a>
								</li>
								<li {% if controller=='settings_days' %}class="active"{% endif %}>
									<a href="?controller=settings_days"><i class="bi bi-clock me-1"></i>{{ 'Display time'|lang }}</a>
								</li>
								<li {% if controller=='settings_appearance' %}class="active"{% endif %}>
									<a href="?controller=settings_appearance"><i class="bi bi-palette me-1"></i>{{ 'Appearance'|lang }}</a>
								</li>
								<li {% if controller=='settings_social_media' %}class="active"{% endif %}>
									<a href="?controller=settings_social_media"><i class="bi bi-share me-1"></i>{{ 'Social Media'|lang }}</a>
								</li>
								<li {% if controller=='settings_ads' %}class="active"{% endif %}>
									<a href="?controller=settings_ads"><i class="bi bi-megaphone me-1"></i>{{ 'Ads'|lang }}</a>
								</li>
								<li {% if controller=='settings_payments' %}class="active"{% endif %}>
									<a href="?controller=settings_payments"><i class="bi bi-credit-card me-1"></i>{{ 'Payment settings'|lang }}</a>
								</li>
								<li {% if controller=='settings_security' %}class="active"{% endif %}>
									<a href="?controller=settings_security"><i class="bi bi-shield-lock me-1"></i>Bezpieczeństwo</a>
								</li>
								<li {% if controller=='settings' %}class="active"{% endif %}>
									<a href="?controller=settings"><i class="bi bi-gear-fill me-1"></i>{{ 'General settings'|lang }}</a>
								</li>
							</ul>
						</div>
					</li>

					{# ── Mobile-only linki ── #}
					<li class="nav-item d-lg-none mt-2">
						<hr class="mx-3 my-1">
					</li>
					<li class="nav-item d-lg-none">
						<a class="nav-link theme-toggle-btn" href="#" role="button">
							<i class="bi bi-moon-fill me-2 theme-toggle-icon"></i>
							<span class="theme-toggle-text">Tryb ciemny</span>
						</a>
					</li>
					<li class="nav-item d-lg-none">
						<a class="nav-link text-success fw-semibold" href="/" target="_blank" rel="noopener">
							<i class="bi bi-box-arrow-up-right me-1"></i>Strona główna
						</a>
					</li>
					<li class="nav-item d-lg-none">
						<a class="nav-link" href="?controller=admin">
							<i class="bi bi-person-circle me-1"></i>Admin
						</a>
					</li>
					<li class="nav-item d-lg-none">
						<a class="nav-link text-danger" href="?log_out&token={{ generateToken('admin_logout') }}">
							<i class="bi bi-box-arrow-right me-1"></i>Wyloguj
						</a>
					</li>

				</ul>

				{# ── Stopka sidebara ── #}
				<div class="admin-footer mt-auto">
					GiełdaBudowlana CMS &bull; v1.3
				</div>
			</div>
		</aside>

		{# ── MAIN CONTENT ── #}
		<main class="flex-grow-1" id="page-wrapper" role="main">

			{% if _ADMIN_TEST_MODE_ %}
				<div class="alert alert-warning mb-4 shadow-sm border-warning admin-alert">
					<i class="bi bi-info-circle me-2"></i>
					<b>{{ 'Demo version of the Admin Panel. Editing functions are disabled'|lang }}</b>
				</div>
			{% endif %}

			{# Breadcrumbs – widoki mogą nadpisać ten blok #}
			{% block breadcrumb %}{% endblock %}

			{% block content %}{% endblock %}

		</main>
	</div>
</div>
{% endblock %}


{# ── Modal: Roxy File Manager ── #}
<div class="modal fade" id="roxySelectFile" tabindex="-1" aria-label="Wybierz plik" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title mb-0"><i class="bi bi-folder2-open me-2"></i>Menadżer plików</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
			</div>
			<div class="modal-body p-0">
				<iframe frameborder="0" allowtransparency="true" style="width:100%; height:80vh;" title="Menadżer plików"></iframe>
			</div>
		</div>
	</div>
</div>

{# ── JS – ładowane z defer na końcu ── #}
<script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pl.js" defer></script>
{# CKEditor 5 v43 – aktualna wersja (zamiast 35.4.0) #}
<script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js" defer></script>
<script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/translations/pl.umd.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js" defer></script>
<script type="module" src="js/engine_admin.js?v=1.5"></script>

{% block extra_scripts %}{% endblock %}

</body>
</html>
