<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import SingleCard from "../layouts/SingleCard.vue";
import MainPublic from "../layouts/MainPublic.vue";

const props = defineProps<{
  initiatives: App.Data.GroupData[];
  canActAsSystemAdmin: boolean;
}>();

function selectInitiative(id: number, asAdmin: boolean = false) {
  router.post(`/actAsGroup/${id}`, {
    asAdmin
  });
}

function selectSystemAdmin() {
  router.post('/actAsSystemAdmin');
}

defineOptions({
  layout: MainPublic
})
</script>

<template>
  <SingleCard
    title="Initiative auswählen"
    description="Wähle die Initiative, die Du gerade verwenden möchtest. Du kannst jederzeit zwischen den Initiativen oben rechts im User-Panel wechseln."
    :showBackLink="false"
    :fixedWidth="false"
  >
    <div class="initiatives-grid">
      <!-- Regular Initiative Cards -->
      <div v-for="initiative in initiatives" :key="initiative.id" class="initiative-card" @click="selectInitiative(initiative.id)">
        <div class="initiative-content">
          <div class="initiative-logo" v-if="initiative.logo_path">
            <img :src="initiative.logo_path" :alt="initiative.name">
          </div>
          <div class="initiative-name">
            {{ initiative.name }}
          </div>
        </div>
      </div>

      <!-- Admin Initiative Cards -->
      <div 
        v-for="initiative in initiatives.filter(i => i.userCanActAsAdmin)" 
        :key="`${initiative.id}-admin`" 
        class="initiative-card admin" 
        @click="selectInitiative(initiative.id, true)"
      >
        <div class="initiative-content">
          <div class="initiative-logo" v-if="initiative.logo_path">
            <img :src="initiative.logo_path" :alt="initiative.name">
          </div>
          <div class="initiative-name">
            {{ initiative.name }}
            <span class="role-badge">Admin</span>
          </div>
        </div>
      </div>

      <!-- System Admin Card -->
      <div 
        v-if="canActAsSystemAdmin" 
        class="initiative-card system-admin"
        @click="selectSystemAdmin"
      >
        <div class="initiative-content">
          <div class="initiative-logo">
            <i class="dx-icon-key"></i>
          </div>
          <div class="initiative-name">
            System Admin
          </div>
        </div>
      </div>
    </div>
  </SingleCard>
</template>

<style lang="scss">
@import "../themes/generated/variables.base.scss";

.initiatives-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  padding: 24px 0;
  max-width: 1200px;
  margin: 0 auto;
}

.initiative-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  border: 1px solid rgba($base-text-color, 0.1);
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  &.admin {
    background: rgba($base-color, 0.02);
    border: 1px solid rgba($base-color, 0.2);

    &:hover {
      background: rgba($base-color, 0.05);
    }
  }

  &.system-admin {
    background: rgba(#000, 0.02);
    border: 1px solid rgba(#000, 0.2);

    &:hover {
      background: rgba(#000, 0.05);
    }

    .dx-icon-key {
      font-size: 48px;
      color: rgba(#000, 0.7);
    }
  }
}

.initiative-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.initiative-logo {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  width: 100%;

  img {
    max-width: 100%;
    max-height: 60px;
    object-fit: contain;
    display: block;
  }
}

.initiative-name {
  font-size: 18px;
  font-weight: 500;
  color: $base-text-color;
  text-align: center;
  display: flex;
  align-items: center;
  gap: 8px;
}

.role-badge {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba($base-color, 0.1);
  color: $base-color;
  font-weight: 600;
}
</style> 