{% if pagination.page_count %}
	<div class="text-center mb-2">
		<small class="text-muted">
			Strona {{ pagination.page_number }} z {{ pagination.page_count }} | Pokazano {{ pagination.limit_start + 1 }} - {% if pagination.page_number * pagination.limit > pagination.total_items %}{{ pagination.total_items }}{% else %}{{ pagination.page_number * pagination.limit }}{% endif %} z {{ pagination.total_items }}
		</small>
	</div>
	<div class="text-center">
		<ul class="pagination justify-content-center flex-wrap">
			<li class="page-item {% if pagination.page_number==1 %}disabled{% endif %}"><a class="page-link {% if pagination.page_number==1 %}inactive{% endif %}" href="{% if pagination.page_url.page_admin %}?{% endif %}{{ pagination.page_url.page_admin }}" title="{{ 'First page'|lang }}" aria-label="{{ 'First page'|lang }}">&laquo;</a></li>
			{% for this_page in pagination.page_start..pagination.page_count %}
				{% if loop.index0<10 %}
					<li class="page-item {% if pagination.page_number==this_page %}disabled{% endif %}"><a class="page-link {% if pagination.page_number==this_page %}inactive{% endif %}" href="?{{ pagination.page_url.page_admin }}{% if pagination.page_url.page_admin %}&{% endif %}page={{ this_page }}" title="{{ 'Page'|lang }}: {{ this_page }}">{{ this_page }}</a></li>
				{% endif %}
			{% endfor %}
		   <li class="page-item {% if pagination.page_number==pagination.page_count %}disabled{% endif %}"><a class="page-link {% if pagination.page_number==pagination.page_count %}inactive{% endif %}" href="?{{ pagination.page_url.page_admin }}{% if pagination.page_url.page_admin %}&{% endif %}page={{  pagination.page_count }}" title="{{ 'Last page'|lang }}" aria-label="{{ 'Last page'|lang }}">&raquo;</a></li>
		</ul>
	</div>
{% endif %}
