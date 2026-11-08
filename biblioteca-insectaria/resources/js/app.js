import './bootstrap';
import { createApp } from 'vue';
import CatalogoApp from './CatalogoApp.vue';

const target = document.getElementById('catalog-app');

if (target) {
    createApp(CatalogoApp, {
        muebles: JSON.parse(target.dataset.muebles),
        editUrlTemplate: target.dataset.editUrl,
    }).mount(target);
}
