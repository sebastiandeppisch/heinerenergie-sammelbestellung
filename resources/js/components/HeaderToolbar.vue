<script setup lang="ts">
import DxButton from "devextreme-vue/button";
import DxToolbar, { DxItem } from "devextreme-vue/toolbar";
import auth from "../auth";
import { computed } from 'vue';
import { useStore } from '../store'

import UserPanel from "./UserPanel.vue";
import notify from 'devextreme/ui/notify';
import axios from "axios";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps<{
  menuToggleEnabled: boolean;
  title: string;
  toggleMenuFunc: Function;
  logOutFunc: Function;
}>();

const store = useStore();

const email = computed(() => {
  return store.getters.email;
})

const isLoggedIn = computed(() => {
  return store.getters.isLoggedIn;
}); 
const userMenuItems = computed(() => {
  const items = [{
    text: "Profil",
    icon: "user",
    onClick: onProfileClick
  },
  {
    text: "Logout",
    icon: "runner",
    onClick: onLogoutClick
  }];
  if(!store.getters.canActAsAdmin){
    return items;
  }
  if(store.getters.user.is_admin){
    items.push( {
      text: 'Berater*innen Ansicht',
      icon: 'key',
      onClick: () => {
        axios.post('/api/stopActAsAdmin').then(response => {
          notify('Du bist jetzt als Berater*in angemeldet', 'success', 2000);
          store.commit('actAsNonAdmin')
          router.reload()
        }).catch(error => {
          notify('Es ist ein Fehler aufgetreten', 'error', 2000);
        })
      }
    });
  }else{
    items.push( {
      text: 'Admin Ansicht',
      icon: 'key',
      onClick: () => {
        axios.post('/api/actAsAdmin').then(response => {
          notify('Du bist jetzt als Admin angemeldet', 'success', 2000);
          store.commit('actAsAdmin')
          router.reload()
        }).catch(error => {
          notify('Du bist kein Admin', 'error', 2000);
        })
      }
    });
  }
  return items;
});
  
function onLogoutClick() {
  auth.logOut().then(response => {
    router.visit('/login-form');
  })
  
}

function onProfileClick() {
  router.visit('/profile');
}
</script>

<template>
  <header class="header-component">
    <dx-toolbar class="header-toolbar">
      <dx-item
        v-if="isLoggedIn"
        :visible="menuToggleEnabled"
        location="before"
        css-class="menu-button"
      >
        <template #default>
          <dx-button
            icon="menu"
            styling-mode="text"
            @click="toggleMenuFunc"
          />
        </template>
      </dx-item>

      <dx-item
        v-if="title"
        location="before"
        css-class="header-title dx-toolbar-label"
      >
        <div><Link href="/"><img src="img/logo.png" style="height:2em;"></Link></div>
      </dx-item>

      <dx-item
        location="after"
        locate-in-menu="auto"
        menu-item-template="menuUserItem"
      >
      <template #default>
          <div>
            <dx-button
              class="user-button authorization"
              :width="210"
              height="100%"
              styling-mode="text"
            >
              <user-panel :email="email" :menu-items="userMenuItems" menu-mode="context" />
            </dx-button>
          </div>
        </template>
      </dx-item>
      
      <template #menuUserItem>
        <user-panel
          :email="email"
          :menu-items="userMenuItems"
          menu-mode="list"
        />
      </template>
    </dx-toolbar>
  </header>
</template>

<style lang="scss">
@import "../themes/generated/variables.base.scss";
@import "../dx-styles.scss";

.header-component {
  flex: 0 0 auto;
  z-index: 1;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);

  .dx-toolbar .dx-toolbar-item.menu-button > .dx-toolbar-item-content .dx-icon {
    color: $base-accent;
  }
}

.dx-toolbar.header-toolbar .dx-toolbar-items-container .dx-toolbar-after {
  padding: 0 40px;

  .screen-x-small & {
    padding: 0 20px;
  }
}

.dx-toolbar .dx-toolbar-item.dx-toolbar-button.menu-button {
  width: $side-panel-min-width;
  text-align: center;
  padding: 0;
}

.header-title .dx-item-content {
  padding: 0;
  margin: 0;
}

.dx-theme-generic {
  .dx-toolbar {
    padding: 10px 0;
  }

  .user-button > .dx-button-content {
    padding: 3px;
  }
}
</style>
