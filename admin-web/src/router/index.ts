import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/index.vue'),
    meta: { public: true, title: '登录' },
  },
  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '仪表盘' },
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/views/users/index.vue'),
        meta: { title: '用户管理' },
      },
      {
        path: 'users/:id',
        name: 'UserDetail',
        component: () => import('@/views/users/detail.vue'),
        meta: { title: '用户详情' },
      },
      {
        path: 'clouds',
        name: 'Clouds',
        component: () => import('@/views/clouds/index.vue'),
        meta: { title: '云朵管理' },
      },
      {
        path: 'clouds/:id',
        name: 'CloudDetail',
        component: () => import('@/views/clouds/detail.vue'),
        meta: { title: '云朵详情' },
      },
      {
        path: 'cloud-types',
        name: 'CloudTypes',
        component: () => import('@/views/cloud-types/index.vue'),
        meta: { title: '云类型管理' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  document.title = to.meta.title
    ? `${String(to.meta.title)} - 云彩管理后台`
    : '云彩管理后台'

  if (to.meta.public) {
    if (auth.isLoggedIn && to.name === 'Login') {
      return { name: 'Dashboard' }
    }
    return true
  }

  if (!auth.isLoggedIn) {
    return { name: 'Login', query: { redirect: to.fullPath } }
  }

  return true
})

export default router
