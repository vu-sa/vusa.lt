<template>
  <div class="mt-4 flex flex-col gap-4">
    <NFormItem label="Pavadinimas" :show-feedback="false">
      <OriginalTipTap v-model="json_content.title" html type="text" />
    </NFormItem>
    <NFormItem label="Subtitle" :show-feedback="false">
      <OriginalTipTap v-model="json_content.subtitle" html type="text" />
    </NFormItem>
    <NFormItem label="Background Nuotrauka" :show-feedback="false">
      <TiptapImageButton v-if="!json_content.backgroundMedia" size="medium"
        @submit="json_content.backgroundMedia = $event">
        Pasirinkti paveikslėlį
      </TiptapImageButton>
      <div v-else>
        <img :src="json_content.backgroundMedia" class="aspect-video h-24 rounded-lg object-cover">
        <Button variant="destructive" size="sm" @click="json_content.backgroundMedia = null">
          Ištrinti paveikslėlį
        </Button>
      </div>
    </NFormItem>
    <NFormItem label="Ar tamsinama ir blurinama foninė nuotrauka?" :show-feedback="false">
      <NSwitch v-model:value="options.backgroundBlur" checked-value="1" unchecked-value="0" />
    </NFormItem>
    <NFormItem label="Right side logo or photo" :show-feedback="false">
      <TiptapImageButton v-if="!json_content.rightMedia" size="medium" @submit="json_content.rightMedia = $event">
        Pasirinkti paveikslėlį
      </TiptapImageButton>
      <div v-else>
        <img :src="json_content.rightMedia" class="aspect-video h-24 rounded-lg object-cover">
        <Button variant="destructive" size="sm" @click="json_content.rightMedia = null">
          Ištrinti paveikslėlį
        </Button>
      </div>
    </NFormItem>
    <!-- Button text -->
    <NFormItem label="Button text" :show-feedback="false">
      <NInput v-model:value="json_content.buttonText" type="text" />
    </NFormItem>
    <!-- Button link -->
    <NFormItem label="Button link" :show-feedback="false">
      <NInput v-model:value="json_content.buttonLink" type="text" />
    </NFormItem>
    <NFormItem label="Button color" :show-feedback="false">
      <NSelect v-model:value="options.buttonColor" :options="[
        { 'label': 'Raudonas', 'value': 'red' }, { 'label': 'Geltonas', 'value': 'yellow' }, { 'label': 'Juodas', 'value': 'zinc' }, { 'label': 'Baltas', 'value': 'white' }]
        " />
    </NFormItem>
    <!-- Layout style -->
    <!-- <NFormItem label="Layout style" :show-feedback="false">
              <NSelect v-model:value="content.options.layoutStyle" default-value="default" :options="[
                { 'label': 'Default', 'value': 'default' }, { 'label': 'Centered', 'value': 'centered' }]
                " />
</NFormItem>-->
  </div>
</template>

<script setup lang="ts">
import OriginalTipTap from '@/Components/TipTap/OriginalTipTap.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import type { Hero } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { NFormItem, NSwitch, NInput, NSelect } from 'naive-ui';

const options = defineModel<Hero['options']>('options', { required: true });
const json_content = defineModel<Hero['json_content']>({ required: true });

</script>
