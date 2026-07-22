
{% if alert_success %}
  {% for alert in alert_success %}
    <div class="alert alert-success" role="alert"><span class="bi bi-check-circle-fill"></span> {{ alert }}</div>
  {% endfor %}
{% endif %}
{% if alert_danger %}
  {% for alert in alert_danger %}
	 <div class="alert alert-danger" role="alert"><span class="bi bi-exclamation-triangle-fill"></span> {{ alert }}</div>
  {% endfor %}
{% endif %}
