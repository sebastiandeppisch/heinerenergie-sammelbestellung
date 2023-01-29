<template>
  <div class="widget-container flex-box">
    <span>Profilbild</span>
    
    <div style="position:relative">
      <div style="position:absolute;right:0px;padding:5px" v-if="r.imageSource !== null">
        <DxButton
          icon="trash"
          @click="removeImage"
          v-if="true"
          type="danger"
        />
      </div>
      <div
        id="dropzone-external"
        class="flex-box"
        :class="[r.isDropZoneActive
          ? 'dx-theme-accent-as-border-color dropzone-active'
          : 'dx-theme-border-color']"
        :element-attr="{ 
          style: 'position: relative;'
        }"
      >
        <img
          id="dropzone-image"
          :src="r.imageSource"
          v-if="r.imageSource !== null"
          alt="Kein Profilbild"
        >
        <div
          id="dropzone-text"
          class="flex-box"
        >
          <span>Drag & Drop Dein Profilbild hier</span>
          <span>…oder klicke hier und wähle ein Bild aus.</span>
        </div>
        
      </div>
    </div>
    <DxFileUploader
      id="file-uploader"
      dialog-trigger="#dropzone-external"
      drop-zone="#dropzone-external"
      :multiple="false"
      :allowed-file-extensions="r.allowedFileExtensions"
      upload-mode="instantly"
      upload-url="/api/upload"
      :visible="false"
      @drop-zone-enter="onDropZoneEnter"
      @drop-zone-leave="onDropZoneLeave"
      @uploaded="onUploaded"
      @upload-started="onUploadStarted"
      name="file"
      :upload-custom-data="{path: 'profiles/' }"
    />
  </div>
</template>
<script setup lang="ts">
import { DxFileUploader } from 'devextreme-vue/file-uploader';
import { DxProgressBar } from 'devextreme-vue/progress-bar';
import { reactive } from 'vue';
import axios from 'axios';
import { useStore } from '../store';
import { DxButton } from 'devextreme-vue/button';


const r = reactive({
  isDropZoneActive: false,
  imageSource: null,
  textVisible: true,
  allowedFileExtensions: ['.jpg', '.jpeg', '.gif', '.png'],
});

r.imageSource = useStore().getters.user.picture;

function onDropZoneEnter(e) {
  if (e.dropZoneElement.id === 'dropzone-external') {
    r.isDropZoneActive = true;
  }
}

function onDropZoneLeave(e) {
  if (e.dropZoneElement.id === 'dropzone-external') {
    r.isDropZoneActive = false;
  }
}

function onUploaded(e) {
  const { file } = e;
  const fileReader = new FileReader();
  fileReader.onload = () => {
    r.isDropZoneActive = false;
    r.imageSource = fileReader.result;
  };
  fileReader.readAsDataURL(file);
  const json = JSON.parse(e.request.response);
  r.imageSource = json.url;
  axios.post('/api/profile/picture', {url: json.url});
}

function onUploadStarted() {
  r.imageSource = null;
}

function removeImage() {
  r.imageSource = null;
  axios.post('/api/profile/picture', {url: null});
}


</script>
<style>
#dropzone-external {
  width: 350px;
  height: 350px;
  background-color: rgba(183, 183, 183, 0.1);
  border-width: 2px;
  border-style: dashed;
  padding: 10px;
}

#dropzone-external > * {
  pointer-events: none;
}

#dropzone-external.dropzone-active {
  border-style: solid;
}

.widget-container > span {
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 16px;
}

#dropzone-image {
  max-width: 100%;
  max-height: 100%;
}

#dropzone-text > span {
  font-weight: 100;
  opacity: 0.5;
}

#upload-progress {
  display: flex;
  margin-top: 10px;
}

.flex-box {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
</style>
