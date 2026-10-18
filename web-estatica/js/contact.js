/** Formulario de contacto: validación + envío vía EmailJS (CDN, sin build). */
(function () {
  'use strict';

  var form = document.querySelector('#contact-form');
  if (!form) return;

  var submitBtn = form.querySelector('button[type="submit"]');
  var defaultLabel = submitBtn.innerHTML;

  function fail(title, description) {
    window.Ansiosxs.toast(title, description, 'error');
  }

  function validate(data) {
    if (!data.name.trim()) {
      fail('Error de validación', 'Por favor, ingresa tu nombre completo.');
      return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email.trim())) {
      fail('Error de validación', 'Por favor, ingresa un email válido.');
      return false;
    }
    if (!data.subject) {
      fail('Error de validación', 'Por favor, selecciona un asunto.');
      return false;
    }
    if (data.message.trim().length < 10) {
      fail('Error de validación', 'Por favor, escribe un mensaje de al menos 10 caracteres.');
      return false;
    }
    return true;
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    submitBtn.innerHTML = loading
      ? '<span class="spinner"></span> Enviando...'
      : defaultLabel;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var fields = form.elements;

    var data = {
      name: fields.name.value,
      email: fields.email.value,
      subject: fields.subject.value,
      message: fields.message.value,
      newsletter: fields.newsletter.checked ? 'Sí' : 'No'
    };

    if (!validate(data)) return;
    setLoading(true);

    var config = window.Ansiosxs.emailjsConfig;

    if (!window.emailjs) {
      setLoading(false);
      fail('No se pudo cargar el servicio de correo', 'Escríbenos directamente a ansiosxs@gmail.com.');
      return;
    }

    window.emailjs.send(config.serviceId, config.templateId, {
      from_name: data.name,
      from_email: data.email,
      subject: data.subject,
      message: data.message,
      newsletter: data.newsletter,
      to_name: 'Ansiosxs',
      time: new Date().toLocaleString('es-ES', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
      })
    }, config.publicKey)
      .then(function () {
        form.reset();
        window.Ansiosxs.toast('¡Mensaje enviado! 📧', 'Gracias por contactarnos. Te responderemos pronto.');
      })
      .catch(function () {
        fail('Error al enviar el mensaje', 'Hubo un problema al enviar tu mensaje. Intenta nuevamente o escríbenos a ansiosxs@gmail.com.');
      })
      .then(function () {
        setLoading(false);
      });
  });
})();