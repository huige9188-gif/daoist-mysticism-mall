import { createRouter, createWebHistory } from 'vue-router'
import store from '@/store'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    redirect: '/dashboard',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue')
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/views/Users.vue')
      },
      {
        path: 'categories',
        name: 'Categories',
        component: () => import('@/views/Categories.vue')
      },
      {
        path: 'products',
        name: 'Products',
        component: () => import('@/views/Products.vue')
      },
      {
        path: 'orders',
        name: 'Orders',
        component: () => import('@/views/Orders.vue')
      },
      {
        path: 'videos',
        name: 'Videos',
        component: () => import('@/views/Videos.vue')
      },
      {
        path: 'articles',
        name: 'Articles',
        component: () => import('@/views/Articles.vue')
      },
      {
        path: 'feng-shui-masters',
        name: 'FengShuiMasters',
        component: () => import('@/views/FengShuiMasters.vue')
      },
      {
        path: 'payment-config',
        name: 'PaymentConfig',
        component: () => import('@/views/PaymentConfig.vue')
      },
      {
        path: 'chat',
        name: 'Chat',
        component: () => import('@/views/Chat.vue')
      }
    ]
  },
  {
    path: '/shop',
    component: () => import('@/layouts/FrontLayout.vue'),
    meta: { requiresAuth: false },
    children: [
      {
        path: '',
        name: 'ProductList',
        component: () => import('@/views/shop/ProductList.vue')
      },
      {
        path: 'cart',
        name: 'ShopCart',
        component: () => import('@/views/shop/Cart.vue')
      },
      {
        path: 'videos',
        name: 'ShopVideos',
        component: () => import('@/views/shop/VideoList.vue')
      },
      {
        path: 'articles',
        name: 'ShopArticles',
        component: () => import('@/views/shop/ArticleList.vue')
      },
      {
        path: 'masters',
        name: 'ShopMasters',
        component: () => import('@/views/shop/MasterList.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const isAuthenticated = store.getters['user/isAuthenticated']

  if (requiresAuth && !isAuthenticated) {
    next('/login')
  } else if (to.path === '/login' && isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router
