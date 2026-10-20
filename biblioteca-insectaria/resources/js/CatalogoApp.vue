<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    muebles: { type: Array, required: true },
});

const query = ref('');
const shelfId = ref('');
const sortOrder = ref('autor_asc');
const page = ref(1);
const books = ref([]);
const totalPages = ref(1);
const generatedAt = ref('');
const offline = ref(false);
const loading = ref(false);
const loadingMore = ref(false);
const error = ref('');
const loadMoreError = ref('');
const loadedQuery = ref('');
const loadedShelfId = ref('');
const loadedSortOrder = ref('');
const selectedBook = ref(null);
const loadMoreTrigger = ref(null);
let debounce;
let requestId = 0;
let controller;
let observer;

const cacheKey = (searchTerm, selectedShelfId, selectedSortOrder, pageNumber) => `catalogo:${searchTerm}:${selectedShelfId}:${selectedSortOrder}:${pageNumber}`;

async function loadPage(pageNumber, append = false) {
    const searchTerm = query.value.trim();
    const selectedShelfId = shelfId.value;
    const selectedSortOrder = sortOrder.value;
    const currentRequestId = ++requestId;
    controller?.abort();
    controller = new AbortController();

    if (append) {
        loadingMore.value = true;
        loadMoreError.value = '';
    } else {
        loading.value = true;
        error.value = '';
        offline.value = false;
        page.value = 1;
        books.value = [];
        totalPages.value = 1;
        loadMoreError.value = '';
    }

    const params = new URLSearchParams({ pagina: String(pageNumber) });
    if (searchTerm) params.set('q', searchTerm);
    if (selectedShelfId) params.set('mueble_id', selectedShelfId);
    params.set('orden', selectedSortOrder);

    try {
        const response = await fetch(`/api/catalogo?${params}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        if (!response.ok) throw new Error('No se pudo consultar el catálogo.');
        const data = await response.json();
        if (currentRequestId !== requestId) return;
        books.value = append ? [...books.value, ...data.datos] : data.datos;
        page.value = data.pagina;
        totalPages.value = data.total_paginas;
        generatedAt.value = data.generado_en;
        loadedQuery.value = searchTerm;
        loadedShelfId.value = selectedShelfId;
        loadedSortOrder.value = selectedSortOrder;
        offline.value = false;
        localStorage.setItem(cacheKey(searchTerm, selectedShelfId, selectedSortOrder, pageNumber), JSON.stringify(data));
        localStorage.setItem('catalogo:ultima-respuesta', JSON.stringify({ data, at: data.generado_en }));
    } catch (cause) {
        if (cause.name === 'AbortError' || currentRequestId !== requestId) return;
        const cached = localStorage.getItem(cacheKey(searchTerm, selectedShelfId, selectedSortOrder, pageNumber));
        if (cached) {
            const data = JSON.parse(cached);
            books.value = append ? [...books.value, ...data.datos] : data.datos;
            page.value = data.pagina;
            totalPages.value = data.total_paginas;
            generatedAt.value = data.generado_en;
            loadedQuery.value = searchTerm;
            loadedShelfId.value = selectedShelfId;
            loadedSortOrder.value = selectedSortOrder;
            offline.value = true;
        } else if (append) {
            loadMoreError.value = 'No fue posible cargar más libros. Intenta nuevamente.';
        } else {
            error.value = 'No fue posible cargar el catálogo. Revisa tu conexión e intenta otra vez.';
        }
    } finally {
        if (currentRequestId === requestId) {
            loading.value = false;
            loadingMore.value = false;
        }
    }
}

function search() {
    clearTimeout(debounce);
    loadPage(1);
}

function loadNextPage() {
    if (loading.value || loadingMore.value || loadedQuery.value !== query.value.trim() || loadedShelfId.value !== shelfId.value || loadedSortOrder.value !== sortOrder.value || page.value >= totalPages.value) return;
    loadPage(page.value + 1, true);
}

watch([query, shelfId, sortOrder], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => loadPage(1), 250);
});

watch(loadMoreTrigger, (element, previousElement) => {
    if (previousElement) observer?.unobserve(previousElement);
    if (element) observer?.observe(element);
});

onMounted(() => {
    loadPage(1);
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) loadNextPage();
        }, { rootMargin: '300px 0px' });
        if (loadMoreTrigger.value) observer.observe(loadMoreTrigger.value);
    }
});

onBeforeUnmount(() => {
    clearTimeout(debounce);
    controller?.abort();
    observer?.disconnect();
});

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
        <label for="catalog-query">Título, autor o ISBN</label>
        <div class="search-control"><input id="catalog-query" v-model="query" type="search" placeholder="Ej.: narrativa gráfica o 978..."><button class="button" type="button" @click="search">Buscar</button></div>
        <label for="catalog-shelf">Filtrar por mueble</label>
        <select id="catalog-shelf" v-model="shelfId">
            <option value="">Todos los muebles</option>
            <option v-for="mueble in props.muebles" :key="mueble.id" :value="String(mueble.id)">{{ mueble.nombre }}</option>
        </select>
        <label for="catalog-sort">Ordenar por</label>
        <select id="catalog-sort" v-model="sortOrder">
            <option value="titulo_asc">Título: A–Z</option>
            <option value="titulo_desc">Título: Z–A</option>
            <option value="autor_asc">Autor: A–Z</option>
            <option value="autor_desc">Autor: Z–A</option>
        </select>
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
    <div v-if="books.length && page < totalPages" class="catalog-load-more">
        <div ref="loadMoreTrigger" class="catalog-load-trigger" aria-hidden="true"></div>
        <p v-if="loadingMore" class="catalog-message" role="status">Cargando más libros…</p>
        <p v-if="loadMoreError" class="notice notice-error" role="alert">{{ loadMoreError }}</p>
        <button class="button button-secondary" type="button" :disabled="loadingMore" @click="loadNextPage">
            {{ loadingMore ? 'Cargando…' : loadMoreError ? 'Intentar nuevamente' : 'Cargar más libros' }}
        </button>
    </div>
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
