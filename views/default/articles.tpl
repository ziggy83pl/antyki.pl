

{% if articles %}
	<div class="row g-4 articles-grid">
		{% for article in articles %}
			<div class="col-lg-4 col-md-6 col-12">
				<div class="card h-100 border-0 shadow-sm article-card overflow-hidden" style="transition: transform 0.25s ease, box-shadow 0.25s ease; border-radius: 12px;">
					<a href="{{ path('article',article.id,article.slug) }}" class="article-card-img-link d-block overflow-hidden" style="height: 200px;">
						<img src="{% if article.thumb %}{{ article.thumb }}{% else %}{{ settings.base_url }}/views/{{ settings.template }}/images/no_image.png{% endif %}" alt="{{ article.name }}" class="card-img-top object-fit-cover w-100 h-100" onerror="this.src='{{ settings.base_url }}/views/{{ settings.template }}/images/no_image.png'" loading="lazy" style="transition: transform 0.3s ease;" width="400" height="200">
					</a>
					<div class="card-body d-flex flex-column p-4">
						<div class="d-flex align-items-center gap-1 text-muted small mb-2">
							<i class="bi bi-calendar3"></i>
							<span>{{ article.date|date("d-m-Y") }}</span>
						</div>
						<h5 class="card-title fw-bold mb-3" style="line-height: 1.4;">
							<a href="{{ path('article',article.id,article.slug) }}" class="text-dark text-decoration-none article-title-link" style="transition: color 0.2s ease;">{{ article.name }}</a>
						</h5>
						<p class="card-text text-muted flex-grow-1 small lh-base" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">{{ article.content_short }}</p>
						<div class="mt-4 pt-2 border-top">
							<a href="{{ path('article',article.id,article.slug) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-medium text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">{{ 'Read more'|lang }} <i class="bi bi-arrow-right ms-1"></i></a>
						</div>
					</div>
				</div>
			</div>
		{% endfor %}
	</div>
{% else %}
	<div class="text-center py-5">
		<i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
		<h3 class="text-danger mt-3">{{ 'Nothing found'|lang }}</h3>
	</div>
{% endif %}

<style>
.article-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
.article-card:hover img {
	transform: scale(1.05);
}
.article-title-link:hover {
	color: var(--bs-primary) !important;
}
</style>
