<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 -translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-2"
  >
    <div
      v-if="modelValue"
      role="alert"
      :class="[
        'relative w-full rounded-xl border px-4 py-3.5',
        'flex items-start gap-3',
        variantClasses,
      ]"
    >
      <!-- Icon -->
      <div
        :class="[
          'flex-shrink-0 flex items-center justify-center',
          'w-8 h-8 rounded-lg',
          iconBgClasses,
        ]"
      >
        <component
          :is="iconComponent"
          :class="['h-4.5 w-4.5', iconClasses]"
        />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0 pt-0.5">
        <h5
          v-if="title"
          :class="['font-semibold text-sm leading-tight mb-1', titleClasses]"
        >
          {{ title }}
        </h5>
        <p :class="['text-sm leading-relaxed', descriptionClasses]">
          <slot>{{ description }}</slot>
        </p>

        <!-- Action button -->
        <Button
          v-if="actionLabel"
          :variant="actionVariant"
          size="sm"
          class="mt-3"
          @click="$emit('action')"
        >
          {{ actionLabel }}
        </Button>
      </div>

      <!-- Dismiss button -->
      <button
        v-if="dismissible"
        type="button"
        :class="[
          'flex-shrink-0 p-1.5 rounded-md',
          'transition-colors duration-200',
          'hover:bg-black/5 dark:hover:bg-white/10',
          'focus:outline-none focus:ring-2 focus:ring-offset-2',
          dismissClasses,
        ]"
        :aria-label="$t('Uždaryti')"
        @click="modelValue = false"
      >
        <X class="h-4 w-4" />
      </button>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  X,
  AlertTriangle,
  AlertCircle,
  Info,
  CheckCircle2,
} from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

type AlertVariant = 'warning' | 'danger' | 'info' | 'success';

interface Props {
  variant?: AlertVariant;
  title?: string;
  description?: string;
  icon?: Component;
  dismissible?: boolean;
  actionLabel?: string;
  actionVariant?: 'default' | 'outline' | 'secondary' | 'ghost';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'warning',
  dismissible: true,
  actionVariant: 'outline',
});

const emit = defineEmits<{
  action: [];
}>();

const modelValue = defineModel<boolean>({ default: true });

// Default icons per variant
const defaultIcons: Record<AlertVariant, Component> = {
  warning: AlertTriangle,
  danger: AlertCircle,
  info: Info,
  success: CheckCircle2,
};

const iconComponent = computed(() => props.icon || defaultIcons[props.variant]);

// Variant-based styling
const variantClasses = computed(() => {
  const classes: Record<AlertVariant, string> = {
    warning: [
      'bg-amber-50 border-amber-200',
      'dark:bg-amber-950/30 dark:border-amber-800/50',
    ].join(' '),
    danger: [
      'bg-red-50 border-red-200',
      'dark:bg-red-950/30 dark:border-red-800/50',
    ].join(' '),
    info: [
      'bg-blue-50 border-blue-200',
      'dark:bg-blue-950/30 dark:border-blue-800/50',
    ].join(' '),
    success: [
      'bg-emerald-50 border-emerald-200',
      'dark:bg-emerald-950/30 dark:border-emerald-800/50',
    ].join(' '),
  };
  return classes[props.variant];
});

const iconBgClasses = computed(() => {
  const classes: Record<AlertVariant, string> = {
    warning: 'bg-amber-100 dark:bg-amber-900/40',
    danger: 'bg-red-100 dark:bg-red-900/40',
    info: 'bg-blue-100 dark:bg-blue-900/40',
    success: 'bg-emerald-100 dark:bg-emerald-900/40',
  };
  return classes[props.variant];
});

const iconClasses = computed(() => {
  const classes: Record<AlertVariant, string> = {
    warning: 'text-amber-600 dark:text-amber-400',
    danger: 'text-red-600 dark:text-red-400',
    info: 'text-blue-600 dark:text-blue-400',
    success: 'text-emerald-600 dark:text-emerald-400',
  };
  return classes[props.variant];
});

const titleClasses = computed(() => {
  const classes: Record<AlertVariant, string> = {
    warning: 'text-amber-800 dark:text-amber-300',
    danger: 'text-red-800 dark:text-red-300',
    info: 'text-blue-800 dark:text-blue-300',
    success: 'text-emerald-800 dark:text-emerald-300',
  };
  return classes[props.variant];
});

const descriptionClasses = computed(() => {
  const classes: Record<AlertVariant, string> = {
    warning: 'text-amber-700 dark:text-amber-400/90',
    danger: 'text-red-700 dark:text-red-400/90',
    info: 'text-blue-700 dark:text-blue-400/90',
    success: 'text-emerald-700 dark:text-emerald-400/90',
  };
  return classes[props.variant];
});

const dismissClasses = computed(() => {
  const classes: Record<AlertVariant, string> = {
    warning: 'text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 focus:ring-amber-500',
    danger: 'text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 focus:ring-red-500',
    info: 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 focus:ring-blue-500',
    success: 'text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 focus:ring-emerald-500',
  };
  return classes[props.variant];
});
</script>
