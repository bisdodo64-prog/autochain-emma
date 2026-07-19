<template>
  <nav class="navbar glass-dark animate-fade-in-down">
    <div class="nav-container">
      <div class="nav-left">
        <button class="menu-toggle" @click="toggleSidebar">
          <i class="fas fa-bars"></i>
        </button>
        <router-link to="/dashboard" class="nav-brand">
          <div class="brand-icon animate-float">
            <i class="fas fa-car"></i>
          </div>
          <div class="brand-text">
            <span class="brand-name text-glow">AutoChain</span>
            <span class="brand-tag">Emma+</span>
          </div>
        </router-link>
      </div>
      
      <div class="nav-center">
        <div class="search-bar">
          <i class="fas fa-search search-icon"></i>
          <input type="text" placeholder="Rechercher..." class="search-input">
        </div>
      </div>
      
      <div class="nav-right">
        <div class="nav-actions">
          <div class="panel-wrap">
            <button class="action-btn group" title="Notifications" @click="toggleNotifications">
              <i class="fas fa-bell group-hover:animate-pulse"></i>
              <span v-if="unreadNotifs" class="notification-badge">{{ unreadNotifs }}</span>
            </button>
            <div v-if="notifOpen" class="drop-panel animate-panel">
              <div class="drop-panel-head">
                <div class="drop-panel-title">Notifications</div>
                <button type="button" class="link-btn" @click="markAllNotifs">Tout lu</button>
              </div>
              <div class="drop-scroll">
                <div
                  v-for="n in notifications"
                  :key="n.id"
                  class="drop-item"
                  :class="{ unread: !n.read }"
                  @click="markRead(n)"
                >
                  <i :class="n.icon"></i>
                  <div>
                    <strong>{{ n.title }}</strong>
                    <small>{{ n.text }}</small>
                    <em>{{ n.time }}</em>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="panel-wrap">
            <button class="action-btn group" title="Messages" @click="toggleMessages">
              <i class="fas fa-envelope group-hover:animate-pulse"></i>
              <span v-if="unreadMsgs" class="notification-badge">{{ unreadMsgs }}</span>
            </button>
            <div v-if="msgOpen" class="drop-panel animate-panel">
              <div class="drop-panel-head">
                <div class="drop-panel-title">Messages</div>
                <button type="button" class="link-btn" @click="openMessage(messages[0])">Ouvrir</button>
              </div>
              <div class="drop-scroll">
                <div
                  v-for="m in messages"
                  :key="m.id"
                  class="drop-item"
                  :class="{ unread: !m.read }"
                  @click="openMessage(m)"
                >
                  <div class="msg-avatar">{{ m.initials }}</div>
                  <div>
                    <strong>{{ m.from }}</strong>
                    <small>{{ m.text }}</small>
                    <em>{{ m.time }}</em>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="selectedMessage" class="message-modal" @click.self="selectedMessage = null">
            <div class="message-card">
              <h3>{{ selectedMessage.from }}</h3>
              <em>{{ selectedMessage.time }}</em>
              <p>{{ selectedMessage.full || selectedMessage.text }}</p>
              <button class="btn-close-msg" @click="selectedMessage = null">Fermer</button>
            </div>
          </div>
          
          <div v-if="toast.show" class="navbar-toast" :class="toast.type">
            <i :class="toast.icon"></i>
            <span>{{ toast.message }}</span>
          </div>

          <WalletConnect />

          <div class="user-menu">
            <button class="user-btn" @click="toggleUserMenu">
              <div class="user-avatar">
                <img v-if="userAvatar" :src="userAvatar" alt="" class="user-avatar-img" />
                <i v-else class="fas fa-user"></i>
              </div>
              <div class="user-info">
                <span class="user-name">{{ userName }}</span>
                <span class="user-role">{{ userRole }}</span>
              </div>
              <i class="fas fa-chevron-down dropdown-icon"></i>
            </button>
            
            <div class="dropdown-menu" :class="{ 'active': userMenuOpen }">
              <router-link to="/profile" class="dropdown-item">
                <i class="fas fa-user-circle"></i> Mon Profil
              </router-link>
              <router-link to="/settings" class="dropdown-item">
                <i class="fas fa-cog"></i> Paramètres
              </router-link>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item logout">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { fetchNotificationsHybrid, dismissAlertHybrid } from '../utils/dataService'
import WalletConnect from './WalletConnect.vue'

