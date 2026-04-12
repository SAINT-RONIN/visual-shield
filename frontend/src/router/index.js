import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
import LoginPage from '@/components/pages/LoginPage.vue'
import RegisterPage from '@/components/pages/RegisterPage.vue'
import DashboardPage from '@/components/pages/DashboardPage.vue'
import UploadPage from '@/components/pages/UploadPage.vue'
import ReportPage from '@/components/pages/ReportPage.vue'
import ProfilePage from '@/components/pages/ProfilePage.vue'
import AdminPage from '@/components/pages/AdminPage.vue'
import PrivacyPolicyPage from '@/components/pages/PrivacyPolicyPage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginPage,
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterPage,
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/upload',
      name: 'upload',
      component: UploadPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/videos/:id/report',
      name: 'report',
      component: ReportPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfilePage,
      meta: { requiresAuth: true },
    },
    {
      path: '/admin',
      name: 'admin',
      component: AdminPage,
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/privacy',
      name: 'privacy',
      component: PrivacyPolicyPage,
    },
  ],
})

router.beforeEach(async (to) => {
  const { isLoggedIn, isAdmin, hydrateAuthUser } = useAuth()

  if (isLoggedIn.value) {
    try {
      await hydrateAuthUser()
    } catch {
      return { name: 'login' }
    }
  }

  if (to.meta.requiresAuth && !isLoggedIn.value) {
    return { name: 'login' }
  }

  if (to.meta.requiresAdmin && !isAdmin.value) {
    return { name: 'dashboard' }
  }

  if ((to.name === 'login' || to.name === 'register') && isLoggedIn.value) {
    return { name: 'dashboard' }
  }
})

export default router
