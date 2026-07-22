{# views/main.tpl #}
<!doctype html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="Keywords" content="admin panel">
	<meta name="Description" content="Admin panel for the CMS application.">
	<meta name="author" content="CMS Team">
	<title>{{ title }}</title>
	<meta name="color-scheme" content="light dark">
	<script>
		(function() {
			const theme = localStorage.getItem('admin-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
			document.documentElement.setAttribute('data-theme', theme);
		})();
	</script>


	<!-- Bootstrap 5.3 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<link rel="stylesheet" href="views/css/style.css?v=1.2">
	<link rel="shortcut icon" href="images/favicon.png"/>
	<!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pl.js"></script>
	<script src="https://cdn.ckeditor.com/ckeditor5/35.4.0/super-build/ckeditor.js"></script>
	<script src="https://cdn.ckeditor.com/ckeditor5/35.4.0/super-build/translations/pl.js"></script>
	<script type="module" src="js/engine_admin.js?v=1.3"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
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
	'noclegi': 'bi-house-door-fill icon-blue'
} %}

{% block wrapper %}
<div id="wrapper">
  <nav class="main-nav navbar navbar-expand-lg bg-white border-bottom sticky-top" role="navigation">
    <div class="container-fluid">
      <a class="navbar-brand" href="?" title="Admin Panel" id="logo"><span class="logo-text">Ogłoszenia<span class="logo-accent">Nova</span></span></a>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#left-navigation" aria-controls="left-navigation" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="top-navbar-content">
        <ul class="navbar-nav ms-auto d-none d-lg-flex align-items-center">
          <li class="nav-item me-3">
            <button type="button" class="btn btn-link nav-link p-0 border-0 theme-toggle-btn" title="{{ 'Toggle dark mode'|lang }}">
              <i class="bi bi-moon-fill fs-5 theme-toggle-icon"></i>
            </button>
          </li>
          <li class="nav-item"><a class="nav-link text-primary fw-bold" href="/" target="_blank" title="{{ 'Home'|lang }}"><i class="bi bi-box-arrow-up-right me-1"></i> Strona główna ogłoszeń</a></li>
          <li class="nav-item"><a class="nav-link" href="?controller=admin" title="{{ 'Admin Panel Settings'|lang }}"><i class="bi bi-person-circle"></i> Admin</a></li>
          <li class="nav-item"><a class="nav-link" href="?log_out&token={{ generateToken('admin_logout') }}" title="{{ 'Log out'|lang }}"><i class="bi bi-box-arrow-right"></i> {{ 'Log out'|lang }}</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="d-flex shadow-sm" id="main-container">
    <div class="sidebar collapse d-lg-block" id="left-navigation">
      <div class="sidebar-nav" id="sidebarCollapse">
		<ul class="nav flex-column mt-2" id="side-menu">
			<li class="nav-item {% if controller=='index' %}active{% endif %}"><a class="nav-link" href="?" title="Pulpit"><i class="bi bi-house-door"></i> Pulpit</a></li>
			<li class="nav-item {% if controller=='statistics' %}active{% endif %}"><a class="nav-link" href="?controller=statistics" title="{{ 'Statistics'|lang }}"><i class="bi bi-graph-up"></i> {{ 'Statistics'|lang }}</a></li>
			<li class="nav-item {% if controller=='offers' %}active{% endif %}"><a class="nav-link" href="?controller=offers" title="{{ 'Offers'|lang }}"><i class="bi bi-list-ul"></i> {{ 'Offers'|lang }}</a></li>
			<li class="nav-item {% if controller=='users' %}active{% endif %}"><a class="nav-link" href="?controller=users" title="{{ 'Users'|lang }}"><i class="bi bi-people"></i> {{ 'Users'|lang }}</a></li>
			<li class="nav-item {% if controller=='mailing' %}active{% endif %}"><a class="nav-link" href="?controller=mailing" title="{{ 'Mailing'|lang }}"><i class="bi bi-envelope"></i> {{ 'Mailing'|lang }}</a></li>
			<li class="nav-item {% if controller=='suggestions' %}active{% endif %}"><a class="nav-link" href="?controller=suggestions" title="Sugestie i Zgłoszenia"><i class="bi bi-lightbulb"></i> Sugestie</a></li>
			<li class="nav-item {% if controller=='opinions' %}active{% endif %}"><a class="nav-link" href="?controller=opinions" title="{{ 'Opinions'|lang }}"><i class="bi bi-star"></i> {{ 'Opinions'|lang }}</a></li>
			<li class="nav-item {% if controller=='chat' %}active{% endif %}"><a class="nav-link" href="?controller=chat" title="Czat"><i class="bi bi-chat-left-dots"></i> Czat</a></li>
			
			<li class="nav-item">
				<a class="nav-link dropdown-toggle" href="#submenu_additional" data-bs-toggle="collapse" role="button" aria-expanded="false">
					<i class="bi bi-grid-3x3-gap"></i> {{ 'Additional'|lang }}
				</a>
				<div class="collapse {% if controller in ['categories','states','types','options'] %}show{% endif %}" id="submenu_additional">
					<ul class="nav flex-column ps-3">
						<li {% if controller=='categories' %}class="active"{% endif %}><a href="?controller=categories" title="{{ 'Categories'|lang }}">{{ 'Categories'|lang }}</a></li>
						<li {% if controller=='states' %}class="active"{% endif %}><a href="?controller=states" title="{{ 'States'|lang }}">{{ 'States'|lang }}</a></li>
						<li {% if controller=='types' %}class="active"{% endif %}><a href="?controller=types" title="{{ 'Types'|lang }}">{{ 'Types'|lang }}</a></li>
						<li {% if controller=='options' %}class="active"{% endif %}><a href="?controller=options" title="{{ 'Additional options'|lang }}">{{ 'Additional options'|lang }}</a></li>
					</ul>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link dropdown-toggle" href="#submenu_contents" data-bs-toggle="collapse" role="button" aria-expanded="false">
					<i class="bi bi-pencil-square"></i> {{ 'Contents'|lang }}
				</a>
				<div class="collapse {% if controller in ['index_page','login_page','mails','info','articles'] %}show{% endif %}" id="submenu_contents">
					<ul class="nav flex-column ps-3">
						<li {% if controller=='index_page' %}class="active"{% endif %}><a href="?controller=index_page" title="{{ 'Index page'|lang }}">{{ 'Index page'|lang }}</a></li>
						<li {% if controller=='login_page' %}class="active"{% endif %}><a href="?controller=login_page" title="{{ 'Login page'|lang }}">{{ 'Login page'|lang }}</a></li>
						<li {% if controller=='mails' %}class="active"{% endif %}><a href="?controller=mails" title="{{ 'Mails'|lang }}">{{ 'Mails'|lang }}</a></li>
						<li {% if controller=='info' %}class="active"{% endif %}><a href="?controller=info" title="{{ 'Info'|lang }}">{{ 'Info'|lang }}</a></li>
						<li {% if controller=='articles' %}class="active"{% endif %}><a href="?controller=articles" title="{{ 'Articles'|lang }}">{{ 'Articles'|lang }}</a></li>
					</ul>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link dropdown-toggle" href="#submenu_logs" data-bs-toggle="collapse" role="button" aria-expanded="false">
					<i class="bi bi-database"></i> {{ 'Logs'|lang }}
				</a>
				<div class="collapse {% if controller in ['logs_offers','logs_users','logs_mails','logs_security','reset_password'] %}show{% endif %}" id="submenu_logs">
					<ul class="nav flex-column ps-3">
						<li {% if controller=='logs_offers' %}class="active"{% endif %}><a href="?controller=logs_offers" title="{{ 'Offers'|lang }}">{{ 'Offers'|lang }}</a></li>
						<li {% if controller=='logs_users' %}class="active"{% endif %}><a href="?controller=logs_users" title="{{ 'Users'|lang }}">{{ 'Users'|lang }}</a></li>
						<li {% if controller=='logs_mails' %}class="active"{% endif %}><a href="?controller=logs_mails" title="{{ 'Mails'|lang }}">{{ 'Mails'|lang }}</a></li>
						<li {% if controller=='logs_security' %}class="active"{% endif %}><a href="?controller=logs_security" title="Zablokowane IP & Ataki">Zablokowane IP & Ataki</a></li>
						<li {% if controller=='reset_password' %}class="active"{% endif %}><a href="?controller=reset_password" title="{{ 'Reset password'|lang }}">{{ 'Reset password'|lang }}</a></li>
					</ul>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link dropdown-toggle" href="#submenu_logs_payments" data-bs-toggle="collapse" role="button" aria-expanded="false">
					<i class="bi bi-currency-euro"></i> {{ 'Logs payments'|lang }}
				</a>
				<div class="collapse {% if controller=='logs_payments' %}show{% endif %}" id="submenu_logs_payments">
					<ul class="nav flex-column ps-3">
						<li {% if controller=='logs_payments' and payments_type=='dotpay' %}class="active"{% endif %}><a href="?controller=logs_payments&type=dotpay" title="Dotpay">Dotpay</a></li>
						<li {% if controller=='logs_payments' and payments_type=='przelewy24' %}class="active"{% endif %}><a href="?controller=logs_payments&type=przelewy24" title="Dotpay">Przelewy24</a></li>
						<li {% if controller=='logs_payments' and payments_type=='paypal' %}class="active"{% endif %}><a href="?controller=logs_payments&type=paypal" title="PayPal">PayPal</a></li>
					</ul>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link dropdown-toggle" href="#submenu_settings" data-bs-toggle="collapse" role="button" aria-expanded="false">
					<i class="bi bi-gear"></i> {{ 'Settings'|lang }}
				</a>
				<div class="collapse {% if controller in ['settings_black_list','settings_days','settings_appearance','settings_social_media','settings_ads','settings_payments','settings_security','settings'] %}show{% endif %}" id="submenu_settings">
					<ul class="nav flex-column ps-3 bg-white bg-opacity-10 rounded py-2 my-1">
						<li {% if controller=='settings_black_list' %}class="active"{% endif %}><a href="?controller=settings_black_list" title="{{ 'Black list'|lang }}"><i class="bi bi-person-x me-2"></i> {{ 'Black list'|lang }}</a></li>
						<li {% if controller=='settings_days' %}class="active"{% endif %}><a href="?controller=settings_days" title="{{ 'Display time'|lang }}"><i class="bi bi-clock me-2"></i> {{ 'Display time'|lang }}</a></li>
						<li {% if controller=='settings_appearance' %}class="active"{% endif %}><a href="?controller=settings_appearance" title="{{ 'Appearance'|lang }}"><i class="bi bi-palette me-2"></i> {{ 'Appearance'|lang }}</a></li>
						<li {% if controller=='settings_social_media' %}class="active"{% endif %}><a href="?controller=settings_social_media" title="{{ 'Social Media'|lang }}"><i class="bi bi-share me-2"></i> {{ 'Social Media'|lang }}</a></li>
						<li {% if controller=='settings_ads' %}class="active"{% endif %}><a href="?controller=settings_ads" title="{{ 'Ads'|lang }}"><i class="bi bi-megaphone me-2"></i> {{ 'Ads'|lang }}</a></li>
						<li {% if controller=='settings_payments' %}class="active"{% endif %}><a href="?controller=settings_payments" title="{{ 'Payment settings'|lang }}"><i class="bi bi-credit-card me-2"></i> {{ 'Payment settings'|lang }}</a></li>
						<li {% if controller=='settings_security' %}class="active"{% endif %}><a href="?controller=settings_security" title="Ustawienia bezpieczeństwa"><i class="bi bi-shield-lock me-2"></i> Ustawienia bezpieczeństwa</a></li>
						<li {% if controller=='settings' %}class="active"{% endif %}><a href="?controller=settings" title="{{ 'General settings'|lang }}"><i class="bi bi-gear-fill me-2"></i> {{ 'General settings'|lang }}</a></li>
					</ul>
				</div>
			</li>
			<li class="nav-item d-lg-none">
				<a class="nav-link theme-toggle-btn" href="#" role="button" title="{{ 'Toggle dark mode'|lang }}">
					<i class="bi bi-moon-fill me-2 theme-toggle-icon"></i> <span class="theme-toggle-text">Tryb ciemny</span>
				</a>
			</li>
			<li class="nav-item d-lg-none"><a class="nav-link text-success fw-bold" href="/" target="_blank" title="{{ 'Home'|lang }}"><i class="bi bi-box-arrow-up-right me-1"></i> Strona główna ogłoszeń</a></li>
			<li class="nav-item d-lg-none"><a class="nav-link text-primary" href="?controller=admin" title="{{ 'Admin Panel Settings'|lang }}"><i class="bi bi-person-circle"></i> {{ 'Admin Panel Settings'|lang }}</a></li>
			<li class="nav-item d-lg-none"><a class="nav-link text-danger" href="?log_out&token={{ generateToken('admin_logout') }}" title="{{ 'Log out'|lang }}"><i class="bi bi-box-arrow-right"></i> {{ 'Log out'|lang }}</a></li>
		</ul>
      </div>
    </div>

    <main class="flex-grow-1" id="page-wrapper">
      {% if _ADMIN_TEST_MODE_ %}
        <div class="alert alert-warning mb-4 shadow-sm border-warning admin-alert">
          <i class="bi bi-info-circle me-2"></i><b>{{ 'Demo version of the Admin Panel. Editing functions are disabled'|lang }}</b>
        </div>
      {% endif %}

      {% block content %}{% endblock %}
    </main>
  </div>
</div>

{% endblock %}



<div class="modal fade" id="roxySelectFile" tabindex="-1" aria-labelledby="Select file">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			 <div class="modal-body p-0">
				<iframe frameborder="0" allowtransparency="true" style="width:100%; height:80vh;"></iframe>
			</div>
		</div>
	</div>
</div>

</body>
</html>
