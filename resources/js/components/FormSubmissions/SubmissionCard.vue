<template>


<Card v-if="showGroupHeader"  class="basis-full items-center p-1  mb-2">
    <h3 class="basis-full m-0 p-0">{{ submission.form_name }}</h3>
</Card>


  <Card class="h-full hover:shadow-lg transition-shadow mr-4 mb-4">
    <CardHeader class="pb-3">
      <div class="flex justify-between items-start mb-2">
        <Badge variant="outline" class="text-xs" v-if="showGroupBadge">
          {{ submission.form_name }}
        </Badge>
        <span class="text-xs text-gray-500">{{ formatDate(submission.submitted_at) }}</span>
      </div>
    <!--  <p class="text-sm text-gray-600 font-medium">
        {{ submission.advice_id || '' }}
      </p> -->
    </CardHeader>
    <CardContent>
      <p class="text-sm text-gray-700">
        <FormSubmissionRenderer
            :form-submission="submission"
        />
      </p>


      <div class="mt-4">
        <Button
            v-if="!submission.seen"
            class="text-xs"
            @click="seen(submission.id)"
            >
            <MailCheck/>
            Als gelesen markieren
        </Button>
        <Button
            v-else
            variant="secondary"
            class="text-xs"
            @click="unseen(submission.id)"
            >
            <MailOpen/>
            Als ungesehen markieren
        </Button>
      </div>


      <CardFooter>

      </CardFooter>

    </CardContent>

  </Card>
</template>

<script lang="ts" setup>
import Card from '@/shadcn/components/ui/card/Card.vue'
import CardHeader from '@/shadcn/components/ui/card/CardHeader.vue'
import CardContent from '@/shadcn/components/ui/card/CardContent.vue'
import Badge from '@/shadcn/components/ui/badge/Badge.vue'
import CardFooter from '@/shadcn/components/ui/card/CardFooter.vue'
import Button from '@/shadcn/components/ui/button/Button.vue'
import { MailCheck, MailOpen } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormSubmissionRenderer from '../FormBuilder/FormSubmissionRenderer.vue'
const props = defineProps<{
  submission: App.Data.FormSubmissionData,
  index: number | string
  showGroupHeader: boolean;
  showGroupBadge: boolean;
}>()

const formatDate = (date: Date | string): string => {
  const dateObj = typeof date === 'string' ? new Date(date) : date
  return dateObj.toLocaleDateString('de-DE')
}

function seen(id: string) {

  router.post(route('form-submissions.mark-seen', {
    formSubmission: id,
    index: props.index
  }) , {},
    {
    only: ['formSubmissions'],
    preserveUrl: true,
  })
}

function unseen(id: string) {
  router.post(route('form-submissions.mark-unseen', {
    formSubmission: id,
    index: props.index
  }),{},
   {
    only: ['formSubmissions'],
    preserveUrl: true,
  })
}
</script>
