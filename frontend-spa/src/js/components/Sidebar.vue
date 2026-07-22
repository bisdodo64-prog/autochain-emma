<template>
  <aside class="sidebar glass-dark" :class="{ open: sidebarOpen }">
    <div class="sidebar-mobile-bar">
      <span class="sidebar-mobile-title">Menu</span>
      <button
        type="button"
        class="sidebar-close"
        @click.stop.prevent="closeSidebar"
        @touchend.stop.prevent="closeSidebar"
        aria-label="Fermer le menu"
      >
        <span class="sidebar-close-x" aria-hidden="true">×</span>
        <span class="sidebar-close-label">Fermer</span>
      </button>
    </div>
    <div class="sidebar-header">
      <div class="logo-container">
        <div class="logo-icon animate-float">
          <i class="fas fa-car text-2xl text-glow"></i>
        </div>
        <div class="logo-text">
          <span class="logo-title text-glow">AutoChain</span>
          <span class="logo-subtitle">Emma+</span>
        </div>
      </div>
    </div>

    <div class="sidebar-account">
      <div class="account-row">
        <div class="account-avatar">
          <img
            v-if="userAvatar && !avatarFailed"
            :src="userAvatar"
            alt=""
            @error="avatarFailed = true"
          />
          <span v-else>{{ userInitials }}</span>
        </div>
        <div class="account-info">
          <span class="account-label">Compte</span>
          <span class="account-name">{{ userName }}</span>
          <span class="account-email">{{ userEmail }}</span>
        </div>
      </div>
      <div class="account-meta">
        <span v-for="role in userRoles" :key="role.name" class="account-role">{{ roleLabel(role.name) }}</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <router-link to="/dashboard" class="sidebar-item group">
        <div class="item-icon"><i class="fas fa-th-large group-hover:animate-pulse"></i></div>
        <span>Dashboard</span>
        <div class="item-indicator"></div>
      </router-link>

      <template v-if="showAdminMenu">
        <router-link to="/vehicles" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-car-side group-hover:animate-pulse"></i></div>
          <span>Véhicules</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link to="/maintenance" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-tools group-hover:animate-pulse"></i></div>
          <span>Maintenance</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link to="/drivers" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-users group-hover:animate-pulse"></i></div>
          <span>Chauffeurs</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link to="/documents" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-file-alt group-hover:animate-pulse"></i></div>
          <span>Documents</span>
          <div class="item-indicator"></div>
        </router-link>
        <div class="sidebar-divider"></div>
        <router-link to="/blockchain" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-cubes group-hover:animate-pulse"></i></div>
          <span>Blockchain</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link to="/audit" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-search group-hover:animate-pulse"></i></div>
          <span>Audit</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link to="/admin" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-users-cog group-hover:animate-pulse"></i></div>
          <span>Admin</span>
          <div class="item-indicator"></div>
        </router-link>
      </template>

      <template v-else>
        <router-link v-if="isGestionnaire || isGaragiste || isChauffeur" to="/vehicles" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-car-side"></i></div>
          <span>Véhicules</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link v-if="isGestionnaire || isGaragiste || isChauffeur" to="/maintenance" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-tools"></i></div>
          <span>Maintenance</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link v-if="isGestionnaire" to="/drivers" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-users"></i></div>
          <span>Chauffeurs</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link v-if="isGestionnaire || isAuditeur" to="/documents" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-file-alt"></i></div>
          <span>Documents</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link v-if="isAuditeur || isGaragiste || isChauffeur" to="/blockchain" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-cubes"></i></div>
          <span>Blockchain</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link v-if="isAuditeur" to="/audit" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-search"></i></div>
          <span>Audit</span>
          <div class="item-indicator"></div>
        </router-link>
        <router-link v-if="isChauffeur" to="/missions" class="sidebar-item group">
          <div class="item-icon"><i class="fas fa-route"></i></div>
          <span>Missions</span>
          <div class="item-indicator"></div>
        </router-link>
      </template>

      <div v-if="canSwitchRoles" class="role-switch-block">
        <div class="sidebar-role-title">Rôles Super Admin</div>
        <button
          v-for="link in roleLinks"
          :key="link.role"
          type="button"
          class="sidebar-item role-btn"
          :class="{ active: currentRole === link.role }"
          @click="enterRole(link.role)"
        >
          <div class="item-icon"><i :class="link.icon"></i></div>
          <span>{{ link.label }}</span>
          <div class="item-indicator"></div>
        </button>
        <button
          v-if="!isSuperAdmin"
          type="button"
          class="sidebar-item role-btn back-admin"
          @click="enterRole('super_admin')"
        >
          <div class="item-icon"><i class="fas fa-user-shield"></i></div>
          <span>Retour Super Admin</span>
          <div class="item-indicator"></div>
        </button>
      </div>

      <div class="sidebar-divider"></div>

      <router-link to="/profile" class="sidebar-item group">
        <div class="item-icon"><i class="fas fa-user-circle group-hover:animate-pulse"></i></div>
        <span>Profil</span>
        <div class="item-indicator"></div>
      </router-link>
    </nav>

    <div class="sidebar-footer">
      <div class="wallet-status">
        <div class="wallet-indicator" :class="{ connected: walletConnected }"></div>
        <span class="wallet-text">{{ walletConnected ? 'Wallet Connecté' : 'Wallet Non Connecté' }}</span>
      </div>
    </div>
  </aside>
