# Ansiosxs – Nuevas Lecturas (sitio estático)

Versión del sitio en **HTML, CSS y JavaScript puros**: sin framework ni
dependencias de ejecución. Node.js 20 se usa para generar las partes compartidas
y ejecutar comprobaciones locales antes de publicar.

## Desarrollo y publicación

El menú y el pie de página se mantienen en `partials/nav.html` y
`partials/footer.html`. Después de editar esas plantillas, regenera las páginas
estáticas y verifica que estén sincronizadas:

```bash
node scripts/build-static.mjs
node scripts/build-static.mjs --check
node scripts/check-accessibility.mjs
```

El generador actualiza las seis páginas que comparten navegación y pie de
página. `404.html` y `enlaces.html` conservan intencionalmente sus diseños
independientes. Los archivos HTML de la raíz son el resultado que se publica;
edita las plantillas para cambiar componentes compartidos y vuelve a generar.

Para una vista rápida, basta abrir `index.html` en el navegador. Si prefieres
probar el sitio por HTTP:

```bash
cd web-estatica
python3 -m http.server 8080
# luego abre http://localhost:8080
```

Las páginas de inicio, noticias y enlaces cargan sus datos desde la API
Laravel. La portada muestra las tres noticias más recientes. En desarrollo
local, inicia también `biblioteca-insectaria` en el puerto `8001`;
el sitio detecta `localhost` y consulta esa API automáticamente. En producción
usan la URL pública configurada en sus respectivos archivos HTML.
El acceso **Catálogo** de la navegación apunta actualmente a
`http://127.0.0.1:8001/`. Cuando se publique la biblioteca, actualiza el enlace
en `partials/nav.html` a `https://biblioteca.ansiosxs.cl/`.

La página `enlaces.html` está pensada para compartir desde redes sociales. Sus
botones y su orden se administran desde **Administración → Enlaces públicos**
en Laravel; solo se muestran los enlaces que estén activos.

La portada incorpora el perfil público de Instagram mediante su incrustación
oficial. El contenido depende de que Instagram permita la carga del perfil y de
que el visitante tenga acceso a él; el enlace directo permanece disponible si
la incrustación no carga.

## Estructura

```
web-estatica/
├── index.html              Portada con carrusel
├── quienes-somos.html      Quiénes somos + equipo
├── proyectos.html          Proyectos, talleres, colaboraciones y logos
├── como-colaborar.html     Donaciones y formas de colaborar
├── contacto.html           Formulario (EmailJS) + datos + mapa
├── enlaces.html            Página pública de enlaces para redes sociales
├── 404.html                Página de error
├── css/styles.css          Todo el sistema de diseño
├── partials/               Plantillas compartidas de navegación y pie de página
├── scripts/
│   ├── build-static.mjs    Genera las partes compartidas en las páginas HTML
│   └── check-accessibility.mjs Comprobaciones automáticas de estructura accesible
├── js/
│   ├── icons.js            Sprite SVG de iconos (se inyecta en el DOM)
│   ├── main.js             Navbar, menú móvil, animaciones, toasts, copiar
│   ├── carousel.js         Carrusel del hero
│   ├── home-news.js        Últimas noticias en la portada
│   ├── enlaces.js          Carga de enlaces públicos desde Laravel
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
- **Navbar y footer**: edita `partials/nav.html` o `partials/footer.html` y
  ejecuta `node scripts/build-static.mjs` para regenerar las páginas.

## Accesibilidad y revisión móvil

El sitio incluye enlace para saltar al contenido, landmarks principales,
indicador de foco visible, menú móvil con estado accesible y cierre con Escape,
etiquetas asociadas a los campos y soporte para movimiento reducido. Antes de
publicar, ejecuta las comprobaciones automáticas indicadas arriba y revisa
manualmente:

1. Recorre cada página usando solo Tab, Shift+Tab, Enter, Espacio y Escape;
   confirma que se ve el foco y que el enlace para saltar al contenido funciona.
2. En `contacto.html`, envía el formulario vacío y con datos inválidos; verifica
   que el foco vaya al primer campo que necesita corrección.
3. Prueba el menú, el submenú de Proyectos y el cierre con Escape en móvil y
   escritorio, además de ampliar el navegador al 200 %.
4. Comprueba anchos de 320, 375 y 768 px sin desplazamiento horizontal, y activa
   la preferencia del sistema para reducir movimiento.

## Notas

- Las animaciones de entrada usan `IntersectionObserver` y respetan
  `prefers-reduced-motion`.
- El mapa de la página de contacto usa OpenStreetMap embebido (requiere
  conexión); el resto del sitio funciona sin conexión una vez guardado.
- Las imágenes en `images/` son copias de `public/images/` del proyecto
  original. El logo de la Universidad San Sebastián no existe en esa
  carpeta, por eso no aparece en la grilla de colaboradores.