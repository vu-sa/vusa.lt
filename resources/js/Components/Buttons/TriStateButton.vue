<template>
  <NButtonGroup v-if="showOptions">
    <NButton v-for="button in buttons" :key="button.type" secondary :type="state === button.type ? 'primary' : 'default'"
      :color="state === button.type ? button.color : undefined" @click="handleClick(button.type)">
      <template #icon>
        <component :is="button.icon" />
      </template>
    </NButton>
    <!-- <NButton secondary :type="state === 'positive' ? 'primary' : 'default'"
      :color="state === 'positive' ? '#02bb0f' : undefined" @click="handleClick('positive')">
      <template #icon>
        <slot name="positiveIcon">
          <IFluentCheckmark24Filled />
        </slot>
      </template>
    </NButton>
    <NButton secondary :type="state === 'negative' ? 'primary' : 'default'"
      :color="state === 'negative' ? '#bb020f' : undefined" @click="handleClick('negative')">
      <template #icon>
        <slot name="negativeIcon">
          <IMdiClose />
        </slot>
      </template>
    </NButton>
    <NButton secondary :type="state === 'neutral' ? 'primary' : 'default'"
      :color="state === 'neutral' ? '#333333' : undefined" @click="handleClick('neutral')">
      <template #icon>
        <slot name="neutralIcon">
          <IMdiCheckboxIndeterminateOutline />
        </slot>
      </template>
</NButton> -->
  </NButtonGroup>
  <NButton v-else-if="['positive', 'negative', 'neutral'].includes(state)" secondary type="primary"
    :color="buttons.find(button => button.type === state)?.color">
    <template #icon>
      <component :is="buttons.find(button => button.type === state)?.icon" />
    </template>
  </NButton>
  <NButton v-else secondary @click="$emit('enableOptions')">
    <template #icon>
      <IMdiCheckboxMultipleMarkedOutline />
    </template>
    {{ $t('Nėra') }}
  </NButton>
</template>

<script setup lang="ts">
import { type Component, ref } from 'vue';
import IMdiClose from "~icons/mdi/close";
import IMdiCheckboxIndeterminateOutline from "~icons/mdi/checkbox-indeterminate-outline";
import IFluentCheckmark24Filled from "~icons/fluent/checkmark-24-filled";

const { positiveIcon, negativeIcon, neutralIcon, state } = defineProps<{
  row: Record<string, any>;
  showOptions: boolean;
  state: 'positive' | 'neutral' | 'negative' | null;
  positiveIcon?: Component;
  negativeIcon?: Component;
  neutralIcon?: Component;
}>();

const stateRef = ref(state);

const emit = defineEmits<{
  enableOptions: [void];
  changeState: ['positive' | 'neutral' | 'negative'];
}>();

const handleClick = (button: 'positive' | 'neutral' | 'negative') => {
  stateRef.value = button;
  emit('changeState', button);
};

const buttons = [
  {
    icon: positiveIcon || IFluentCheckmark24Filled,
    color: '#02bb0f',
    type: 'positive',
  },
  {
    icon: negativeIcon || IMdiClose,
    color: '#bb020f',
    type: 'negative',
  },
  {
    icon: neutralIcon || IMdiCheckboxIndeterminateOutline,
    color: '#333333',
    type: 'neutral',
  },
];
</script>
