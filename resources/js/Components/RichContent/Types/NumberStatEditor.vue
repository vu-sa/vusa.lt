<template>
  <div class="mt-4 flex flex-col gap-4">
    <NFormItem label="Pavadinimas" :show-feedback="false">
      <NInput v-model:value="options.title" type="text" />
    </NFormItem>
    <NFormItem label="Spalva" :show-feedback="false">
      <NSelect v-model:value="options.color" :options="[{
        'label': 'Pilka',
        'value': 'zinc'
      }, {
        'label': 'Raudona',
        'value': 'red'
      }, {
        'label': 'Geltona',
        'value': 'yellow'
      }]" />
    </NFormItem>
    <NDynamicInput v-model:value="content" @create="onCreateNumberStat">
      <template #create-button-default>
        Sukurti
      </template>
      <template #default="{ value }">
        <div class="flex w-full gap-4">
          <NFormItem class="w-48" label="Prierašas" :show-feedback="false">
            <NInput v-model:value="value.label" type="text" />
          </NFormItem>
          <NFormItem class="grow" label="Numeris" :show-feedback="false">
            <NInputNumber v-model:value="value.endNumber" />
          </NFormItem>
        </div>
      </template>
    </NDynamicInput>
  </div>
</template>

<script setup lang="ts">
import type { NumberStatSection } from '@/Types/contentParts';
import { NInputNumber } from "naive-ui";

const content = defineModel<NumberStatSection['json_content']>();
const options = defineModel<NumberStatSection['options']>('options');

function onCreateNumberStat(): NumberStatSection['json_content'][number] {
  return {
    endNumber: 0,
    label: ""
  }
}
</script>

