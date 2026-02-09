<template>
  <Popover @update:open="handleOpenChange">
    <PopoverTrigger :disabled as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-[7rem] justify-start text-left font-normal',
          !selectedTime && 'text-zinc-500 dark:text-zinc-400',
          props.class
        )"
        :disabled
      >
        <Clock class="mr-2 h-4 w-4" />
        {{ formattedTime }}
        <ChevronDown class="ml-auto h-4 w-4 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <div class="flex p-2">
        <div class="flex flex-col pr-2 border-r border-zinc-200 dark:border-zinc-700">
          <div class="px-2 py-1.5 text-sm font-medium">
            {{ $t("forms.fields.hour") }}
          </div>
          <ScrollArea ref="hourScrollArea" class="h-40 w-16">
            <div class="flex flex-col">
              <Button
                v-for="hour in hours"
                :key="hour"
                variant="ghost"
                :class="selectedTime.hour === hour ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                @click="updateHour(hour)"
              >
                {{ hour.toString().padStart(2, '0') }}
              </Button>
            </div>
          </ScrollArea>
        </div>
        <div class="flex flex-col pl-2">
          <div class="px-2 py-1.5 text-sm font-medium">
            {{ $t("forms.fields.minute") }}
          </div>
          <ScrollArea class="h-40 w-16">
            <div class="flex flex-col">
              <Button
                v-for="minute in minutes"
                :key="minute"
                variant="ghost"
                :class="selectedTime.minute === minute ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                @click="updateMinute(minute)"
              >
                {{ minute.toString().padStart(2, '0') }}
              </Button>
            </div>
          </ScrollArea>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { Clock, ChevronDown } from 'lucide-vue-next';
import { ref, computed, watch, nextTick, useTemplateRef, type HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { ScrollArea } from '@/Components/ui/scroll-area';

interface TimeValue {
  hour: number;
  minute: number;
}

const props = withDefaults(defineProps<{
  modelValue?: TimeValue;
  class?: HTMLAttributes['class'];
  minuteStep?: number;
  hourRange?: [number, number]; // [min, max]
  disabled?: boolean;
}>(), {
  minuteStep: 5,
  hourRange: () => [0, 23] as [number, number],
  disabled: false,
});

const emit = defineEmits<(e: 'update:modelValue', value: TimeValue) => void>();

// Ref for scroll area to scroll to hour 12
const hourScrollArea = useTemplateRef<InstanceType<typeof ScrollArea>>('hourScrollArea');
const isOpen = ref(false);

// Initialize with either provided value or defaults
const selectedTime = ref<TimeValue>(props.modelValue || { hour: 12, minute: 0 });

// Use a flag to prevent recursive updates
const isInternalUpdate = ref(false);

// Watch for props modelValue changes
watch(() => props.modelValue, (newValue) => {
  if (newValue && !isInternalUpdate.value) {
    // Only update if this wasn't triggered by our own emit
    selectedTime.value = { ...newValue };
  }
}, { deep: true });

// Watch for internal selected time changes - only emit when direct user changes occur
const updateAndEmit = (newTime: TimeValue) => {
  isInternalUpdate.value = true;
  emit('update:modelValue', { ...newTime });
  // Reset flag after the current call stack completes
  setTimeout(() => {
    isInternalUpdate.value = false;
  }, 0);
};

// Generate hours array based on hourRange
const hours = computed(() => {
  const [min, max] = props.hourRange;
  return Array.from({ length: max - min + 1 }, (_, i) => min + i);
});

// Generate minutes array based on minuteStep
const minutes = computed(() => {
  return Array.from({ length: Math.floor(60 / props.minuteStep) }, (_, i) => i * props.minuteStep);
});

// Format the time for display
const formattedTime = computed(() => {
  if (!selectedTime.value) {
    return '--:--';
  }
  return `${selectedTime.value.hour.toString().padStart(2, '0')}:${selectedTime.value.minute.toString().padStart(2, '0')}`;
});

// Handler to update hour
const updateHour = (hour: number) => {
  const newTime = { ...selectedTime.value, hour };
  selectedTime.value = newTime;
  updateAndEmit(newTime);
};

// Handler to update minute
const updateMinute = (minute: number) => {
  const newTime = { ...selectedTime.value, minute };
  selectedTime.value = newTime;
  updateAndEmit(newTime);
};

// Scroll to center on hour 12 when popover opens
const scrollToCenter = () => {
  nextTick(() => {
    const scrollAreaEl = hourScrollArea.value?.$el;
    const viewport = scrollAreaEl?.querySelector('[data-slot="scroll-area-viewport"]') as HTMLElement | null;
    if (viewport) {
      const buttonHeight = 36;
      const [minHour] = props.hourRange;
      const targetHour = Math.min(Math.max(12, minHour), props.hourRange[1]);
      const hourIndex = targetHour - minHour;

      viewport.scrollTo({
        top: Math.max(0, (hourIndex - 1) * buttonHeight),
        behavior: 'instant',
      });
    }
  });
};

const handleOpenChange = (open: boolean) => {
  isOpen.value = open;
  if (open) {
    scrollToCenter();
  }
};
</script>
