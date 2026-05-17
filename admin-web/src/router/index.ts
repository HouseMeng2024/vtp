import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '../layouts/AdminLayout.vue'
import { useAuthStore } from '../stores/auth'
import type { AdminMenu } from '../types/auth'
import { finishProgress, startProgress } from '../utils/progress'

const viewModules = import.meta.glob('../views/**/*.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/login/LoginView.vue'),
      meta: { title: '登录', public: true },
    },
    {
      path: '/',
      name: 'admin',
      component: AdminLayout,
      redirect: '/dashboard',
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('../views/dashboard/DashboardView.vue'),
          meta: { title: '控制台' },
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('../views/profile/ProfileView.vue'),
          meta: { title: '个人中心' },
        },
        {
          path: '403',
          name: 'error.403',
          component: () => import('../views/error/ErrorView.vue'),
          meta: { title: '无权限', status: 403 },
        },
        {
          path: '404',
          name: 'error.404',
          component: () => import('../views/error/ErrorView.vue'),
          meta: { title: '页面不存在', status: 404 },
        },
        {
          path: ':pathMatch(.*)*',
          name: 'admin.fallback',
          component: () => import('../views/error/ErrorView.vue'),
          meta: { title: '页面不存在', status: 404 },
        },
      ],
    },
  ],
})

function addMenuRoutes(menus: AdminMenu[]) {
  for (const menu of flattenMenus(menus)) {
    if (menu.type !== 2 || !menu.path || !menu.component) {
      continue
    }

    const routePath = menu.path.startsWith('/') ? menu.path : `/${menu.path}`
    const routeName = routePath.replace(/^\//, '').replace(/\//g, '.')

    if (router.hasRoute(routeName)) {
      continue
    }

    router.addRoute('admin', {
      path: routePath,
      name: routeName,
      component: resolveView(menu.component),
      meta: {
        title: menu.title,
        permission: menu.permission,
      },
    })
  }
}

function flattenMenus(menus: AdminMenu[]): AdminMenu[] {
  return menus.flatMap((menu) => [menu, ...flattenMenus(menu.children || [])])
}

function resolveView(component: string) {
  const normalized = component.replace(/^\/+/, '').replace(/\.vue$/, '')
  const candidates = [
    `../views/${normalized}.vue`,
    `../views/${normalized}/index.vue`,
  ]

  for (const path of candidates) {
    if (viewModules[path]) {
      return viewModules[path]
    }
  }

  return () => import('../views/dashboard/DashboardView.vue')
}

router.beforeEach(async (to) => {
  startProgress()
  const authStore = useAuthStore()

  if (to.meta.public) {
    return authStore.isLoggedIn ? '/dashboard' : true
  }

  if (!authStore.isLoggedIn) {
    return {
      path: '/login',
      query: { redirect: to.fullPath },
    }
  }

  if (!authStore.user) {
    await authStore.fetchProfile()
  }

  if (authStore.menus.length === 0) {
    await authStore.fetchMenus()
    addMenuRoutes(authStore.menus)

    const resolved = router.resolve(to.fullPath)

    if (to.name === 'admin.fallback' && resolved.name !== 'admin.fallback') {
      return to.fullPath
    }

    if (resolved.name === 'admin.fallback' && !['/403', '/404'].includes(to.path)) {
      return '/404'
    }
  } else {
    addMenuRoutes(authStore.menus)

    if (to.name === 'admin.fallback' && router.resolve(to.fullPath).name !== 'admin.fallback') {
      return to.fullPath
    }

    if (to.name === 'admin.fallback' && !['/403', '/404'].includes(to.path)) {
      return '/404'
    }
  }

  return true
})

router.afterEach(() => {
  finishProgress()
})

router.onError(() => {
  finishProgress()
})

export default router
