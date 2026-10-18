function validarRun(value) {
    const run = value.replace(/[.\-\s]/g, '').toUpperCase();

    if (!/^\d{7,8}[0-9K]$/.test(run)) return false;

    const cuerpo = run.slice(0, -1).split('').reverse();
    const suma = cuerpo.reduce((total, digit, index) => total + Number(digit) * ((index % 6) + 2), 0);
    const resto = suma % 11;
    const verificador = resto === 0 ? '0' : resto === 1 ? 'K' : String(11 - resto);

    return run.at(-1) === verificador;
}

document.querySelectorAll('[data-run-validation]').forEach((form) => {
    const input = form.querySelector('[data-run-input]');
    const mensaje = form.querySelector('[data-run-mensaje]');

    input.addEventListener('input', () => {
        if (!input.value.trim()) {
            mensaje.textContent = '';
            input.removeAttribute('aria-invalid');
            return;
        }

        const valid = validarRun(input.value);
        mensaje.textContent = valid ? 'RUN válido.' : 'RUN incompleto o inválido.';
        mensaje.className = valid ? 'text-sm font-bold text-green-700' : 'field-error';
        input.setAttribute('aria-invalid', String(!valid));
    });
});

document.querySelectorAll('[data-libros-url]').forEach((form) => {
    const busqueda = form.querySelector('[data-libro-busqueda]');
    const resultados = form.querySelector('[data-libro-resultados]');
    const estado = form.querySelector('[data-libro-estado]');
    const libroId = form.querySelector('[data-libro-id]');
    const cantidad = form.querySelector('[name="cantidad"]');
    const campos = Object.fromEntries(
        [...form.querySelectorAll('[data-libro-campo]')].map((field) => [field.dataset.libroCampo, field]),
    );
    let temporizador;
    let controlador;

    function completar(libro) {
        libroId.value = libro.id;
        Object.entries(campos).forEach(([nombre, field]) => {
            field.value = libro[nombre] ?? '';
        });
        cantidad.max = String(libro.cantidad_disponible);
        cantidad.value = String(Math.min(Math.max(1, Number(cantidad.value) || 1), libro.cantidad_disponible));
        resultados.hidden = true;
        resultados.replaceChildren();
        estado.textContent = `${libro.titulo}: ${libro.cantidad_disponible} unidad(es) disponible(s).`;
    }

    function limpiarResultados() {
        resultados.hidden = true;
        resultados.replaceChildren();
        libroId.value = '';
        Object.values(campos).forEach((field) => { field.value = ''; });
        estado.textContent = '';
    }

    async function buscar(valor) {
        const termino = valor.trim();
        controlador?.abort();

        if (!termino) {
            limpiarResultados();
            return;
        }

        controlador = new AbortController();
        estado.textContent = 'Buscando libros disponibles…';

        try {
            const url = new URL(form.dataset.librosUrl, window.location.origin);
            url.searchParams.set('q', termino);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controlador.signal,
            });

            if (!response.ok) throw new Error('No se pudo consultar el inventario disponible.');

            const libros = await response.json();
            resultados.replaceChildren();

            if (libros.length === 0) {
                resultados.hidden = true;
                estado.textContent = 'No se encontraron libros con unidades disponibles.';
                return;
            }

            libros.forEach((libro) => {
                const opcion = document.createElement('button');
                opcion.type = 'button';
                opcion.className = 'button button-secondary justify-start text-left';
                opcion.setAttribute('role', 'option');
                opcion.textContent = `${libro.titulo} — ${libro.autor} · ${libro.cantidad_disponible} disponible(s)`;
                opcion.addEventListener('click', () => completar(libro));
                resultados.append(opcion);
            });
            resultados.hidden = false;
            estado.textContent = `Selecciona uno de los ${libros.length} libros con disponibilidad.`;
        } catch (error) {
            if (error.name === 'AbortError') return;
            limpiarResultados();
            estado.textContent = error.message;
        }
    }

    function programarBusqueda(input) {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => buscar(input.value), 220);
    }

    busqueda.addEventListener('input', () => {
        limpiarResultados();
        programarBusqueda(busqueda);
    });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});
