import './bootstrap';
import { createApp } from 'vue';
import CatalogoApp from './CatalogoApp.vue';

const target = document.getElementById('catalog-app');

if (target) {
    createApp(CatalogoApp).mount(target);
}
