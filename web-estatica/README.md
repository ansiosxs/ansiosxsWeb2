# Ansiosxs – Nuevas Lecturas (sitio estático)

Versión del sitio en **HTML, CSS y JavaScript puros**: sin React, sin Vite, sin
dependencias y sin paso de build. Mantiene el diseño del sitio original
(mismos colores, tipografías, tarjetas "sticker", navegación y contenido).

## Cómo abrirlo

Basta con abrir `index.html` en el navegador (doble clic). No hace falta
servidor. Si prefieres servirlo por HTTP:

```bash
cd web-estatica
python3 -m http.server 8080
# luego abre http://localhost:8080
```

## Estructura

```
web-estatica/
├── index.html              Portada con carrusel
├── quienes-somos.html      Quiénes somos + equipo
├── proyectos.html          Proyectos, talleres, colaboraciones y logos
├── como-colaborar.html     Donaciones y formas de colaborar
├── contacto.html           Formulario (EmailJS) + datos + mapa
├── 404.html                Página de error
├── css/styles.css          Todo el sistema de diseño
├── js/
│   ├── icons.js            Sprite SVG de iconos (se inyecta en el DOM)
│   ├── main.js             Navbar, menú móvil, animaciones, toasts, copiar
│   ├── carousel.js         Carrusel del hero
│   └── contact.js          Validación y envío del formulario
├── fonts/                  CookieRun (otf)
└── images/                 Logotipos, proyectos, retratos, mascotas
```

## Personalización

- **Colores y tipografías**: variables CSS al inicio de `css/styles.css`
  (`--brand-purple`, `--brand-pink`, `--brand-yellow`, `--brand-blue`,
  `--background`, `--foreground`).
- **Formulario de contacto**: credenciales de EmailJS en `js/main.js`
  (objeto `EMAILJS`). El script de EmailJS se carga por CDN en
  `contacto.html`; la plantilla debe usar las variables `from_name`,
  `from_email`, `subject`, `message`, `newsletter`, `to_name` y `time`.
- **Textos**: cada página es HTML plano, se edita directamente.
- **Navbar y footer**: están copiados en cada HTML. Si cambias enlaces,
  actualízalos en los cinco archivos (o regenera desde la plantilla).

## Notas

- Las animaciones de entrada usan `IntersectionObserver` y respetan
  `prefers-reduced-motion`.
- El mapa de la página de contacto usa OpenStreetMap embebido (requiere
  conexión); el resto del sitio funciona sin conexión una vez guardado.
- Las imágenes en `images/` son copias de `public/images/` del proyecto
  original. El logo de la Universidad San Sebastián no existe en esa
  carpeta, por eso no aparece en la grilla de colaboradores.