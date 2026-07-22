{% extends "main.tpl" %}

{% block content %}
<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center error-page-wrapper">
            <h1 class="error-title">404</h1>
            <h2 class="display-5 fw-bold mb-3 text-dark">{{ 'Error 404'|lang }}</h2>
            <p class="lead text-muted mb-4 error-description">
                {{ 'The page you are looking for has been moved, deleted, or simply never existed.'|lang }}
            </p>
            <div class="d-flex gap-2 justify-content-center pt-3">
                <a href="{{ settings.base_url }}" class="btn btn-accent btn-lg px-5 shadow-sm">{{ 'Home'|lang }}</a>
            </div>
        </div>
    </div>
</div>
{% endblock %}