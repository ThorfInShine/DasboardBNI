import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // PUBLIC ROUTES (tanpa login)
    {
      path: '/auth/login',
      name: 'login',
      component: () => import('@/views/pages/auth/Login.vue'),
    },
    {
      path: '/auth/regist',
      name: 'register',
      component: () => import('@/views/pages/auth/Regist.vue'),
    },
    {
      path: '/landing',
      name: 'landing',
      component: () => import('@/views/pages/Landing.vue'),
    },
    // PROTECTED ROUTES (HARUS LOGIN DULU!)
    {
      path: '/',
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '/',
          name: 'dashboard',
          component: () => import('@/views/Dashboard.vue'),
        },
        {
          path: '/pages/empty',
          name: 'empty',
          component: () => import('@/views/pages/Empty.vue'),
        },
        {
          path: '/pages/crud',
          name: 'crud',
          component: () => import('@/views/pages/Crud.vue'),
        },
        {
          path: '/documentation',
          name: 'documentation',
          component: () => import('@/views/pages/Documentation.vue'),
        },
        {
          path: '/profile',
          name: 'profile',
          component: () => import('@/views/pages/Profile.vue'),
        },
        {
          path: '/data-management',
          name: 'dataManagement',
          component: () => import('@/views/pages/DataManagement.vue'),
        },
        {
          path: '/data-import',
          name: 'dataImport',
          component: () => import('@/views/pages/DataImport.vue'),
        },
        {
          path: '/data-input',
          name: 'dataInput',
          component: () => import('@/views/pages/DataInput.vue'),
        },
        {
          path: '/user-management',
          name: 'userManagement',
          meta: { requiresAdmin: true },
          component: () => import('@/views/pages/UserManagement.vue'),
        },
      ],
    },
    // ERROR PAGES
    {
      path: '/pages/notfound',
      name: 'notfound',
      component: () => import('@/views/pages/NotFound.vue'),
    },
    {
      path: '/auth/access',
      name: 'accessDenied',
      component: () => import('@/views/pages/auth/Access.vue'),
    },
    {
      path: '/forbidden',
      name: 'forbidden',
      component: () => import('@/views/pages/Forbidden.vue'),
    },
    {
      path: '/auth/error',
      name: 'error',
      component: () => import('@/views/pages/auth/Error.vue'),
    },
    // CATCH ALL
    {
      path: '/:pathMatch(.*)*',
      redirect: '/pages/notfound',
    },
  ],
});

router.beforeEach((to, from, next) => {
  const { isAuthenticated, isAdmin } = useAuth();

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    next('/auth/login');
  } else if (to.path === '/auth/login' && isAuthenticated.value) {
    next('/');
  } else if (to.meta.requiresAdmin && !isAdmin.value) {
    next('/forbidden');
  } else {
    next();
  }
});

export default router;