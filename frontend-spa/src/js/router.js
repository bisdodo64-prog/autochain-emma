import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'Login', component: () => import('../views/Login.vue') },
  { path: '/dashboard', name: 'Dashboard', component: () => import('./components/Dashboard.vue'), meta: { requiresAuth: true } },
  { path: '/vehicles', name: 'Vehicles', component: () => import('./components/Vehicles.vue'), meta: { requiresAuth: true } },
  { path: '/vehicle/:id', name: 'VehicleDetail', component: () => import('./components/VehicleDetail.vue'), meta: { requiresAuth: true } },
  { path: '/vehicle/:id/timeline', name: 'VehicleTimeline', component: () => import('./components/VehicleTimeline.vue'), meta: { requiresAuth: true }, props: true },
  { path: '/maintenance', name: 'Maintenance', component: () => import('../views/Maintenance.vue'), meta: { requiresAuth: true } },
  { path: '/drivers', name: 'Drivers', component: () => import('../views/Drivers.vue'), meta: { requiresAuth: true } },
  { path: '/documents', name: 'Documents', component: () => import('../views/Documents.vue'), meta: { requiresAuth: true } },
  { path: '/blockchain', name: 'Blockchain', component: () => import('../views/Blockchain.vue'), meta: { requiresAuth: true } },
  { path: '/missions', name: 'Missions', component: () => import('../views/Missions.vue'), meta: { requiresAuth: true } },
  { path: '/admin', name: 'Admin', component: () => import('../views/Admin.vue'), meta: { requiresAuth: true } },
  { path: '/audit', name: 'Audit', component: () => import('../views/Audit.vue'), meta: { requiresAuth: true } },
  { path: '/profile', name: 'Profile', component: () => import('../views/Profile.vue'), meta: { requiresAuth: true } },
  { path: '/settings', name: 'Settings', component: () => import('./components/Settings.vue'), meta: { requiresAuth: true } },
  { path: '/role/:role', name: 'RoleWorkspace', component: () => import('./components/Dashboard.vue'), meta: { requiresAuth: true } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.path === '/login' && token) {
    next('/dashboard')
  } else if (to.name === 'RoleWorkspace') {
    next({ path: '/dashboard', query: { role: to.params.role } })
  } else {
    next()
  }
})

export default router
