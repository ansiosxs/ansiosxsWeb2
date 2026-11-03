import { readFile, readdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const pages = new Map([
  ['index.html', 'home'],
  ['quienes-somos.html', 'about'],
  ['proyectos.html', 'projects'],
  ['noticias.html', 'news'],
  ['como-colaborar.html', 'collaborate'],
  ['contacto.html', 'contact'],
]);
const skipLink = '<a class="skip-link" href="#main-content">Saltar al contenido principal</a>';
const [navTemplate, footerTemplate] = await Promise.all([
  readFile(path.join(root, 'partials', 'nav.html'), 'utf8'),
  readFile(path.join(root, 'partials', 'footer.html'), 'utf8'),
]);
const checkOnly = process.argv.includes('--check');
const errors = [];

function renderNav(activePage) {
  return navTemplate
    .replace('{{homeClass}}', activePage === 'home' ? 'nav--home' : '')
    .replace(/ data-nav-key="([^"]+)"/g, (_match, key) => (
      key === activePage ? ' class="is-active" aria-current="page"' : ''
    ))
    .trimEnd();
}

for (const name of await readdir(root)) {
  if (!name.endsWith('.html')) continue;
  const file = path.join(root, name);
  const original = await readFile(file, 'utf8');
  let rendered = original;
  const activePage = pages.get(name);

  if (activePage) {
    const navMatches = rendered.match(/<nav\b[\s\S]*?<\/nav>/gi) || [];
    const footerMatches = rendered.match(/<footer\b[\s\S]*?<\/footer>/gi) || [];
    if (navMatches.length !== 1 || footerMatches.length !== 1) {
      errors.push(`${name}: se esperaba exactamente un menú y un pie de página.`);
      continue;
    }

    rendered = rendered.replace(/<nav\b[\s\S]*?<\/nav>/i, renderNav(activePage));
    rendered = rendered.replace(/<footer\b[\s\S]*?<\/footer>/i, footerTemplate.trimEnd());
  }

  if (!rendered.includes('class="skip-link"')) {
    rendered = rendered.replace(/(<body\b[^>]*>)/i, `$1\n  ${skipLink}`);
  }

  const mainMatches = rendered.match(/<main\b[^>]*>/gi) || [];
  if (mainMatches.length !== 1) {
    errors.push(`${name}: se esperaba exactamente un elemento <main>.`);
    continue;
  }
  rendered = rendered.replace(/<main\b([^>]*)>/i, (_match, attributes) => {
    const withMainId = /\bid\s*=/.test(attributes)
      ? attributes.replace(/\bid=(["']).*?\1/i, 'id="main-content"')
      : ` id="main-content"${attributes}`;
    const focusable = /\btabindex\s*=/.test(withMainId) ? withMainId : `${withMainId} tabindex="-1"`;
    return `<main${focusable}>`;
  });

  if (rendered !== original) {
    if (checkOnly) {
      errors.push(`${name}: HTML desactualizado; ejecuta node scripts/build-static.mjs.`);
    } else {
      await writeFile(file, rendered, 'utf8');
      console.log(`Actualizado ${name}`);
    }
  }
}

if (errors.length) {
  console.error(errors.join('\n'));
  process.exitCode = 1;
} else if (checkOnly) {
  console.log('Las plantillas compartidas y los landmarks HTML están sincronizados.');
}
