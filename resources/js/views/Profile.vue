<script setup lang="ts">
import DxTextBox from "devextreme-vue/text-box";
import { store, useStore} from "./../store";
import ProfilePictureUpload from "./ProfilePictureUpload.vue";
import { DxGroupItem } from "devextreme-vue/form";
import { DxButton, DxNumberBox } from "devextreme-vue";
import { reactive } from "@vue/reactivity";
import axios from "axios";
import AdvisorMap from "./AdvisorMap.vue";

const state = reactive({
  user: store.getters.user as App.Models.User
});


function saveAddress() {
  axios.post("/api/profile/address", state.user).then((response) => {
    console.log(response.data);
    store.commit("setUser", {
      user: response.data as App.Models.User
    });
    state.user = response.data;
  });
}

</script>

<template>
  <div ref="outer">
    <h2 class="content-block">Profil {{state.user.name}}</h2>
    <div style="margin: 30px 40px 30px 40px;">
      <div class="flex-row">
        <div class="dx-card flex-cell" style="padding:30px;max-width:400px;">
         <div class="flex-row">
            <div class="flex-cell">
                <span class="label">Beratungsgebiet</span>
                <br>Trage hier die Adresse ein, von der Du Beratungen aus durchführen möchtest. Wenn Du Deine Adresse nicht mit anderen Berater*innen teilen möchtest, kannst Du auch eine Adresse in Deiner Nähe angeben.
                <div class="flex-row">
                  <div class="flex-cell">
                    <DxTextBox
                      label="Straße"
                      style="margin: 10px;"
                      v-model="state.user.street"
                    />
                  </div>
                  <div>
                    <DxTextBox
                      label="Nr."
                      width="100px"
                      style="margin: 10px;"
                      v-model="state.user.streetNumber"
                    />
                  </div>
                </div>

                <div class="flex-row">
                  <div >
                    <DxNumberBox
                      label="PLZ"
                      width="100px"
                      style="margin: 10px;"
                      v-model="state.user.zip"
                    />
                  </div>
                  <div class="flex-cell">
                    <DxTextBox
                      label="Stadt"
                      style="margin: 10px;"
                      v-model="state.user.city"
                    />
                  </div>
                </div>

                <div class="flex-row">
                  <div class="flex-cell">
                    <DxNumberBox
                      label="Beratungsgebiet (m)"
                      style="margin: 10px;"
                      v-model="state.user.advice_radius"
                    />
                  </div>
                </div>

                <DxButton
                  icon="save"
                  text="Beratungsgebiet speichern"
                  @click="saveAddress"
                  width="100%"
                  type="success"
                />

                <AdvisorMap :advisor="state.user" style="padding-top:30px;"/>
            </div>
            <div class="flex-cell" style="display:none;">
              <ProfilePictureUpload />
            </div>
          </div>
        </div>
        <div class="dx-card flex-cell" style="padding:30px;display:none;">
          

        </div>
   <!--  <div class="dx-card flex-cell" style="padding:30px;">
          Test
        </div>-->   
      </div>
    </div>
  </div>
</template>
<style scoped>
.flex-row{
  flex-direction: row;
  display: flex;
  width: 100%;
}
.flex-cell{
  /*padding: 30px;*/
  flex: 1;
}

.label{
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 16px;
}
</style>