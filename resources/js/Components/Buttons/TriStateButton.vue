<template>
  <ButtonGroup v-if="showOptions">
    <template v-for="button in buttons" :key="button.type">
      <Tooltip v-if="button.text">
        <TooltipTrigger as-child>
          <Button 
            size="icon-sm" 
            :variant="state === button.type ? 'default' : 'outline'"
            :class="state === button.type ? button.activeClass : ''"
            @click="handleClick(button.type)">
            <component :is="button.icon" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          {{ button.text }}
        </TooltipContent>
      </Tooltip>
      <!-- Button without tooltip -->
      <Button 
        v-else
        size="icon-sm" 
        :variant="state === button.type ? 'default' : 'outline'"
        :class="state === button.type ? button.activeClass : ''"
        @click="handleClick(button.type)">
        <component :is="button.icon" />
      </Button>
    </template>
  </ButtonGroup>
  <Button 
    v-else-if="['positive', 'negative', 'neutral'].includes(state)" 
    size="icon-sm"
    :class="buttons.find(button => button.type === state)?.activeClass">
    <component :is="buttons.find(button => button.type === state)?.icon" />
  </Button>
  <Button v-else variant="secondary" size="sm" @click="$emit('enableOptions')">
    <IMdiCheckboxMultipleMarkedOutline />
    {{ $t('Nėra') }}
  </Button>
</template>

<script setup lang="ts">
import { type Component, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
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
    activeClass: 'bg-green-600 hover:bg-green-700 text-white',
    type: 'positive',
    text: positiveText,
  },
  {
    icon: negativeIcon || IMdiClose,
    activeClass: 'bg-red-600 hover:bg-red-700 text-white',
    type: 'negative',
    text: negativeText,
  },
  {
    icon: neutralIcon || IMdiCheckboxIndeterminateOutline,
    activeClass: 'bg-zinc-600 hover:bg-zinc-700 text-white',
    type: 'neutral',
    text: neutralText,
  },
];
</script>
