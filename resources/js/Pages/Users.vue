<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Dialog, DialogDescription, DialogTitle } from '@/shadcn/components/ui/dialog';
import DialogContent from '@/shadcn/components/ui/dialog/DialogContent.vue';
import { Input } from '@/shadcn/components/ui/input';
import { PageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import DxDataGrid, { DxButton, DxColumn, DxEditing, DxScrolling, DxSummary, DxTotalItem } from 'devextreme-vue/data-grid';
import { ColumnButtonClickEvent } from 'devextreme/ui/data_grid';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import { AdaptTableHeight } from '../helpers';
import LaravelDataSource from '../LaravelDataSource';

const props = defineProps<{
    canPromoteUsersToSystemAdmin: boolean;
}>();

const users = new LaravelDataSource('api/users');

const outer = ref(null);

const tableHeight = new AdaptTableHeight(outer);
const r = tableHeight.getReactive();

const dialogOpen = ref(false);

const newPassword = ref('');
const selectedUser = ref<null | { id: string; first_name: string; last_name: string }>(null);

onMounted(() => {
    tableHeight.calcHeight();
});

function openDialog(e: ColumnButtonClickEvent) {
    selectedUser.value = e.row?.data;
    dialogOpen.value = true;
}

function changePassword() {
    if (newPassword.value.length < 8) {
        toast.error('Das Passwort muss mindestens 8 Zeichen lang sein.');
        return;
    }
    router.put(
        route('users.changePassword', selectedUser.value?.id),
        { password: newPassword.value },
        {
            onSuccess: () => {
                dialogOpen.value = false;
                newPassword.value = '';
            },
        },
    );
}

interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData;
        currentGroup?: App.Data.GroupBaseData;
        availableGroups?: App.Data.GroupData[];
    };
    defaultLogo?: string;
}

const page = usePage<CustomPageProps>();
const currentGroup = computed(() => page.props.auth.currentGroup);
</script>

<template>
    <div ref="outer">
        <h2 class="content-block">Berater*innen</h2>
        <div style="margin: 30px 40px 30px 40px">
            <div v-if="currentGroup !== undefined && currentGroup !== null">
                Wenn Du eine*n Berater*in erstellst, wird er*sie der Gruppe <b>{{ currentGroup.name }}</b> zugewiesen.
            </div>
            <DxDataGrid class="dx-card wide-card" :data-source="users" :show-borders="false" :column-auto-width="true" :height="r.height">
                <DxScrolling mode="virtual" />
                <DxEditing :allow-updating="true" :allow-adding="true" :allow-deleting="false" mode="cell" />
                <DxColumn data-field="first_name" caption="Vorname" />
                <DxColumn data-field="last_name" caption="Nachname" sort-order="asc" />
                <DxColumn data-field="email" caption="E-Mail Adresse" :editor-options="{ mode: 'email' }" />
                <DxColumn v-if="canPromoteUsersToSystemAdmin" data-field="is_admin" caption="System Admin" />
                <DxColumn type="buttons">
                    <DxButton name="changePassword" text="Passwort ändern" icon="key" @click="openDialog" />
                </DxColumn>
                <DxSummary>
                    <DxTotalItem column="first_name" summary-type="count" />
                </DxSummary>
            </DxDataGrid>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogTitle>Passwort ändern</DialogTitle>
                <DialogDescription>
                    Passwort von <b>{{ selectedUser?.first_name }} {{ selectedUser?.last_name }}</b> ändern
                </DialogDescription>
                <Input v-model="newPassword" type="password" placeholder="Neues Passwort" />
                <p>Das Passwort muss mindestens 8 Zeichen lang sein.</p>
                <p>Der Benutzer wird per E-Mail über die Passwortänderung informiert.</p>
                <Button variant="default" @click="changePassword">Passwort ändern</Button>
            </DialogContent>
        </Dialog>
    </div>
</template>