export default {
  name: 'Navbar',
  components: { WalletConnect },
  setup() {
    const store = useStore()
    const router = useRouter()
    const userMenuOpen = ref(false)
    const notifOpen = ref(false)
    const msgOpen = ref(false)
    const selectedMessage = ref(null)
    const notifSource = ref('local')

    const notifications = ref([])

    const loadNotifications = async () => {
      const result = await fetchNotificationsHybrid()
      notifications.value = result.data
      notifSource.value = result.source
    }

    onMounted(loadNotifications)

    const messages = ref([
      {
        id: 1,
        from: 'Garage AutoPlus',
        initials: 'GA',
        text: 'Révision Toyota Corolla planifiée demain.',
        full: 'Bonjour, la révision de la Toyota Corolla est planifiée demain à 09h30. Merci de confirmer la disponibilité du véhicule.',
        time: '08:42',
        read: false
      },
      {
        id: 2,
        from: 'Jean Dupont',
        initials: 'JD',
        text: 'Mission terminée, km mis à jour.',
        full: 'Mission Paris–Lyon terminée. Kilométrage mis à jour à 78 420 km. Aucun incident à signaler.',
        time: 'Hier',
        read: false
      },
      {
        id: 3,
        from: 'Sophie Bernard',
        initials: 'SB',
        text: 'Audit blockchain prêt à consulter.',
        full: 'L’audit de traçabilité est disponible dans la section Audit. Aucune anomalie majeure détectée.',
        time: 'Lun.',
        read: true
      }
    ])

    const unreadNotifs = computed(() => notifications.value.filter((n) => !n.read).length)
    const unreadMsgs = computed(() => messages.value.filter((m) => !m.read).length)
    
    const userName = computed(() => store.state.auth?.user?.name || 'Utilisateur')
    const userAvatar = computed(() => {
      const url = store.state.auth?.user?.avatar_url
      if (!url) return null
      if (String(url).startsWith('data:')) return url
      return `${url}${url.includes('?') ? '&' : '?'}t=${store.state.auth?.user?.updated_at || ''}`
    })
    const userRole = computed(() => {
      const roles = store.state.auth?.user?.roles || []
      const map = { super_admin: 'Super Admin', gestionnaire_parc: 'Gestionnaire', chauffeur: 'Chauffeur', garagiste_agree: 'Garagiste', auditeur: 'Auditeur' }
      return roles.length > 0 ? (map[roles[0].name] || roles[0].name) : 'Invité'
    })
    
    const toast = ref({ show: false, message: '', type: 'info', icon: 'fas fa-info-circle' })

    const showToast = (message, type = 'info') => {
      toast.value = {
        show: true,
        message,
        type,
        icon: type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-times-circle' : 'fas fa-info-circle'
      }
      setTimeout(() => (toast.value.show = false), 2400)
    }

    const toggleNotifications = async () => {
      notifOpen.value = !notifOpen.value
      msgOpen.value = false
      userMenuOpen.value = false
      if (notifOpen.value) await loadNotifications()
    }

    const toggleMessages = () => {
      msgOpen.value = !msgOpen.value
      notifOpen.value = false
      userMenuOpen.value = false
    }

    const markRead = async (n) => {
      n.read = true
      if (notifSource.value === 'api') {
        await dismissAlertHybrid(n.id)
        notifications.value = notifications.value.filter((item) => item.id !== n.id)
      }
      showToast(n.title + ' — ' + n.text, 'info')
    }

    const markAllNotifs = async () => {
      if (notifSource.value === 'api') {
        const unread = notifications.value.filter((n) => !n.read)
        await Promise.all(unread.map((n) => dismissAlertHybrid(n.id)))
        notifications.value = []
      } else {
        notifications.value.forEach((n) => { n.read = true })
      }
      showToast('Toutes les notifications sont lues.', 'success')
    }

    const openMessage = (m) => {
      if (!m) return
      m.read = true
      selectedMessage.value = m
      msgOpen.value = false
    }

    const toggleSidebar = () => {
      store.dispatch('ui/toggleSidebar')
    }
    
    const toggleUserMenu = () => {
      userMenuOpen.value = !userMenuOpen.value
      notifOpen.value = false
      msgOpen.value = false
    }
    
    const handleLogout = async () => {
      await store.dispatch('auth/logout')
      router.push('/login')
    }
    
    return { 
      userName,
      userAvatar,
      userRole,
      userMenuOpen,
      notifOpen,
      msgOpen,
      notifications,
      messages,
      unreadNotifs,
      unreadMsgs,
      selectedMessage,
      toast,
      toggleNotifications,
      toggleMessages,
      markRead,
      markAllNotifs,
      openMessage,
      toggleSidebar,
      toggleUserMenu,
      handleLogout 
    }
  }
}
</script>

<style scoped>
.navbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  height: 72px;
  border-bottom: 1px solid rgba(56, 189, 248, 0.2);
}

.navbar::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.5), transparent);
}

.nav-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  gap: 32px;
}

.navbar-toast {
  position: absolute;
  right: 140px;
  top: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 14px;
  color: white;
  font-size: 13px;
  z-index: 1200;
  background: rgba(15, 23, 42, 0.95);
  border: 1px solid rgba(56, 189, 248, 0.25);
}

