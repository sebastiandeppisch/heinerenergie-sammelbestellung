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

const formData = reactive({
  email:"",
  password:""
});
const loading = ref(false);

auth.initLogin().then(done => {
  if(auth.loggedIn()){
    router.visit('/backend');
  }
});

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
</style>
