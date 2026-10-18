<script setup>
import { onMounted, ref, watch } from 'vue';

const query = ref('');
const page = ref(1);
const books = ref([]);
const totalPages = ref(1);
const generatedAt = ref('');
const offline = ref(false);
const loading = ref(false);
const error = ref('');
const selectedBook = ref(null);
let debounce;

const cacheKey = () => `catalogo:${query.value.trim()}:${page.value}`;

async function search() {
    loading.value = true;
    error.value = '';
    offline.value = false;
    const params = new URLSearchParams({ pagina: String(page.value) });
    if (query.value.trim()) params.set('q', query.value.trim());

    try {
        const response = await fetch(`/api/catalogo?${params}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('No se pudo consultar el catálogo.');
        const data = await response.json();
        books.value = data.datos;
        totalPages.value = data.total_paginas;
        generatedAt.value = data.generado_en;
        localStorage.setItem(cacheKey(), JSON.stringify(data));
        localStorage.setItem('catalogo:ultima-respuesta', JSON.stringify({ data, at: data.generado_en }));
    } catch (cause) {
        const cached = localStorage.getItem(cacheKey()) || localStorage.getItem('catalogo:ultima-respuesta');
        if (cached) {
            const result = JSON.parse(cached);
            const data = result.data || result;
            books.value = data.datos;
            totalPages.value = data.total_paginas;
            generatedAt.value = result.at || data.generado_en;
            offline.value = true;
        } else {
            error.value = 'No fue posible cargar el catálogo. Revisa tu conexión e intenta otra vez.';
        }
    } finally {
        loading.value = false;
    }
}

watch(query, () => {
    page.value = 1;
    clearTimeout(debounce);
    debounce = setTimeout(search, 250);
});

watch(page, search);
onMounted(search);

async function showBook(book) {
    try {
        const response = await fetch(`/api/catalogo/${book.id}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('No fue posible cargar el libro.');
        selectedBook.value = await response.json();
    } catch (cause) {
        selectedBook.value = book;
    }
}

function closeBook() {
    selectedBook.value = null;
}
</script>

<template>
    <section class="catalog-search">
        <label for="catalog-query">Título o autor</label>
        <div class="search-control"><input id="catalog-query" v-model="query" type="search" placeholder="Ej.: narrativa gráfica"><button class="button" type="button" @click="search">Buscar</button></div>
    </section>
    <p v-if="loading" class="catalog-message">Buscando libros…</p>
    <p v-if="error" class="notice notice-error" role="alert">{{ error }}</p>
    <p v-if="offline && generatedAt" class="notice notice-offline" role="status">Sin conexión. Datos al {{ new Date(generatedAt).toLocaleString('es-CL') }}.</p>
    <p v-else-if="generatedAt" class="catalog-updated">Datos actualizados: {{ new Date(generatedAt).toLocaleString('es-CL') }}</p>
    <p v-if="!loading && !error && books.length === 0" class="catalog-message">No encontramos libros con esa búsqueda.</p>
    <section v-if="books.length" class="book-grid" aria-label="Resultados del catálogo">
        <article v-for="book in books" :key="book.id" class="book-card">
            <img v-if="book.portada_url" :src="book.portada_url" :alt="`Portada de ${book.titulo}`" loading="lazy">
            <div v-else class="book-cover-placeholder" aria-hidden="true">-</div>
            <div class="book-info"><h2>{{ book.titulo }}</h2><p>{{ book.autor }}</p>
                <span :class="['availability', book.disponible ? 'available' : 'unavailable']">{{ book.cantidad_disponible }} disponible(s)</span>
                <p v-if="book.mueble" class="book-location">Ubicación: {{ book.mueble }}</p>
                <button class="text-button" type="button" @click="showBook(book)">Ver detalles</button>
            </div>
        </article>
    </section>
    <nav v-if="totalPages > 1" class="pagination" aria-label="Páginas del catálogo">
        <button class="button button-secondary" :disabled="page <= 1" @click="page--">Anterior</button>
        <span>Página {{ page }} de {{ totalPages }}</span>
        <button class="button button-secondary" :disabled="page >= totalPages" @click="page++">Siguiente</button>
    </nav>
    <div v-if="selectedBook" class="modal-backdrop" role="presentation" @click.self="closeBook">
        <section class="book-modal" role="dialog" aria-modal="true" :aria-label="selectedBook.titulo">
            <button class="modal-close" type="button" aria-label="Cerrar" @click="closeBook">×</button>
            <img v-if="selectedBook.portada_url" :src="selectedBook.portada_url" :alt="`Portada de ${selectedBook.titulo}`">
            <h2>{{ selectedBook.titulo }}</h2><p>Autor: {{ selectedBook.autor }}</p>
            <p v-if="selectedBook.editorial">Editorial: {{ selectedBook.editorial }}</p><p v-if="selectedBook.anio">Año: {{ selectedBook.anio }}</p>
            <p v-if="selectedBook.mueble">Ubicación: {{ selectedBook.mueble }}</p>
            <p>{{ selectedBook.cantidad_disponible }} unidades disponibles.</p>
        </section>
    </div>
</template>
