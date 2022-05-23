import auth from "./auth";
import { createRouter, createWebHistory } from "vue-router";

import Orders from "./views/Orders.vue";
import Products from "./views/Products.vue";
import NewOrder from "./views/NewOrder.vue"
import Impress from "./views/static/Impress.vue"
import DataPolicy from "./views/static/DataPolicy.vue"

import defaultLayout from "./layouts/side-nav-outer-toolbar.vue";
import simpleLayout from "./layouts/single-card.vue";
import LoginForm from './views/login-form.vue'
import ResetPasswordForm from './views/reset-password-form.vue'
import ChangePasswordForm from './views/change-password-form.vue'

import { store } from "./store";

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: "/orders",
      name: "orders",
      meta: {
        requiresAuth: true,
        layout: defaultLayout
      },
      component: Orders
    },
    {
      path: "/products",
      name: "products",
      meta: {
        requiresAuth: true,
        layout: defaultLayout
      },
      component: Products
    },
    {
      path: "/neworder",
      name: "neworder",
      meta: {
        requiresAuth: false,
        layout: defaultLayout
      },
      component: NewOrder
    },
    {
      path: "/login-form",
      name: "login-form",
      meta: {
        requiresAuth: false,
        layout: simpleLayout,
        title: "Login"
      },
      component: LoginForm
    },
    {
      path: "/reset-password",
      name: "reset-password",
      meta: {
        requiresAuth: false,
        layout: simpleLayout,
        title: "Neues Passwort",
        description: "Trage hier Deine E-Mail Adresse ein, um ein neues Passwort zu erhalten"
      },
      component: ResetPasswordForm
    },
    /*{
      path: "/create-account",
      name: "create-account",
      meta: {
        requiresAuth: false,
        layout: simpleLayout,
        title: "Sign Up"
      },
      component: loadView("create-account-form"),
    },*/
    {
      path: "/change-password",
      name: "change-password",
      meta: {
        requiresAuth: false,
        layout: simpleLayout,
        title: "Neues Passwort setzen"
      },
      component: ChangePasswordForm
    },
    {
      path: "/",
      redirect: "/neworder"
    },
    {
      path: "/recovery",
      redirect: "/"
    },
    {
      path: "/:pathMatch(.*)*",
      redirect: "/"
    },
    {
      path: "/impress",
      name: "impress",
      meta: {
        requiresAuth: false,
        layout: defaultLayout
      },
      component: Impress
    },
    {
      path: "/datapolicy",
      name: "datapolicy",
      meta: {
        requiresAuth: false,
        layout: defaultLayout
      },
      component: DataPolicy
    },
  ]
});

router.beforeEach((to, from, next) => {
  if (to.name === "login-form" && auth.loggedIn()) {
    next({ path: "/" });
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
