<template>
  <NButtonGroup v-if="showOptions">
    <template v-for="button in buttons" :key="button.type">
      <NTooltip v-if="button.text" trigger="hover">
        <template #trigger>
          <NButton secondary :type="state === button.type ? 'primary' : 'default'"
            :color="state === button.type ? button.color : undefined" @click="handleClick(button.type)">
            <template #icon>
              <component :is="button.icon" />
            </template>
          </NButton>
        </template>
        {{ button.text }}
      </NTooltip>
      <!-- Repeated button element -->
      <NButton v-else secondary :type="state === button.type ? 'primary' : 'default'"
        :color="state === button.type ? button.color : undefined" @click="handleClick(button.type)">
        <template #icon>
          <component :is="button.icon" />
        </template>
      </NButton>
    </template>
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
import { trans as $t } from 'laravel-vue-i18n';
import IMdiClose from "~icons/mdi/close";
import IMdiCheckboxIndeterminateOutline from "~icons/mdi/checkbox-indeterminate-outline";
import IFluentCheckmark24Filled from "~icons/fluent/checkmark-24-filled";

const { positiveIcon, negativeIcon, neutralIcon, state, positiveText, negativeText, neutralText } = defineProps<{
  row: Record<string, any>;
  showOptions: boolean;
  state: 'positive' | 'neutral' | 'negative' | null;
  positiveIcon?: Component;
  negativeIcon?: Component;
  neutralIcon?: Component;
  positiveText?: string;
  negativeText?: string;
  neutralText?: string;
}>();

const stateRef = ref(state);

const emit = defineEmits<{
  enableOptions: [void];
  changeState: ['positive' | 'neutral' | 'negative' | null];
}>();

const handleClick = (button: 'positive' | 'neutral' | 'negative') => {

  if (stateRef.value === button) {
    stateRef.value = null;
    emit('changeState', null);
    return;
  }

  stateRef.value = button;
  emit('changeState', button);
};

const buttons = [
  {
    icon: positiveIcon || IFluentCheckmark24Filled,
    color: '#02bb0f',
    type: 'positive',
    text: positiveText,
  },
  {
    icon: negativeIcon || IMdiClose,
    color: '#bb020f',
    type: 'negative',
    text: negativeText,
  },
  {
    icon: neutralIcon || IMdiCheckboxIndeterminateOutline,
    color: '#333333',
    type: 'neutral',
    text: neutralText,
  },
];
</script>
