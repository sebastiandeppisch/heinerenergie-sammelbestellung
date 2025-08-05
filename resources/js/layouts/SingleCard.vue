<script setup lang="ts">
import DxScrollView from "devextreme-vue/scroll-view";
import MainPublic from "./MainPublic.vue";
import { Link } from "@inertiajs/vue3";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/shadcn/components/ui/card";

const props = defineProps<{
  title: string
  description?: string
}>();

</script>

<template>
  <div class="flex justify-center items-center h-full">
    <Card>
      <CardHeader>
        <CardTitle>{{ title }}</CardTitle>
        <CardDescription>{{ description }}</CardDescription>
      </CardHeader>
      <CardContent>
        <slot />
      </CardContent>
    </Card>
  </div>
</template>
