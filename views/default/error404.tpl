{% extends "main.tpl" %}

{% block content %}
<div class="page_box text-center py-5">
    <h1 class="display-4 fw-bold mb-3 error-title">
        {{ 'Error 404'|lang }}
    </h1>
    <p class="lead text-muted error-description">
        {{ 'The page you are looking for has been moved, deleted, or simply never existed.'|lang }}
    </p>
    <a href="{{ settings.base_url }}" class="btn btn-primary mt-4">{{ 'Home'|lang }}</a>
</div>
{% endblock %}