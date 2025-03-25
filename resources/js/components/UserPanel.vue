<script setup lang="ts">
import DxContextMenu, { DxPosition } from "devextreme-vue/context-menu";
import DxList from "devextreme-vue/list";
import { Link, router } from "@inertiajs/vue3";
import { computed } from 'vue';
import notify from 'devextreme/ui/notify';
import { route } from "ziggy-js";

interface MenuItem {
  text: string;
  icon?: string;
  disabled?: boolean;
  isHeader?: boolean;
  isCurrent?: boolean;
  isAdmin?: boolean;
  logoPath?: string;
  onClick?: () => void;
}

interface Props {
  menuMode: string;
  email?: string;
  user: App.Models.User;
  currentGroup?: App.Data.GroupData;
  availableGroups: App.Data.GroupData[];
}

const props = defineProps<Props>();

function switchGroup(groupId: string, asAdmin: boolean) {
  router.post(route('actAsGroup', groupId), {
    asAdmin: asAdmin
  });
}

function switchToSystemAdmin() {
  router.post('/actAsSystemAdmin');
}

function onProfileClick() {
  router.visit('/profile');
}

function onLogoutClick() {
  router.post('/logout');
}

const menuItems = computed<MenuItem[]>(() => {
  const items: MenuItem[] = [];

  // Add standard menu items first
  items.push({ 
    text: "Profil",
    icon: "user",
    onClick: onProfileClick
  });
  
  items.push({
    text: "Logout",
    icon: "runner",
    onClick: onLogoutClick
  });

  // Add current view section
  if (props.currentGroup) {
    items.push({
      text: 'Aktuelle Ansicht:',
      disabled: true,
      isHeader: true
    });
    items.push({
      text: props.currentGroup.name,
      icon: 'group',
      isCurrent: true,
      logoPath: props.currentGroup.logo_path || '',
      isAdmin: props.user.is_acting_as_admin
    });
  }

  // Add group switching section
  if (props.availableGroups.length > 0) {
    items.push({
      text: 'Wechseln zu:',
      disabled: true,
      isHeader: true
    });

    // Add available groups
    props.availableGroups.forEach(group => {
      // Add regular view
      items.push({
        text: ' ' + group.name,
        icon: 'group',
        logoPath: group.logo_path || '',
        isAdmin: false,
        onClick: () => switchGroup(group.id, false)
      });

      // If user is admin for this group, add admin view option
      if (group.userCanActAsAdmin) {
        items.push({
          text: group.name,
          icon: 'group',
          logoPath: group.logo_path || '',
          isAdmin: true,
          onClick: () => switchGroup(group.id, true)
        });
      }
    });
  }

  // Add system admin option if user is admin
  if (props.user.is_admin) {
    items.push({
      text: 'System Admin',
      icon: 'key',
      onClick: () => switchToSystemAdmin()
    });
  }

  return items;
});
</script>

<template>
  <div class="user-panel">
    <div v-if="email">
      <div class="user-info">
        <div class="image-container">
          <i class="dx-icon-user"></i>
        </div>
        <div class="user-name">{{user.name}} ({{email}})</div>
      </div>
      <DxContextMenu
        v-if="menuMode === 'context'"
        target=".user-button"
        :items="menuItems"
        :width="250"
        show-event="dxclick"
        css-class="user-menu"
      >
        <template #item="{ data }">
          <!-- Header Item -->
          <div v-if="data.isHeader" class="menu-group-header">
            {{ data.text }}
          </div>
          <!-- Regular Menu Item -->
          <div v-else class="menu-group-item" :class="{ current: data.isCurrent }">
            <!-- Group Logo -->
            <img v-if="data.logoPath" :src="data.logoPath" class="group-logo" />
            <!-- Icon -->
            <i v-else-if="data.icon" :class="'dx-icon-' + data.icon"></i>
            <!-- Text -->
            <span class="pl-2">{{ data.text }}</span>
            <!-- Role Badge -->
            <span v-if="data.isAdmin" class="role-badge admin">Admin</span>
          </div>
        </template>
        <DxPosition my="top center" at="bottom center" />
      </DxContextMenu>

      <DxList
        v-if="menuMode === 'list'"
        class="dx-toolbar-menu-action"
        :items="menuItems"
      >
        <template #item="{ data }">
          <!-- Header Item -->
          <div v-if="data.isHeader" class="menu-group-header">
            {{ data.text }}
          </div>
          <!-- Regular Menu Item -->
          <div v-else class="menu-group-item" :class="{ current: data.isCurrent }">
            <!-- Group Logo -->
            <img v-if="data.logoPath" :src="data.logoPath" class="group-logo" />
            <!-- Icon -->
            <i v-else-if="data.icon" :class="'dx-icon-' + data.icon"></i>
            <!-- Text -->
            <span>{{ data.text }}</span>
            <!-- Role Badge -->
            <span v-if="data.isAdmin" class="role-badge admin">Admin</span>
          </div>
        </template>
      </DxList>
    </div>
    <div v-else>
      <Link href="login-form">
        <div class="user-info">
          <div class="image-container">
            <i class="dx-icon-user"></i>
          </div>
          <div class="user-name">Berater*innen Zugang</div>
        </div>
      </Link>
    </div>
  </div>
</template>

<style lang="scss">
@import "../themes/generated/variables.base.scss";

.user-info {
  display: flex;
  align-items: center;

  .dx-toolbar-menu-section & {
    padding: 10px 6px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  }

  .user-name {
    font-size: 14px;
    color: $base-text-color;
    margin: 0 9px;
  }
}

.user-panel {
  .dx-list-item .dx-icon {
    vertical-align: middle;
    color: $base-text-color;
    margin-right: 16px;
  }
  .dx-rtl .dx-list-item .dx-icon {
    margin-right: 0;
    margin-left: 16px;
  }
}

.menu-group-header {
  padding: 8px 12px;
  color: #999;
  font-size: 0.9em;
  font-weight: 500;
}

.menu-group-item {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  
  &.current {
    background-color: rgba($base-color, 0.1);
  }

  .group-logo {
    width: 24px;
    height: 24px;
    margin-right: 8px;
    object-fit: contain;
  }

  i.dx-icon {
    margin-right: 8px;
    font-size: 18px;
  }

  .role-badge {
    margin-left: auto;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 0.8em;
    background-color: #e0e0e0;
    color: #666;

    &.admin {
      background-color: $base-color;
      color: white;
    }
  }
}

.dx-context-menu.user-menu.dx-menu-base {
  &.dx-rtl {
    .dx-submenu .dx-menu-items-container .dx-icon {
      margin-left: 16px;
    }
  }
  .dx-submenu .dx-menu-items-container .dx-icon {
    margin-right: 16px;
  }
  .dx-menu-item .dx-menu-item-content {
    padding: 3px 15px 4px;
  }
}

.dx-theme-generic .user-menu .dx-menu-item-content .dx-menu-item-text {
  padding-left: 4px;
  padding-right: 4px;
}
</style>
