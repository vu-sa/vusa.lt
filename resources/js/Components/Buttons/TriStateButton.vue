<template>
  <div class="flex items-center gap-1">
    <ButtonGroup v-if="showOptions">
      <template v-for="button in buttons" :key="button.type">
        <!-- Desktop: Tooltip on hover -->
        <Tooltip v-if="button.text && !isTouchDevice">
          <TooltipTrigger as-child>
            <Button
              size="icon-sm"
              :variant="currentState === button.type ? 'default' : 'outline'"
              :class="currentState === button.type ? button.activeClass : ''"
              @click="handleClick(button.type)">
              <component :is="button.icon" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            {{ button.text }}
          </TooltipContent>
        </Tooltip>
        <!-- Mobile: Popover on click for accessibility -->
        <Popover v-else-if="button.text && isTouchDevice">
          <PopoverTrigger as-child>
            <Button
              size="icon-sm"
              :variant="currentState === button.type ? 'default' : 'outline'"
              :class="currentState === button.type ? button.activeClass : ''"
              @click="handleClick(button.type)">
              <component :is="button.icon" />
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-48 p-2">
            <p class="text-xs">
              {{ button.text }}
            </p>
          </PopoverContent>
        </Popover>
        <!-- Button without tooltip -->
        <Button
          v-else
          size="icon-sm"
          :variant="currentState === button.type ? 'default' : 'outline'"
          :class="currentState === button.type ? button.activeClass : ''"
          @click="handleClick(button.type)">
          <component :is="button.icon" />
        </Button>
      </template>
    </ButtonGroup>

    <!-- Clear button when value is selected and showClear is enabled -->
    <Tooltip v-if="showOptions && showClear && currentState !== null">
      <TooltipTrigger as-child>
        <Button
          variant="ghost"
          size="icon-sm"
          class="h-6 w-6 text-zinc-400 hover:text-zinc-600"
          @click="handleClear">
          <IMdiCloseCircleOutline class="h-3.5 w-3.5" />
        </Button>
      </TooltipTrigger>
      <TooltipContent>
        {{ $t('Išvalyti pasirinkimą') }}
      </TooltipContent>
    </Tooltip>

    <Button
      v-if="!showOptions && ['positive', 'negative', 'neutral'].includes(currentState ?? '')"
      size="icon-sm"
      :class="buttons.find(button => button.type === currentState)?.activeClass">
      <component :is="buttons.find(button => button.type === currentState)?.icon" />
    </Button>
    <Button v-else-if="!showOptions" variant="secondary" size="sm" @click="$emit('enableOptions')">
      <IMdiCheckboxMultipleMarkedOutline />
      {{ $t('Nėra') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { type Component, ref, computed, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import IMdiClose from '~icons/mdi/close';
import IMdiCheckboxIndeterminateOutline from '~icons/mdi/checkbox-indeterminate-outline';
import IMdiCloseCircleOutline from '~icons/mdi/close-circle-outline';
import IFluentCheckmark24Filled from '~icons/fluent/checkmark-24-filled';
import IMdiCheckboxMultipleMarkedOutline from '~icons/mdi/checkbox-multiple-marked-outline';

const props = withDefaults(defineProps<{
  row: Record<string, any>;
  showOptions: boolean;
  state: 'positive' | 'neutral' | 'negative' | null;
  positiveIcon?: Component;
  negativeIcon?: Component;
  neutralIcon?: Component;
  positiveText?: string;
  negativeText?: string;
  neutralText?: string;
  showClear?: boolean;
}>(), {
  showClear: true,
});

const currentState = ref(props.state);
const isTouchDevice = ref(false);

onMounted(() => {
  // Detect touch device for mobile-friendly tooltips
  isTouchDevice.value = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
});

const emit = defineEmits<{
  enableOptions: [void];
  changeState: ['positive' | 'neutral' | 'negative' | null];
}>();

const handleClick = (button: 'positive' | 'neutral' | 'negative') => {
  if (currentState.value === button) {
    currentState.value = null;
    emit('changeState', null);
    return;
  }

  currentState.value = button;
  emit('changeState', button);
};

const handleClear = () => {
  currentState.value = null;
  emit('changeState', null);
};

const buttons = computed(() => [
  {
    icon: props.positiveIcon || IFluentCheckmark24Filled,
    activeClass: 'bg-green-600 hover:bg-green-700 text-white',
    type: 'positive' as const,
    text: props.positiveText,
  },
  {
    icon: props.negativeIcon || IMdiClose,
    activeClass: 'bg-red-600 hover:bg-red-700 text-white',
    type: 'negative' as const,
    text: props.negativeText,
  },
  {
    icon: props.neutralIcon || IMdiCheckboxIndeterminateOutline,
    activeClass: 'bg-zinc-600 hover:bg-zinc-700 text-white',
    type: 'neutral' as const,
    text: props.neutralText,
  },
]);
</script>
