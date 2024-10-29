import auth from "./auth";
import { createRouter, createWebHistory } from "vue-router";

import Advices from "./views/Advices.vue";
import Products from "./views/Products.vue";
import Users from "./views/Users.vue";
import NewOrder from './Pages/NewOrder.vue'
import Impress from "./views/Impress.vue"
import DataPolicy from "./views/DataPolicy.vue"

import defaultLayout from "./layouts/SideNavOuterToolbar.vue";
import simpleLayout from "./layouts/single-card.vue";
import PublicLayout from './layouts/PublicLayout.vue';
import LoginForm from './views/login-form.vue'
import ResetPasswordForm from './views/reset-password-form.vue'
import ChangePasswordForm from './views/change-password-form.vue'
import Settings from './views/Settings.vue'
import NewAdvice from "./views/NewAdvice.vue";
const router = createRouter({
  history: createWebHistory(),
  routes: [
  
    {
      path: "/sammelbestellung",
      name: "sammelbestellung",
      meta: {
        requiresAuth: false,
        layout: PublicLayout
      },
      component: NewOrder
    },
    {
      path: "/neworder",
      name: "neworder",
      meta: {
        requiresAuth: true,
        layout: defaultLayout
      },
      component: NewOrder
    },
    {
      path: "/",
      redirect: "/sammelbestellung"
    },
    {
      path: "/recovery",
      redirect: "/"
    },
    {
      path: "/:pathMatch(.*)*",
      redirect: "/"
    }
  ]
});

router.beforeEach((to, from, next) => {
  if (to.name === "login-form" && auth.loggedIn()) {
    next({ path: "/backend" });
  }else  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!auth.loggedIn()) {
      next({
        name: "login-form",
        query: { redirect: to.fullPath }
      });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;
