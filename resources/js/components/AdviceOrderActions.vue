<script setup lang="ts">
import DxButton from 'devextreme-vue/button';
import notify from 'devextreme/ui/notify';
import { computed } from 'vue';

const props = defineProps<{
  advice: App.Models.Advice
  advisor?: {
    email: string;
    first_name: string;
  };
}>();

const orderLink = computed(() => {
  const data =  {
    advisorEmail: props.advisor?.email,
    firstName: props.advice.firstName,
    lastName: props.advice.lastName,
    street: props.advice.street,
    streetNumber: props.advice.streetNumber,
    zip: props.advice.zip,
    city: props.advice.city,
    email: props.advice.email,
    phone: props.advice.phone,
    email_confirmation: props.advice.email,
  }
  return 'https://balkon.heinerenergie.de/sammelbestellung?formdata=' + JSON.stringify(data);
});

const orderMailLink = computed(() => {
  const url = orderLink.value;
  const body = 'Hallo ' + props.advice.firstName + ',%0D%0A%0D%0A' + url + '%0D%0A%0D%0A' + 'HIER MUSST DU NOCH DAS PASSWORT EINTRAGEN' + '%0D%0A%0D%0AGruß,%0D%0A' + props.advisor?.first_name;
  const subject = 'heiner*energie%20Sammelbestellung';
   
  return 'mailto:' + props.advice.email + '?subject=' + subject + '&body=' + body;
});

function copyOrderLink() {
  const el = document.createElement('textarea');
  el.value = orderLink.value;
  document.body.appendChild(el);
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  notify('Bestelllink wurde in die Zwischenablage kopiert', 'success', 2000);
}
</script>

<template>
  <div style="display:flex;gap:20px;">
    <a :href="orderMailLink">
      <DxButton text="E-Mail mit Bestelllink verfassen" icon="email" />
    </a>
    <DxButton
      text="Bestelllink kopieren"
      icon="copy"
      @click="copyOrderLink"
    />
  </div>
</template> 