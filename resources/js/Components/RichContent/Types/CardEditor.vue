<template>
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-3 items-center gap-7">
      <NFormItem label="Variantas" :show-feedback="false">
        <NSelect v-model:value="options.variant" :options="[
          { 'label': 'Outlined', 'value': 'outline' }, { 'label': 'Soft', 'value': 'soft' }]
          " />
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
      <div class="mt-4">
        <NCheckbox v-model:checked="options.isTitleColored" :checked-value="true" :unchecked-value="false">
          Ar spalvinti kortelės pavadinimą?
        </NCheckbox>
        <NCheckbox v-model:checked="options.showIcon" :checked-value="true" :unchecked-value="false">
          Ar naudoti ikonėlę?
          <InfoPopover>
            <p>Ikona bus rodoma kairėje pusėje </p>
            <ul>
              <li> Raudona: šauktukas </li>
              <li> Geltona: klaustukas </li>
              <li> Pilka: informacija </li>
            </ul>
          </InfoPopover>
        </NCheckbox>
      </div>
    </div>
    <NFormItem label="Pavadinimas" :show-feedback="false">
      <NInput v-model:value="options.title" type="text" />
    </NFormItem>
    <NFormItem label="Turinys" :show-feedback="false">
      <ModernTipTap v-model="content" />
    </NFormItem>
  </div>
</template>

<script setup lang="ts">
import type { ShadcnCard } from '@/Types/contentParts';
import ModernTipTap from '@/Components/TipTap/ModernTipTap.vue';
import InfoPopover from '@/Components/Buttons/InfoPopover.vue';

const content = defineModel<ShadcnCard['json_content']>();
const options = defineModel<ShadcnCard['options']>('options');
</script>
