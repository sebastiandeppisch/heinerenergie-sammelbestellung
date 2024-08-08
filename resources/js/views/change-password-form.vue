<script>
import DxForm, {
  DxItem,
  DxLabel,
  DxButtonItem,
  DxButtonOptions,
  DxCustomRule,
  DxRequiredRule
} from 'devextreme-vue/form';
import DxLoadIndicator from 'devextreme-vue/load-indicator';
import notify from 'devextreme/ui/notify';
import { useRouter, useRoute } from 'vue-router';
import { ref, reactive } from "vue";

import auth from "../auth";

export default {
components: {
    DxForm,
    DxItem,
    DxLabel,
    DxButtonItem,
    DxButtonOptions,
    DxRequiredRule,
    DxCustomRule,
    DxLoadIndicator
  },
  setup() {
    const router = useRouter();
    const route = useRoute();

    const recoveryCode = route.query.token;
    const email = route.query.email;

    const loading = ref(false);
    const formData = reactive({
      password:""
    });

    async function onSubmit() {
      const { password , confirmedPassword } = formData;
      loading.value = true;
  
      const result = await auth.changePassword(email, password, confirmedPassword, recoveryCode);
      loading.value = false;
      if (result.isOk) {
        router.push("/login-form");
        notify('Dein Passwort wurde geändert', 'success');
      } else {
        notify(result.message, 'error', 2000);
      }
    }

    function confirmPassword (e) {
      return e.value.toString() === formData.password.toString();
    }

    return {
      loading,
      formData,
      onSubmit,
      confirmPassword
    }
  }
}
</script>

<template>
  <form @submit.prevent="onSubmit">
    <dx-form :form-data="formData" :disabled="loading">
      <dx-item
        data-field="password"
        editor-type="dxTextBox"
        :editor-options="{ stylingMode: 'filled', placeholder: 'Passwort', mode: 'password' }"
      >
        <dx-required-rule message="Gib bitte Dein neues Passwort ein" />
        <dx-label :visible="false" />
      </dx-item>
      <dx-item
        data-field="confirmedPassword"
        editor-type="dxTextBox"
        :editor-options="{ stylingMode: 'filled', placeholder: 'Passwort bestätigen', mode: 'password' }"
      >
        <dx-required-rule message="Gib bitte Dein neues Passwort ein" />
        <dx-custom-rule
          message="Die Passwort stimmen nicht überein"
          :validation-callback="confirmPassword"
        />
        <dx-label :visible="false" />
      </dx-item>
      <dx-button-item>
        <dx-button-options
          width="100%"
          type="default"
          template="changePassword"
          :use-submit-behavior="true"
        >
        </dx-button-options>
      </dx-button-item>

      <template #changePassword>
        <div>
          <span class="dx-button-text">
              <dx-loadIndicator v-if="loading" width="24px" height="24px" :visible="true" />
              <span v-if="!loading">Continue</span>
          </span>
        </div>
      </template>
    </dx-form>
  </form>
</template>

<style>

</style>