.navbar-toast.info { background: linear-gradient(135deg, #38bdf8, #0ea5e9); }
.navbar-toast.success { background: linear-gradient(135deg, #10b981, #059669); }
.navbar-toast.error { background: linear-gradient(135deg, #ef4444, #dc2626); }

.nav-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.menu-toggle {
  width: 40px;
  height: 40px;
  display: none;
  align-items: center;
  justify-content: center;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 10px;
  color: #38bdf8;
  cursor: pointer;
  transition: all 0.3s;
}

.menu-toggle:hover {
  background: rgba(56, 189, 248, 0.2);
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 12px;
  transition: all 0.3s;
}

.nav-brand:hover {
  background: rgba(56, 189, 248, 0.1);
}

.brand-icon {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #38bdf8, #a855f7);
  border-radius: 12px;
  color: white;
  font-size: 18px;
  box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3);
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-name {
  font-size: 18px;
  font-weight: 700;
  color: #38bdf8;
}

.brand-tag {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.nav-center {
  flex: 1;
  max-width: 500px;
}

.search-bar {
  display: flex;
  align-items: center;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  padding: 12px 16px;
  gap: 12px;
  transition: all 0.3s;
}

.search-bar:focus-within {
  border-color: #38bdf8;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.search-icon {
  color: #94a3b8;
  font-size: 14px;
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #f8fafc;
  font-size: 14px;
  placeholder: #94a3b8;
}

.search-input::placeholder {
  color: #94a3b8;
}

.nav-right {
  display: flex;
  align-items: center;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 10px;
  color: #38bdf8;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.action-btn:hover {
  background: rgba(56, 189, 248, 0.2);
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
  transform: translateY(-2px);
}

.notification-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  font-size: 10px;
  font-weight: 700;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
  animation: pulse 2s infinite;
}

.user-menu {
  position: relative;
}

.user-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.user-btn:hover {
  background: rgba(56, 189, 248, 0.2);
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

.user-avatar {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #38bdf8, #a855f7);
  border-radius: 10px;
  color: white;
  font-size: 14px;
  overflow: hidden;
  flex-shrink: 0;
}

.user-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-info {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.user-name {
  font-size: 13px;
  font-weight: 600;
  color: #f8fafc;
}

.user-role {
  font-size: 11px;
  color: #94a3b8;
  text-transform: capitalize;
}

.dropdown-icon {
  color: #94a3b8;
  font-size: 12px;
  transition: transform 0.3s;
}

.user-btn:hover .dropdown-icon {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  min-width: 200px;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(56, 189, 248, 0.2);
  border-radius: 12px;
  padding: 8px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.dropdown-menu.active {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  color: #94a3b8;
  text-decoration: none;
  border-radius: 8px;
  font-size: 13px;
  transition: all 0.3s;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
}

.dropdown-item:hover {
  background: rgba(56, 189, 248, 0.1);
  color: #38bdf8;
  transform: translateX(4px);
}

.dropdown-item i {
  width: 16px;
  text-align: center;
}

.dropdown-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.3), transparent);
  margin: 8px 0;
}

.dropdown-item.logout {
  color: #ef4444;
}

.dropdown-item.logout:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.panel-wrap {
  position: relative;
}

.drop-panel {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 340px;
  background: #0f172a;
  border: 1px solid rgba(56, 189, 248, 0.25);
  border-radius: 14px;
  padding: 10px;
  z-index: 1200;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
}

.animate-panel {
  animation: panelIn 0.25s ease;
}

@keyframes panelIn {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

.drop-panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 8px 10px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.15);
  margin-bottom: 6px;
}

.drop-panel-title {
  font-size: 12px;
  text-transform: uppercase;
  color: #94a3b8;
}

.link-btn {
  border: none;
  background: transparent;
  color: #38bdf8;
  font-size: 11px;
  cursor: pointer;
}

.drop-scroll {
  max-height: 320px;
  overflow-y: auto;
}

.drop-item {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 10px 8px;
  border-radius: 10px;
  cursor: pointer;
  color: #e2e8f0;
}

.drop-item.unread {
  background: rgba(56, 189, 248, 0.08);
}

.drop-item:hover {
  background: rgba(56, 189, 248, 0.12);
}

.drop-item i {
  color: #38bdf8;
  margin-top: 3px;
}

.drop-item strong {
  display: block;
  font-size: 13px;
  color: #f8fafc;
}

.drop-item small {
  display: block;
  font-size: 11px;
  color: #94a3b8;
}

.drop-item em {
  display: block;
  font-size: 10px;
  color: #64748b;
  font-style: normal;
  margin-top: 2px;
}

.msg-avatar {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}

.message-modal {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 16px;
}

.message-card {
  width: min(420px, 100%);
  background: #1e293b;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid rgba(148, 163, 184, 0.2);
  color: #e2e8f0;
  animation: panelIn 0.25s ease;
}

.message-card h3 { margin: 0 0 4px; color: #f8fafc; }
.message-card em { color: #94a3b8; font-size: 12px; }
.message-card p { margin: 14px 0; line-height: 1.5; color: #cbd5e1; }
.btn-close-msg {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  background: #0ea5e9;
  color: #fff;
  cursor: pointer;
  font-weight: 600;
}

@media (max-width: 1024px) {
  .nav-center {
    display: none;
  }
}

@media (max-width: 768px) {
  .nav-container {
    padding: 0 12px;
  }

  .menu-toggle {
    display: inline-flex !important;
  }

  .user-info {
    display: none;
  }

  .brand-tag {
    display: none;
  }

  .nav-actions {
    gap: 4px;
  }
}

@media (max-width: 480px) {
  .brand-name {
    font-size: 14px;
  }
}
</style>