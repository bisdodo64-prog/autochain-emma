<template>
  <div id="app" :class="{ 'authenticated': isAuthenticated }">
    <Navbar v-if="isAuthenticated" />
    <div class="app-layout" v-if="isAuthenticated">
      <div
        class="sidebar-overlay"
        :class="{ open: sidebarOpen }"
        @click="closeSidebar"
      ></div>
      <Sidebar />
      <main class="main-content">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
    <div v-else class="auth-layout">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useStore } from 'vuex'
import Navbar from './Navbar.vue'
import Sidebar from './Sidebar.vue'

export default {
  name: 'App',
  components: { Navbar, Sidebar },
  setup() {
    const store = useStore()
    const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])
    const sidebarOpen = computed(() => store.getters['ui/sidebarOpen'])
    const closeSidebar = () => store.dispatch('ui/closeSidebar')

    const onKeydown = (e) => {
      if (e.key === 'Escape') closeSidebar()
    }

    watch(sidebarOpen, (open) => {
      if (typeof document === 'undefined') return
      document.body.style.overflow = open && window.innerWidth <= 768 ? 'hidden' : ''
    })

    onMounted(() => window.addEventListener('keydown', onKeydown))
    onUnmounted(() => {
      window.removeEventListener('keydown', onKeydown)
      document.body.style.overflow = ''
    })

    return { isAuthenticated, sidebarOpen, closeSidebar }
  }
}
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
#app { min-height: 100vh; }
.app-layout { display: flex; position: relative; }
.main-content {
  flex: 1;
  padding: 32px;
  min-height: calc(100vh - 72px);
  overflow-x: hidden;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  min-width: 0;
}
.auth-layout { min-height: 100vh; }

.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.65);
  z-index: 1100;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 1024px) {
  .main-content { padding: 24px 20px; }
}

@media (max-width: 768px) {
  .main-content { padding: 16px 12px; min-height: calc(100vh - 64px); }
  .sidebar-overlay.open { display: block; }
}

@media (max-width: 480px) {
  .main-content { padding: 12px 10px; }
}

/* Mode clair (Parametres > Mode sombre desactive) */
body.theme-light,
html.theme-light {
  background: #e2e8f0;
  color: #0f172a;
}
body.theme-light .main-content {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}
</style>
