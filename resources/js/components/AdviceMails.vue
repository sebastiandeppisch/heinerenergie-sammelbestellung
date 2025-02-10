<script setup lang="ts">
import { ref } from 'vue';

import axios from 'axios';

import DxAccordion from 'devextreme-vue/accordion';
import moment from 'moment';

const props = defineProps<{
	advice: App.Models.Advice;
}>();

interface Email {
	id: number;
	opened: boolean;
}

const emails = ref<Email[]>([]);

axios.get('/api/advices/' + props.advice.id + '/mails')
.then(response => {
	console.log(response.data);
	emails.value = response.data;
})
.catch(error => {
	console.error(error);
});

function toggleEmail(id: number) {
	const email = emails.value.find(email => email.id === id);
	if (email) {
		email.opened = !email.opened;
	}
}

function parseEmail(emailString: Record<string, string | null>) {
	return Object.entries(emailString)
		.map(([email, name]) => name ? `${name} <${email}>` : email)
		.join(', ');
}
</script>
<template>

	<div class="dx-card" style="padding:16px;width: 500px;">
		<strong>Versendete E-Mails</strong>
		<DxAccordion
			item-template="item"
			:items="emails"
			item-title-template="title"
			:collapsible="true"
		>
			<template #title="{ data}">
				<div class="email-container">
					<div class="email-time">
						{{moment(data.date).format('DD.MM.YYYY')}}
					</div>	
					<div class="email-to">
						An: {{ parseEmail(data.to) }}
					</div>
				</div>
			</template>
			<template #item="{data}"> 
				<iframe :srcdoc="data.content" class="email-iframe"></iframe>
			</template>

		</DxAccordion>
	</div>
</template>


<style scoped>
.email-card {
	padding: 16px;
	margin-bottom: 16px;
	border-radius: 8px;
}
.email-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	background-color: #f0f0f0;
	padding: 8px 16px;
}
.email-container {
  display: flex;
  justify-content: space-between;
  padding-right: 48px;
  background-color: #f0f0f0;
}
.email-iframe {
	width: 100%;
	min-height: 500px;
	border: none;
	padding: 0;
}
</style>
