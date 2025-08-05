<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
import { User } from 'lucide-vue-next';
import logo from '../../img/logo.png';

const openBackend = () => {
  router.visit('/backend');
}
</script>

<template>
  <header class="flex items-center justify-between px-4 py-2 bg-white border-b shadow-sm rounded-b-lg">
    <!-- Left spacer -->
    <div class="w-[70px]"></div>
    
    <!-- Logo -->
    <div class="flex items-center">
      <Link href="/">
        <img :src="logo" alt="Logo" class="h-8 w-24 object-contain">
      </Link>
    </div>

    <!-- Center title -->
    <div class="flex-1 text-center">
      <h1 class="text-2xl font-semibold text-gray-800">Balkonsolar</h1>
    </div>

    <!-- Right button -->
    <div class="flex items-center">
      <button 
        @click="openBackend"
        class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md transition-colors"
      >
        <User class="h-4 w-4" />
        <span class="hidden sm:inline">Berater:innen Zugang</span>
      </button>
    </div>
  </header>
</template>


