<script setup lang="ts">
import { computed, useId } from "vue";
import { cn } from "@/Utils/Shadcn/utils";
import Input from "./Input.vue";

interface Props {
  modelValue?: string | number | null;
  label: string;
  placeholder?: string;
  type?: string;
  disabled?: boolean;
  hint?: string;
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: "",
  placeholder: "",
  type: "text",
  disabled: false,
  hint: undefined,
  class: undefined,
});

const emit = defineEmits<{
  "update:modelValue": [value: string];
}>();

const id = useId();

const value = computed({
  get: () => props.modelValue ?? "",
  set: (val) => emit("update:modelValue", String(val)),
});
</script>

<template>
  <div class="space-y-1">
    <div class="group relative">
      <label
        :for="id"
        :class="cn(
          'absolute start-1 top-0 z-10 block -translate-y-1/2 bg-background px-2 text-xs font-medium text-foreground group-has-disabled:opacity-50',
          $slots.icon && 'start-7'
        )"
      >
        {{ label }}
      </label>
      <div class="relative flex items-center">
        <div
          v-if="$slots.icon"
          class="pointer-events-none absolute left-3 flex h-full items-center"
        >
          <slot name="icon" />
        </div>
        <Input
          :id="id"
          v-model="value"
          :type="type"
          :placeholder="placeholder"
          :disabled="disabled"
          :class="cn(
            'h-10',
            $slots.icon && 'pl-9',
            props.class
          )"
        />
      </div>
    </div>
    <p v-if="hint" class="text-xs text-muted-foreground">
      {{ hint }}
    </p>
  </div>
</template>
