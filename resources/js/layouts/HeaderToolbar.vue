<script setup lang="ts">
import DxButton from "devextreme-vue/button";
import DxToolbar, { DxItem } from "devextreme-vue/toolbar";
import { computed, watch } from 'vue';
import UserPanel from "@/components/UserPanel.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { PageProps } from "@inertiajs/core";
import genericLogo from '../../img/logo.png';
import notify from "devextreme/ui/notify";

interface CustomPageProps extends PageProps {
  auth: {
    user: App.Data.UserData;
    currentGroup?: App.Data.GroupData;
    availableGroups?: App.Data.GroupData[];
  }
  flashMessages: {
    [key: string]: string;
  };
}

const props = defineProps<{
  menuToggleEnabled: boolean;
  title?: string|undefined;
  toggleMenuFunc: Function;
}>();

const page = usePage<CustomPageProps>();
const user = computed(() => page.props.auth.user);
const currentGroup = computed(() => page.props.auth.currentGroup);
const availableGroups = computed(() => page.props.auth.availableGroups || []);

const logo = computed(() => {
  if (currentGroup.value) {
    return currentGroup.value.logo_path || genericLogo;
  }
  return genericLogo;
});

watch(() => page.props.flashMessages, (newVal) => {
  for (const key in newVal) {
    if (newVal[key]) {
      notify(newVal[key], key);
    }
  }
});

</script>

<template>
  <header class="header-component">
    <DxToolbar class="header-toolbar">
      <DxItem
        :visible="menuToggleEnabled"
        location="before"
        css-class="menu-button"
      >
        <template #default>
          <DxButton
            icon="menu"
            styling-mode="text"
            @click="toggleMenuFunc"
          />
        </template>
      </DxItem>

      <DxItem
        location="before"
      >
      <div>
        <Link href="/"><img :src="logo" style="height: 100%;width: auto;max-height: 3em;object-fit: contain;"></Link>
      </div>
      </DxItem>
      <DxItem
        location="before"
      >
       <span class="p-2" style="font-size: 1.5em;font-weight: bold;">{{ currentGroup?.name }}</span>
      </DxItem>
      <DxItem
        location="after"
        locate-in-menu="auto"
        menu-item-template="menuUserItem"
      >
        <template #default>
          <div>
            <DxButton
              class="user-button authorization"
              height="100%"
              styling-mode="text"
            >
              <UserPanel
                :email="user.email"
                :user="user"
                :current-group="currentGroup"
                :available-groups="availableGroups"
                menu-mode="context"
              />
            </DxButton>
          </div>
        </template>
      </DxItem>

      <template #menuUserItem>
        <UserPanel
          :email="user.email"
          :user="user"
          :current-group="currentGroup"
          :available-groups="availableGroups"
          menu-mode="list"
        />
      </template>
    </DxToolbar>
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
