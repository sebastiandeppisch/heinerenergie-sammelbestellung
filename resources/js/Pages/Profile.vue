<script setup lang="ts">
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import AdvisorMap from '@/views/AdvisorMap.vue';
import ProfilePictureUpload from '@/views/ProfilePictureUpload.vue';
import axios from 'axios';
import notify from 'devextreme/ui/notify';
import { Save } from 'lucide-vue-next';
import { ref } from 'vue';
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
                                <div class="flex-cell" style="margin: 10px">
                                    <Label for="street">Straße</Label>
                                    <Input id="street" v-model="user.street" />
                                </div>
                                <div style="margin: 10px">
                                    <Label for="street_number">Nr.</Label>
                                    <Input id="street_number" v-model="user.street_number" style="width: 100px" />
                                </div>
                            </div>

                            <div class="flex-row">
                                <div style="margin: 10px">
                                    <Label for="zip">PLZ</Label>
                                    <Input id="zip" type="number" style="width: 100px" v-model="user.zip" />
                                </div>
                                <div class="flex-cell" style="margin: 10px">
                                    <Label for="city">Stadt</Label>
                                    <Input id="city" v-model="user.city" />
                                </div>
                            </div>

                            <div class="flex-row">
                                <div class="flex-cell" style="margin: 10px">
                                    <Label for="advice_radius">Beratungsgebiet (m)</Label>
                                    <Input id="advice_radius" type="number" v-model="user.advice_radius" />
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
