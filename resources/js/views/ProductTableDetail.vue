<script setup lang="ts">
import DxDataGrid, {
  DxColumn,
  DxEditing,
} from "devextreme-vue/data-grid";
import LaravelDataSource from '../LaravelDataSource'
import LaravelLookupSource from '../LaravelLookupSource'
import { ref, onMounted } from 'vue'
import {AdaptTableHeight} from '../helpers'
import axios from 'axios'
import DxButton from 'devextreme-vue/button';
import DxPopup from 'devextreme-vue/popup';
import DxTextBox from 'devextreme-vue/text-box';
import DxFileUploader from 'devextreme-vue/file-uploader';
import { json } from "body-parser";
import dxTreeView from "devextreme/ui/tree_view";
import DxTreeView from "devextreme-vue/tree-view";

interface Props {
  product: App.Models.Product
}
const props = defineProps<Props>();


const productDownloadsGrid = ref();

const productDownloads = new LaravelDataSource('api/products/' + props.product.id + '/downloads');

let url: string|null = null;

function fileUploaded(e: any){
  const json = JSON.parse(e.request.response);
  url = json.url;

  const grid = productDownloadsGrid.value.instance;
  grid.addRow()
}

function initNewRow(args: any){
  if(url !== null){
    args.data.url = url;
  }
}
</script>

<template>
  <div class="dx-card content" style="width:620px;padding:10px;">
  <DxDataGrid
    :data-source="productDownloads"
    :show-borders="false"
    :column-auto-width="true"
    width="600px"
    ref="productDownloadsGrid"
    @init-new-row="initNewRow"
  >
    <DxEditing
      :allow-updating="true"
      :allow-deleting="true"
      :allow-adding="true"
      mode="cell"
    />
    <DxColumn data-field="name" caption="Name" />
    <DxColumn data-field="url" caption="URL" width="400"/>
  </DxDataGrid>

  <DxFileUploader
    upload-mode="instantly"
    :upload-url="'api/upload/'"
    @uploaded="fileUploaded"
    name="file"
    :upload-custom-data="{path: 'products/' }"
  />
  </div>
  
</template>
