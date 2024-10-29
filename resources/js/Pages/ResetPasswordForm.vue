<script lang="ts" setup>
import DxForm, {
  DxItem,
  DxLabel,
  DxButtonItem,
  DxButtonOptions,
  DxRequiredRule,
  DxEmailRule
} from 'devextreme-vue/form';
import DxLoadIndicator from 'devextreme-vue/load-indicator';
import notify from 'devextreme/ui/notify';
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

import auth from "../auth";
import { Link } from '@inertiajs/vue3';
import MainPublic from '../layouts/MainPublic.vue';
import SingleCard from '../layouts/SingleCard.vue';
const notificationText = 'Du hast eine E-Mail mit einem Passwort-Reset Link erhalten';


const loading = ref(false);
const formData = reactive({
  email:""
});

async function onSubmit() {
  const { email } = formData;
  loading.value = true;

  const result = await auth.resetPassword(email);
  loading.value = false;

  if (result.isOk) {
    router.get('login-form');
    notify(notificationText, "success", 2500);
  } else {
    notify(result.message, "error", 2000);
  }
}

defineOptions({
  layout: MainPublic
});


</script>

<template>
<SingleCard title="Neues Passwort" description="Trage hier Deine E-Mail Adresse ein, um ein neues Passwort zu erhalten">
  <form class="reset-password-form" @submit.prevent="onSubmit">
    <dx-form :form-data="formData" :disabled="loading">
      <dx-item
        data-field="email"
        editor-type="dxTextBox"
        :editor-options="{ stylingMode: 'filled', placeholder: 'E-Mail', mode: 'email' }"
      >
        <dx-required-rule message="Du musst eine E-Mail Adresse eintragan" />
        <dx-email-rule message="Die E-Mail Adresse ist ungültig" />
        <dx-label :visible="false" />
      </dx-item>
      <dx-button-item>
        <dx-button-options
          :element-attr="{ class: 'submit-button' }"
          width="100%"
          type="default"
          template="resetTemplate"
          :use-submit-behavior="true"
        >
        </dx-button-options>
      </dx-button-item>
      <dx-item>
        <template #default>
          <div class="login-link">
            <Link href="/login-form">Zurück zum Login</Link>
          </div>
        </template>
      </dx-item>
      <template #resetTemplate>
        <div>
          <span class="dx-button-text">
              <dx-load-indicator v-if="loading" width="24px" height="24px" :visible="true" />
              <span v-if="!loading">Passwort zurücksetzen</span>
          </span>
        </div>
      </template>
    </dx-form>
  </form>
</SingleCard>
</template>

<style lang="scss">
@import "../themes/generated/variables.base.scss";

.reset-password-form {
  .submit-button {
    margin-top: 10px;
  }

  .login-link {
    text-align: center;
    font-size: 16px;
    font-style: normal;

    a {
      text-decoration: none;
    }
  }
}
</style>
