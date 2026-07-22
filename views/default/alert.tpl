
{% if alert_success %}
  {% for alert in alert_success %}
    <div class="alert alert-success d-flex align-items-center gap-2" role="alert"><i class="bi bi-check-circle-fill fs-5"></i> {{ alert }}</div>
  {% endfor %}
{% endif %}
{% if alert_success or alert_danger %}
  <div id="js_scroll_page"></div>
{% endif %}
{% if alert_danger %}
  {% for alert in alert_danger %}
	 <div class="alert alert-danger d-flex align-items-center gap-2" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i> {{ alert }}</div>
  {% endfor %}
{% endif %}
