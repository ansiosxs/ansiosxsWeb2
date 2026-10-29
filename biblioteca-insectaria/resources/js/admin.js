import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

document.querySelectorAll('[data-book-image-form]').forEach((form) => {
    const urlInput = form.querySelector('[data-book-image-url]');
    const fileInput = form.querySelector('[data-book-image-file]');
    const preview = form.querySelector('[data-book-image-preview]');
    let previewUrl;

    const showPreview = (source) => {
        preview.hidden = !source;
        if (source) preview.src = source;
        else preview.removeAttribute('src');
    };

    const releasePreviewUrl = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = undefined;
    };

    urlInput.addEventListener('input', () => {
        fileInput.value = '';
        releasePreviewUrl();
        showPreview(urlInput.value.trim());
    });

    fileInput.addEventListener('change', () => {
        releasePreviewUrl();
        if (fileInput.files[0]) {
            previewUrl = URL.createObjectURL(fileInput.files[0]);
            showPreview(previewUrl);
        } else {
            showPreview(urlInput.value.trim());
        }
    });

    preview.addEventListener('error', () => {
        preview.hidden = true;
    });

    window.addEventListener('pagehide', releasePreviewUrl, { once: true });
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-calendar-png]');
    if (!button) return;

    const originalText = button.textContent;
    let svgUrl;
    button.disabled = true;
    button.textContent = 'Preparando imagen…';

    try {
        const response = await fetch(button.dataset.svgUrl, { headers: { Accept: 'image/svg+xml' } });
        if (!response.ok) throw new Error(`No se pudo obtener el calendario (${response.status}).`);

        const svgBlob = new Blob([await response.text()], { type: 'image/svg+xml;charset=utf-8' });
        svgUrl = URL.createObjectURL(svgBlob);
        const image = new Image();
        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = () => reject(new Error('El navegador no pudo procesar la imagen del calendario.'));
            image.src = svgUrl;
        });

        const canvas = document.createElement('canvas');
        canvas.width = image.naturalWidth * 2;
        canvas.height = image.naturalHeight * 2;
        const context = canvas.getContext('2d');
        if (!context) throw new Error('No se pudo preparar la descarga de la imagen.');
        context.scale(2, 2);
        context.drawImage(image, 0, 0);

        const pngBlob = await new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) resolve(blob);
                else reject(new Error('No se pudo generar el archivo PNG.'));
            }, 'image/png');
        });
        const downloadUrl = URL.createObjectURL(pngBlob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = button.dataset.calendarFilename;
        link.click();
        window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
    } catch (error) {
        console.error(error);
        window.alert('No se pudo descargar el calendario como PNG. Inténtalo nuevamente.');
    } finally {
        if (svgUrl) URL.revokeObjectURL(svgUrl);
        button.disabled = false;
        button.textContent = originalText;
    }
});