</template>

<script>
import { computed, ref, watch } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import { getInitials, resolveAvatarUrl } from '../utils/avatarUrl'

export default {
  name: 'Sidebar',
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()

    const user = computed(() => store.state.auth?.user || {})
    const userRoles = computed(() => store.state.auth?.user?.roles || [])
    const userName = computed(() => user.value.name || 'Utilisateur')
    const userEmail = computed(() => user.value.email || '')
    const avatarFailed = ref(false)
    const userAvatar = computed(() =>
      resolveAvatarUrl(user.value.avatar_url, user.value.updated_at || '')
    )
    watch(userAvatar, () => { avatarFailed.value = false })
    const userInitials = computed(() => getInitials(userName.value))
    const sidebarOpen = computed(() => store.getters['ui/sidebarOpen'])
    const closeSidebar = (e) => {
      if (e) {
        e.preventDefault()
        e.stopPropagation()
      }
      store.commit('ui/SET_SIDEBAR_OPEN', false)
    }

    watch(() => route.fullPath, () => {
      if (window.innerWidth <= 768) closeSidebar()
    })

    const canSwitchRoles = computed(() => store.getters['auth/canSwitchRoles'])
    const currentRole = computed(() => userRoles.value[0]?.name || '')
    const isSuperAdmin = computed(() => currentRole.value === 'super_admin')
    const isGestionnaire = computed(() => currentRole.value === 'gestionnaire_parc')
    const isChauffeur = computed(() => currentRole.value === 'chauffeur')
    const isGaragiste = computed(() => currentRole.value === 'garagiste_agree')
    const isAuditeur = computed(() => currentRole.value === 'auditeur')
    const showAdminMenu = computed(() => isSuperAdmin.value || canSwitchRoles.value)

    const roleLabel = (roleName) => {
      const map = {
        super_admin: 'Super Admin',
        gestionnaire_parc: 'Gestionnaire',
        chauffeur: 'Chauffeur',
        garagiste_agree: 'Garagiste',
        auditeur: 'Auditeur'
      }
      return map[roleName] || roleName
    }

    const roleLinks = [
      { label: 'Gestionnaire', icon: 'fas fa-user-tie', role: 'gestionnaire_parc' },
      { label: 'Garagiste', icon: 'fas fa-tools', role: 'garagiste_agree' },
      { label: 'Chauffeur', icon: 'fas fa-car-side', role: 'chauffeur' },
      { label: 'Auditeur', icon: 'fas fa-search', role: 'auditeur' }
    ]

    const enterRole = async (role) => {
      await store.dispatch('auth/switchRole', role)
      await router.push('/dashboard')
    }

    const walletConnected = computed(() => !!store.state.auth?.user?.wallet_address)

    return {
      userName,
      userEmail,
      userAvatar,
      userInitials,
      avatarFailed,
      userRoles,
      canSwitchRoles,
      currentRole,
      isSuperAdmin,
      isGestionnaire,
      isChauffeur,
      isGaragiste,
      isAuditeur,
      showAdminMenu,
      roleLabel,
      roleLinks,
      enterRole,
      walletConnected,
      sidebarOpen,
      closeSidebar
    }
  }
}
</script>

<style scoped>
.sidebar {
  width: 280px;
  min-height: calc(100vh - 64px);
  padding: 24px 16px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  z-index: 100;
}

.sidebar-mobile-bar {
  display: none;
}

.sidebar-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: none;
  border-radius: 10px;
  background: rgba(56, 189, 248, 0.18);
  color: #f8fafc;
  cursor: pointer;
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
}

.sidebar-close-x {
  font-size: 22px;
  line-height: 1;
  font-weight: 700;
}

.sidebar-close-label {
  line-height: 1;
}

