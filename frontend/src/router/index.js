import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '@/components/pages/LoginPage.vue'
import RegisterPage from '@/components/pages/RegisterPage.vue'
import DashboardPage from '@/components/pages/DashboardPage.vue'
import UploadPage from '@/components/pages/UploadPage.vue'
import ReportPage from '@/components/pages/ReportPage.vue'
import ProfilePage from '@/components/pages/ProfilePage.vue'

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
    },
    {
      path: '/upload',
      name: 'upload',
      component: UploadPage,
    },
    {
      path: '/videos/:id/report',
      name: 'report',
      component: ReportPage,
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfilePage,
    },
  ],
})

export default router
