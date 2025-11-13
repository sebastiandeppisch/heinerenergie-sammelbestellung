<script setup lang="ts">
import AdvisorMap from '@/views/AdvisorMap.vue';
import ProfilePictureUpload from '@/views/ProfilePictureUpload.vue';
import axios from 'axios';
import { DxNumberBox } from 'devextreme-vue';
import DxTextBox from 'devextreme-vue/text-box';
import notify from 'devextreme/ui/notify';
import { ref } from 'vue';
import { Button } from '@/shadcn/components/ui/button';
import { Save } from 'lucide-vue-next';
import { user as userData } from '../authHelper';

const user = ref(userData.value);

function saveAddress() {
    axios.post('/api/profile/address', user.value).then((response) => {
        console.log(response.data);

        user.value = response.data;
        notify('Adresse gespeichert', 'success');
    });
}
</script>

<template>
    <div ref="outer">
        <h2 class="content-block">Profil {{ user.name }}</h2>
        <div style="margin: 30px 40px 30px 40px">
            <div class="flex-row">
                <div class="dx-card flex-cell" style="padding: 30px; max-width: 400px">
                    <div class="flex-row">
                        <div class="flex-cell">
                            <span class="label">Beratungsgebiet</span>
                            <br />Trage hier die Adresse ein, von der Du Beratungen aus durchführen möchtest. Wenn Du Deine Adresse nicht mit anderen
                            Berater*innen teilen möchtest, kannst Du auch eine Adresse in Deiner Nähe angeben.
                            <div class="flex-row">
                                <div class="flex-cell">
                                    <DxTextBox label="Straße" style="margin: 10px" v-model="user.street" />
                                </div>
                                <div>
                                    <DxTextBox label="Nr." width="100px" style="margin: 10px" v-model="user.street_number" />
                                </div>
                            </div>

                            <div class="flex-row">
                                <div>
                                    <DxNumberBox label="PLZ" width="100px" style="margin: 10px" v-model="user.zip" />
                                </div>
                                <div class="flex-cell">
                                    <DxTextBox label="Stadt" style="margin: 10px" v-model="user.city" />
                                </div>
                            </div>

                            <div class="flex-row">
                                <div class="flex-cell">
                                    <DxNumberBox
                                        label="Beratungsgebiet (m)"
                                        style="margin: 10px"
                                        v-model="user.advice_radius"
                                        :show-clear-button="true"
                                    />
                                </div>
                            </div>

                            <Button variant="default" @click="saveAddress" class="w-full">
                                <Save class="h-4 w-4" />
                                Beratungsgebiet speichern
                            </Button>

                            <AdvisorMap :advisor="user" style="padding-top: 30px" />
                        </div>
                        <div class="flex-cell" style="display: none">
                            <ProfilePictureUpload />
                        </div>
                    </div>
                </div>
                <div class="dx-card flex-cell" style="padding: 30px; display: none"></div>
                <!--  <div class="dx-card flex-cell" style="padding:30px;">
          Test
        </div>-->
            </div>
        </div>
    </div>
</template>
<style scoped>
.flex-row {
    flex-direction: row;
    display: flex;
    width: 100%;
}
.flex-cell {
    /*padding: 30px;*/
    flex: 1;
}

.label {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 16px;
}
</style>