.account-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.account-avatar {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
  background: linear-gradient(135deg, #0ea5e9, #6366f1);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
}

.account-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.sidebar-header { margin-bottom: 16px; }

.sidebar-account {
  margin-bottom: 20px;
  padding: 14px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(56, 189, 248, 0.15);
  border-radius: 14px;
  animation: fadeSlide 0.45s ease;
}

.account-info { display: flex; flex-direction: column; gap: 4px; margin-bottom: 0; flex: 1; min-width: 0; }
.account-label { font-size: 10px; text-transform: uppercase; color: #60a5fa; letter-spacing: 0.1em; }
.account-name { font-size: 14px; font-weight: 700; color: #f8fafc; }
.account-email { font-size: 11px; color: #94a3b8; word-break: break-all; }
.account-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.account-role {
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(56, 189, 248, 0.15);
  color: #7dd3fc;
}

.logo-container { display: flex; align-items: center; gap: 12px; }
.logo-icon {
  width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #38bdf8, #0ea5e9); border-radius: 12px; color: white;
}
.logo-title { font-size: 18px; font-weight: 700; color: #38bdf8; }
.logo-subtitle { font-size: 12px; color: #94a3b8; font-weight: 500; }
.logo-text { display: flex; flex-direction: column; }

.sidebar-nav { display: flex; flex-direction: column; gap: 4px; flex: 1; }

.sidebar-item {
  display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 12px;
  color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 500;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;
  border: none; background: transparent; width: 100%; cursor: pointer; text-align: left;
}

.sidebar-item::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
  background: #38bdf8; transform: scaleY(0); transition: transform 0.3s;
}

.sidebar-item:hover {
  background: rgba(56, 189, 248, 0.1); color: #38bdf8; transform: translateX(4px);
}
.sidebar-item:hover::before { transform: scaleY(1); }

.sidebar-item.router-link-active,
.sidebar-item.active {
  background: rgba(56, 189, 248, 0.16); color: #38bdf8; font-weight: 600;
}
.sidebar-item.router-link-active::before,
.sidebar-item.active::before { transform: scaleY(1); }

.item-icon {
  width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
  background: rgba(56, 189, 248, 0.1); border-radius: 10px; transition: all 0.3s;
}
.sidebar-item:hover .item-icon {
  background: rgba(56, 189, 248, 0.2); box-shadow: 0 0 16px rgba(56, 189, 248, 0.25);
}

.item-indicator {
  margin-left: auto; width: 6px; height: 6px; border-radius: 50%; background: #38bdf8;
  opacity: 0; transform: scale(0); transition: all 0.3s;
}
.sidebar-item.router-link-active .item-indicator,
.sidebar-item.active .item-indicator { opacity: 1; transform: scale(1); box-shadow: 0 0 10px #38bdf8; }

/* Sans boîte colorée — comme l’image */
.role-switch-block { margin-top: 8px; }
.sidebar-role-title {
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin: 8px 4px 6px;
}

.role-btn.back-admin { margin-top: 4px; color: #94a3b8; }

.sidebar-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.3), transparent);
  margin: 12px 0;
}

.sidebar-footer {
  margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(148, 163, 184, 0.2);
}
.wallet-status {
  display: flex; align-items: center; gap: 12px; padding: 12px 16px;
  background: rgba(15, 23, 42, 0.5); border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.2);
}
.wallet-indicator { width: 10px; height: 10px; border-radius: 50%; background: #ef4444; animation: pulse 2s infinite; }
.wallet-indicator.connected { background: #10b981; }
.wallet-text { font-size: 12px; color: #94a3b8; font-weight: 500; }

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.45; }
}
@keyframes fadeSlide {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 1024px) { .sidebar { width: 240px; } }

@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: min(300px, 88vw);
    min-height: 100vh;
    min-height: 100dvh;
    z-index: 1200;
    padding-top: 12px;
    /* Important: pas d'animation CSS globale qui override ce transform */
    animation: none !important;
    transform: translate3d(-105%, 0, 0);
    transition: transform 0.25s ease;
    box-shadow: 8px 0 32px rgba(0,0,0,.45);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    pointer-events: auto;
  }
  .sidebar.open {
    transform: translate3d(0, 0, 0);
  }
  .sidebar-mobile-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 0 12px;
    padding: 0 4px 10px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    position: sticky;
    top: 0;
    z-index: 2;
    background: #0f172a;
  }
  .sidebar-mobile-title {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #94a3b8;
  }
  .sidebar-close {
    position: relative;
    z-index: 3;
    min-height: 44px;
    min-width: 88px;
    pointer-events: auto;
  }
}
</style>
