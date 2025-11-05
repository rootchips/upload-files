import '../css/app.css'
import './bootstrap'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Upload from './components/Upload.vue'
import Products from './components/Products.vue'

const app = createApp({})
app.use(createPinia())

app.component('upload', Upload)
app.component('products', Products)

app.mount('#app')