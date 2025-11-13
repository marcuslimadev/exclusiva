import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import Conversas from '../views/Conversas.vue'
import Leads from '../views/Leads.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/conversas',
    name: 'Conversas',
    component: Conversas,
    meta: { requiresAuth: true }
  },
  {
    path: '/leads',
    name: 'Leads',
    component: Leads,
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guard de autenticação
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.meta.requiresGuest && token) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
