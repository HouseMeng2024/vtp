import { createApp } from 'vue'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/dark/css-vars.css'
import './style.css'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'
import { setupPermissionDirective } from './directives/permission'
import { i18n, setI18nLocale, getStoredLocale } from './plugins/i18n'

const app = createApp(App)

setI18nLocale(getStoredLocale())
app.use(createPinia())
app.use(router)
app.use(ElementPlus)
app.use(i18n)
setupPermissionDirective(app)
app.mount('#app')
