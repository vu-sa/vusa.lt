<template>
  <div class="mt-4 flex flex-col gap-4">
    <NDynamicInput v-model:value="modelValue" @create="onCreateGridImage">
      <template #create-button-default>
        Sukurti
      </template>
      <template #default="{ value }">
        <div class="flex w-full gap-4">
          <NFormItem class="w-48" label="Nuotraukos plotis" :show-feedback="false">
            <NSelect v-model:value="value.colspan" type="text" placeholder="Layout"
              :options="imageGridOptions" />
          </NFormItem>
          <NFormItem class="grow" label="Nuotrauka" :show-feedback="false">
            <div>
              <TiptapImageButton v-if="!value.image" size="medium" class="grow" @submit="value.image = $event">
                Pasirinkti paveikslėlį
              </TiptapImageButton>
              <img v-else :src="value.image" class="aspect-video h-24 rounded-lg object-cover">
            </div>
          </NFormItem>
        </div>
      </template>
    </NDynamicInput>
  </div>
</template>

<script setup lang="ts">
import type { ImageGrid } from '@/Types/contentParts';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';

const modelValue = defineModel<ImageGrid['json_content']>();

const imageGridOptions = [
  {
    label: '1/1',
    value: 'col-span-full',
  }, {
    label: '1/3',
    value: 'col-span-2',
  }, {
    label: '2/3',
    value: 'col-span-4',
  }, {
    label: '1/2',
    value: 'col-span-3',
  }
];

function onCreateGridImage(): ImageGrid['json_content'][number] {
  return {
    colspan: "col-span-2",
    image: "",
  };
}
</script>

