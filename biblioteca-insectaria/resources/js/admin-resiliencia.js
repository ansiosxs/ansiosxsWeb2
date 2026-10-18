const storagePrefix = 'biblioteca-formulario:';

function noticeFor(form, text, action, label = 'Reintentar') {
    const notice = form.querySelector('.resilience-notice');
    notice.replaceChildren();
    notice.hidden = false;
    const message = document.createElement('p');
    message.textContent = text;
    notice.append(message);

    if (action) {
        const button = document.createElement('button');
        button.className = 'button button-secondary';
        button.type = 'button';
        button.textContent = label;
        button.addEventListener('click', action);
        notice.append(button);
    }
}

document.querySelectorAll('form[data-resiliente]').forEach((form) => {
    const key = `${storagePrefix}${form.dataset.resiliente}`;
    const saved = localStorage.getItem(key);

    function lockAndRetry(body, message, retry = () => send(body), label = 'Reintentar') {
        form.querySelectorAll('input, select, textarea, button[type="submit"]').forEach((field) => {
            field.disabled = true;
        });
        noticeFor(form, message, retry, label);
    }

    async function send(body) {
        if (!navigator.onLine) {
            lockAndRetry(body, 'No hay conexión. El formulario quedó guardado en este navegador.');
            return;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (response.status >= 500) {
                lockAndRetry(body, 'El servidor no pudo completar la operación. Los datos se conservaron; vuelve a intentar.');
                return;
            }

            const isJson = response.headers.get('content-type')?.includes('application/json');
            const result = isJson ? await response.json() : {};

            if (!response.ok) {
                if (response.status === 419) {
                    lockAndRetry(body, 'La sesión venció. Recarga esta página para continuar con el envío guardado.', () => window.location.reload(), 'Recargar');
                    return;
                }

                localStorage.removeItem(key);
                form.querySelectorAll('input, select, textarea, button[type="submit"]').forEach((field) => {
                    field.disabled = false;
                });
                noticeFor(form, Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'El servidor rechazó la operación. Revisa los datos e intenta nuevamente.');
                return;
            }

            if (!isJson || !result.redireccion) {
                lockAndRetry(body, 'El servidor respondió en un formato inesperado. Los datos se conservaron para reintentar.');
                return;
            }

            localStorage.removeItem(key);
            window.location.assign(result.redireccion);
        } catch (error) {
            lockAndRetry(body, 'Se perdió la conexión. El formulario quedó guardado en este navegador.');
        }
    }

    if (saved) {
        const body = new URLSearchParams(saved);
        const token = form.querySelector('[name="_token"]')?.value;
        if (token) {
            body.set('_token', token);
            localStorage.setItem(key, body.toString());
        }
        lockAndRetry(body, 'Hay un envío pendiente guardado en este navegador.');
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const body = new URLSearchParams(new FormData(form));
        localStorage.setItem(key, body.toString());
        send(body);
    });
});
