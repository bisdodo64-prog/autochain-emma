import { createApp } from 'vue' 
import App from './components/App.vue' 
import router from './router' 
import store from './store' 
 
const app = createApp(App) 
app.use(store) 
app.use(router) 
store.dispatch('auth/initializeAuth')
store.dispatch('blockchain/checkConnection') 
app.mount('#app') 
