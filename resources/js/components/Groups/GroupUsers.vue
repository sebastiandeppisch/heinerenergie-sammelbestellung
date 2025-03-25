<template>
  <div class="group-users">
    <div class="overflow-x-auto">
      <DxDataGrid
        :data-source="users"
        :show-borders="true"
        :column-auto-width="true"
        @init-new-row="initNewRow"
      >
        <DxEditing
          mode="row"
          :allow-updating="true"
          :allow-deleting="true"
          :allow-adding="true"
        />
        <DxColumn
          data-field="id"
          caption="Berater:in"
        >
          <DxLookup
            :data-source="availableUsers"
            display-expr="name"
            value-expr="id"
          />
        </DxColumn>

        <DxColumn
          data-field="is_admin"
          caption="Initiativen-Admin"
          :width="200"
          :allow-editing="true"
          data-type="boolean"
        />
        <DxSearchPanel :visible="true" />
      </DxDataGrid>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

import DxDataGrid, {
  DxColumn,
  DxEditing,
  DxLookup,
  DxSearchPanel,
} from "devextreme-vue/data-grid";
import LaravelDataSource from "../../LaravelDataSource"
import LaravelLookupSource from '@/LaravelLookupSource'

type GroupData = App.Data.GroupData;

const props = defineProps<{
  group: GroupData,
}>()

const users = new LaravelDataSource(`/api/groups/${props.group.id}/users`)
const availableUsers = new LaravelLookupSource('/api/users')

watch(() => props.group, () => {
  users.reload()
}, { deep: true })

function initNewRow(e: any) {
  e.data = {
    id: null,
    is_admin: false,
  }
}
</script>