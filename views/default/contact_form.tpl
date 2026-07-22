<form method="post" enctype="multipart/form-data" class="needs-validation">
  <input type="hidden" name="action" value="send_message">
  <input type="hidden" name="token" value="{{ generateToken('send_message') }}">
  
  <div class="mb-3">
    <label for="name" class="form-label fw-semibold">{{ 'Name'|lang }}</label>
    <input type="text" class="form-control" id="name" name="name" placeholder="{{ 'John Smith'|lang }}" required value="{{ input.name }}" title="{{ 'Enter your name'|lang }}">
  </div>

  <div class="mb-3 {% if error.email %}was-validated{% endif %}">
    <label for="email" class="form-label fw-semibold">{{ 'E-mail address'|lang }}</label>
    <input type="email" class="form-control" id="email" name="email" placeholder="{{ 'example@example.com'|lang }}" required value="{% if input.email %}{{ input.email }}{% elseif user.id %}{{ user.email }}{% endif %}" title="{{ 'Enter your email address'|lang }}" {% if user.id %}readonly{% endif %}>
    {% if error.email %}<div class="invalid-feedback d-block mt-1">{{ error.email }}</div>{% endif %}
  </div>

  <div class="mb-3">
    <label for="message" class="form-label fw-semibold">{{ 'Message'|lang }}</label>
    <textarea class="form-control" rows="5" name="message" id="message" required placeholder="{{ 'My message'|lang }}" title="{{ 'Enter your message'|lang }}">{{ input.message }}</textarea>
  </div>

  {% if settings.mail_attachment %}
    <div class="mb-3">
      <label for="attachment" class="form-label fw-semibold">{{ 'Attachment'|lang }}</label>
      <input type="file" name="attachment" id="attachment" title="{{ 'Here you can add an attachment to your message'|lang }}" class="form-control">
    </div>
  {% endif %}

  <div class="mb-4 {% if error.captcha %}was-validated{% endif %}">
    <label for="captcha" class="form-label fw-semibold">{{ 'Captcha'|lang }}</label>
    <div class="d-flex align-items-center gap-3 mb-2">
      <img src="{{ path('captcha') }}" alt="captcha" class="rounded border">
      <input type="text" class="form-control captcha-input" placeholder="abc123" title="{{ 'Enter the code Captcha'|lang }}" name="captcha" id="captcha" required maxlength="32">
    </div>
    {% if error.captcha %}<div class="invalid-feedback d-block mt-1">{{ error.captcha }}</div>{% endif %}
  </div>

  {% if not user.id %}
    <div class="form-check mb-4">
      <input class="form-check-input" type="checkbox" name="rules" id="contact_rules" required>
      <label class="form-check-label small" for="contact_rules">
        {{ 'Accepts the terms and conditions and the privacy policy'|lang }}
      </label>
      <div class="mt-1">
        <a href="{{ path('rules') }}" title="{{ 'Terms of service'|lang }}" target="_blank" class="small text-decoration-none me-2"><i class="bi bi-file-text me-1"></i>{{ 'Terms of service'|lang }}</a>
        <a href="{{ path('privacy_policy') }}" title="{{ 'Privacy policy'|lang }}" target="_blank" class="small text-decoration-none"><i class="bi bi-shield-lock me-1"></i>{{ 'Privacy policy'|lang }}</a>
      </div>
    </div>
  {% endif %}

  <button type="submit" class="btn btn-accent w-100 text-uppercase py-2 fw-bold"><i class="bi bi-send me-2"></i>{{ 'Send!'|lang }}</button>
</form>
