import { readFile, readdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const failures = [];

function textContent(html) {
  return html
    .replace(/<svg\b[\s\S]*?<\/svg>/gi, '')
    .replace(/<[^>]*>/g, '')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .trim();
}

for (const name of (await readdir(root)).filter((file) => file.endsWith('.html'))) {
  const html = await readFile(path.join(root, name), 'utf8');
  const fail = (message) => failures.push(`${name}: ${message}`);

  if (!/<html\b[^>]*\blang=["'][^"']+["']/i.test(html)) fail('falta el idioma del documento.');
  if (!/<title>\s*[^<]+\s*<\/title>/i.test(html)) fail('falta un título de página.');
  if (!/<main\b[^>]*\bid=["']main-content["'][^>]*\btabindex=["']-1["']/i.test(html)) {
    fail('falta el landmark principal enfocable para el enlace de salto.');
  }
  if (!/<a\b[^>]*\bhref=["']#main-content["'][^>]*>\s*Saltar al contenido principal\s*<\/a>/i.test(html)) {
    fail('falta el enlace para saltar al contenido principal.');
  }

  const ids = [...html.matchAll(/\bid=["']([^"']+)["']/gi)].map((match) => match[1]);
  if (new Set(ids).size !== ids.length) fail('hay valores id duplicados.');

  for (const match of html.matchAll(/<img\b([^>]*)>/gi)) {
    if (!/\balt\s*=/.test(match[1])) fail('hay una imagen sin atributo alt.');
  }

  for (const match of html.matchAll(/<button\b([^>]*)>([\s\S]*?)<\/button>/gi)) {
    const attributes = match[1];
    if (!/\baria-label\s*=|\btitle\s*=/.test(attributes) && !textContent(match[2])) {
      fail('hay un botón sin nombre accesible.');
    }
  }

  for (const match of html.matchAll(/<a\b([^>]*)>([\s\S]*?)<\/a>/gi)) {
    const attributes = match[1];
    const hasName = /\baria-label\s*=|\baria-labelledby\s*=|\btitle\s*=/.test(attributes)
      || Boolean(textContent(match[2]))
      || /<img\b[^>]*\balt=["'][^"']+["']/i.test(match[2]);
    if (!hasName) fail('hay un enlace sin nombre accesible.');
  }

  for (const match of html.matchAll(/<(input|select|textarea)\b([^>]*)>/gi)) {
    const attributes = match[2];
    if (/\btype=["'](?:hidden|submit|button|reset)["']/i.test(attributes)) continue;
    if (/\baria-label\s*=|\baria-labelledby\s*=/.test(attributes)) continue;

    const id = attributes.match(/\bid=["']([^"']+)["']/i)?.[1];
    const hasLabel = id && new RegExp(`<label\\b[^>]*\\bfor=["']${id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}["']`, 'i').test(html);
    if (!hasLabel) fail(`el control ${id ? `#${id}` : '<' + match[1] + '>'} no tiene etiqueta asociada.`);
  }
}

if (failures.length) {
  console.error(failures.join('\n'));
  process.exitCode = 1;
} else {
  console.log('Comprobaciones HTML de accesibilidad superadas en todas las páginas.');
}
