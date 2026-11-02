<script setup lang="ts">
withDefaults(defineProps<{ wordmark?: boolean }>(), { wordmark: true });
</script>

<template>
    <img :src="wordmark ? '/hexnet/logo.svg' : '/hexnet/logo-mark.svg'" alt="HexaNetz" />
</template>
