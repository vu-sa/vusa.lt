<template>
  <div class="mb-4 flex items-center gap-2">
    <div class="flex">
      <Button variant="outline" size="sm" class="rounded-r-none" @click="zoomImage(0.1)">
        <IFluentZoomIn16Regular class="mr-2 h-4 w-4" />
        Priartinti
      </Button>
      <Button variant="outline" size="sm" class="rounded-l-none border-l-0" @click="zoomImage(-0.1)">
        <IFluentZoomOut16Regular class="mr-2 h-4 w-4" />
        Nutolinti
      </Button>
    </div>
    <Button variant="outline" size="sm" @click="centerImage">
      <IFluentAlignCenterVertical16Regular class="mr-2 h-4 w-4" />
      Centruoti
    </Button>
    <Button variant="outline" size="sm" @click="rotateImage90">
      <IFluentArrowRotateClockwise16Regular class="mr-2 h-4 w-4" />
      Pasukti 90°
    </Button>
    <Button variant="default" size="sm" @click="handleImageCrop">
      <IFluentCheckmarkCircle16Filled class="mr-2 h-4 w-4" />
      Apkirpti
    </Button>
  </div>

  <cropper-canvas ref="canvas" v-bind="$attrs">
    <cropper-image ref="image" :src alt="Image to be cropped" rotatable scalable />
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
import type { CropperCanvas, CropperImage, CropperSelection } from "cropperjs";
import { router, usePage } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";

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
  if (name) {
    const firstPart = name.split("_")[0];
    if (firstPart?.match(/^\d+$/)) {
      name = name.split("_").slice(1).join("_");
    }
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
  const dataUrl = await cropImage();
  
  if (!dataUrl) {
    console.error("Failed to generate crop data");
    return;
  }

  const blobResponse = await fetch(dataUrl);
  const blob = await blobResponse.blob();

  router.post(
    route("files.uploadImage"),
    {
      image: new File([blob], fileName.value || "cropped_image.jpg", { type: "image/jpeg" }),
      path: props.path,
      name: fileName.value,
    },
    {
      onSuccess: (page) => {
        if (page.props.data && typeof page.props.data === "object" && "url" in page.props.data) {
          const url = page.props.data.url as string;
          src.value = url;
          centerImage();
          emit("finish", url);
        }
      },
      onError: (errors) => {
        console.error("Upload failed:", errors);
      },
    }
  );
  image.value?.$scale(1);
  image.value?.$rotate(0);
};

const cropImage = async () => {
  if (!canvas.value || !image.value) return;

  const dataUrl = await selection.value
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
