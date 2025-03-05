<script setup lang="ts">
import DxLoadIndicator from "devextreme-vue/load-indicator";
import DxForm, {
  DxItem,
  DxEmailRule,
  DxRequiredRule,
  DxLabel,
  DxButtonItem,
  DxButtonOptions
} from "devextreme-vue/form";
import notify from 'devextreme/ui/notify';
import auth from "../auth";
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3'
import { Link } from "@inertiajs/vue3";
import SingleCard from "../layouts/SingleCard.vue";
import MainPublic from "../layouts/MainPublic.vue";
import { defineOptions } from "vue";

interface User{
  id: number
  name: string
  email: string
}

const props = defineProps<{
  devUsers?: User[]
}>();

const formData = reactive({
  email:"",
  password:""
});
const loading = ref(false);

async function onSubmit() {
  const { email, password } = formData;
  loading.value = true;
  const result = await auth.logIn(email, password);
  if (!result.isOk) {
    loading.value = false;
    notify(result.message, "error", 2000);
  } else {
    router.visit('/backend');
  }
}

defineOptions({
  layout: MainPublic
})

</script>

<template>
<SingleCard title="Login" description="für Berater:innen">
  <form class="login-form" @submit.prevent="onSubmit">
    <dx-form :form-data="formData" :disabled="loading">
      <dx-item
        data-field="email"
        editor-type="dxTextBox"
        :editor-options="{ stylingMode: 'filled', placeholder: 'Email', mode: 'email', validationMessageMode: 'always'}"
      >
        <dx-required-rule message="Gib bitte Deine E-Mail Adresse ein" />
        <dx-email-rule message="Gib bitte eine gültige E-Mail Adresse ein" />
        <dx-label :visible="false" />
      </dx-item>
      <dx-item
        data-field='password'
        editor-type='dxTextBox'
        :editor-options="{ stylingMode: 'filled', placeholder: 'Password', mode: 'password', validationMessageMode: 'always' }"
      >
        <dx-required-rule message="Gib bitte Dein Passwort ein" />
        <dx-label :visible="false" />
      </dx-item>
      <dx-item
        data-field="rememberMe"
        editor-type="dxCheckBox"
        :editor-options="{ text: 'Eingeloggt bleiben', elementAttr: { class: 'form-text' } }"
      >
        <dx-label :visible="false" />
      </dx-item>
      <dx-button-item>
        <dx-button-options
          width="100%"
          type="default"
          template="signInTemplate"
          :use-submit-behavior="true"
        >
        </dx-button-options>
      </dx-button-item>
      <dx-item>
        <template #default>
          <div class="link">
            <Link href="/reset-password">Passwort vergessen</Link>
          </div>
        </template>
      </dx-item>
      <template #signInTemplate>
        <div>
          <span class="dx-button-text">
            <dx-load-indicator v-if="loading" width="24px" height="24px" :visible="true" />
            <span v-if="!loading">Login</span>
          </span>
        </div>
      </template>
    </dx-form>
  </form>
  
  <div v-if="props.devUsers && props.devUsers.length > 0" class="dev-login-section">
    <h3 class="text-lg font-medium text-gray-600 mb-4">Direkter Login</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="user in props.devUsers" :key="user.id" class="dev-user-item">
        <Link :href="`/dev-login/${user.id}`" class="dev-user-link" :title="user.email">
          {{ user.name }}
        </Link>
      </div>
    </div>
  </div>
</SingleCard>
</template>

<style lang="scss">
@import "../themes/generated/variables.base.scss";

.login-form {
  .link {
    text-align: center;
    font-size: 16px;
    font-style: normal;

    a {
      text-decoration: none;
    }
  }

  .form-text {
    margin: 10px 0;
    color: rgba($base-text-color, alpha($base-text-color) * 0.7);
  }
}

.dev-login-section {
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.dev-user-link {
  @apply block p-3 text-sm rounded border border-gray-300 transition-colors hover:bg-gray-100 truncate w-full text-center;
}
</style>
