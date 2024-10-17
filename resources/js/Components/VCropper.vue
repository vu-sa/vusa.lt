<template>
  <div class="mb-4 flex items-center gap-2">
    <NButtonGroup>
      <NButton @click="zoomImage(0.1)">
        <template #icon>
          <IFluentZoomIn16Regular />
        </template>Priartinti
      </NButton>
      <NButton @click="zoomImage(-0.1)">
        <template #icon>
          <IFluentZoomOut16Regular />
        </template>Nutolinti
      </NButton>
    </NButtonGroup>
    <NButton @click="centerImage">
      <template #icon>
        <IFluentAlignCenterVertical16Regular />
      </template>Centruoti
    </NButton>
    <NButton @click="rotateImage90">
      <template #icon>
        <IFluentArrowRotateClockwise16Regular />
      </template>Pasukti
      90°
    </NButton>
    <NButton type="primary" @click="handleImageCrop">
      <template #icon>
        <IFluentCheckmarkCircle16Filled />
      </template>Apkirpti
    </NButton>
  </div>

  <cropper-canvas ref="canvas" v-bind="$attrs">
    <cropper-image ref="image" :src alt="Picture" rotatable scalable />
    <cropper-shade hidden />
    <cropper-handle action="select" plain hidden />
    <cropper-selection ref="selection" initial-coverage="0.5" movable resizable>
      <cropper-grid role="grid" covered />
      <cropper-crosshair centered />
      <cropper-handle action="move" theme-color="rgba(255, 255, 255, 0.35)" />
      <cropper-handle action="n-resize" />
      <cropper-handle action="e-resize" />
      <cropper-handle action="s-resize" />
      <cropper-handle action="w-resize" />
      <cropper-handle action="ne-resize" />
      <cropper-handle action="nw-resize" />
      <cropper-handle action="se-resize" />
      <cropper-handle action="sw-resize" />
    </cropper-selection>
  </cropper-canvas>
</template>

<script setup lang="ts">
import "cropperjs";
import { computed, useTemplateRef } from "vue";
import { useAxios } from "@vueuse/integrations/useAxios";
import type { CropperCanvas, CropperImage, CropperSelection } from "cropperjs";

const props = defineProps<{
  path: string;
}>();

const emit = defineEmits<{
  finish: [url: string];
}>();

const src = defineModel<string>("src");

const canvas = useTemplateRef<CropperCanvas>("canvas");
const image = useTemplateRef<CropperImage>("image");
const selection = useTemplateRef<CropperSelection>("selection");

const fileName = computed(() => {
  let name = src.value?.split("/").pop();

  // split by _ and check if first part is a number
  if (name?.split("_")[0].match(/^\d+$/)) {
    name = name?.split("_").slice(1).join("_");
  }

  return name;
});

const centerImage = () => {
  if (!canvas.value || !image.value) return;

  image.value.$center('cover');
};

const zoomImage = (value: number) => {
  if (!canvas.value || !image.value) return;

  image.value.$zoom(value);
};

const rotateImage90 = () => {
  if (!canvas.value || !image.value) return;

  image.value.$rotate("90deg");
};

const handleImageCrop = async () => {
  let dataUrl = await cropImage();

  const { data } = await useAxios(route("files.uploadImage"), {
    method: "post",
    data: {
      image: dataUrl,
      path: props.path,
      name: fileName.value,
    },
  });

  src.value = data.value.url;

  centerImage();
  image.value?.$scale(1);
  image.value?.$rotate(0);

  emit("finish", data.value.url);
};

const cropImage = async () => {
  if (!canvas.value || !image.value) return;

  let dataUrl = await selection.value
    ?.$toCanvas()
    .then((resultCanvas) => {
      return resultCanvas.toDataURL("image/jpg");
    })
    .catch((err) => {
      return undefined;
    });

  return dataUrl;
};
</script>
