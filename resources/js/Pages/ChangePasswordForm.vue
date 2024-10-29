<script lang="ts" setup>
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
import { ref, reactive } from "vue";
import auth from "../auth";
import MainPublic from '../layouts/MainPublic.vue';
import { router } from "@inertiajs/vue3";
import SingleCard from '../layouts/SingleCard.vue';

interface Props{
  token: string;
  email: string;
}

const props = defineProps<Props>();
const recoveryCode = props.token;
const email = props.email;

const loading = ref(false);
const formData = reactive({
  password: "",
  confirmedPassword: ""
});

async function onSubmit() {
  const { password , confirmedPassword } = formData;
  loading.value = true;

  const result = await auth.changePassword(email, password, confirmedPassword, recoveryCode);
  loading.value = false;
  if (result.isOk) {
    router.get('login-form');
    notify('Dein Passwort wurde geändert', 'success');
  } else {
    notify(result.message, 'error', 2000);
  }
}

function confirmPassword (e) {
  return e.value.toString() === formData.password.toString();
}

defineOptions({
  layout: MainPublic
})
</script>

<template>
<SingleCard title="Neues Passwort setzen">
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
</SingleCard>
</template>

<style>

</style>
