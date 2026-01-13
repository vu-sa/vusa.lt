<template>
  <Form @submit="onSubmit" :validation-schema="schema" :initial-values="initialValues">
    <div class="space-y-4">
      <FormField v-slot="{ componentField }" name="start_time" class="flex-1">
        <FormItem>
          <FormLabel class="inline-flex items-center gap-1">
            <component :is="Icons.DATE" class="h-4 w-4" />
            {{ $t("forms.fields.date") }} / {{ $t("forms.fields.time") }}
          </FormLabel>
          <FormControl>
            <DateTimePicker
              class="w-full"
              :model-value="componentField.modelValue"
              @update:model-value="componentField['onUpdate:modelValue']"
              @blur="(e) => componentField.onBlur(e as Event)"
              :hour-range="[7, 22]"
              :minute-step="5"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="type_id">
        <FormItem>
          <FormLabel class="inline-flex items-center gap-1">
            <component :is="Icons.TYPE" class="h-4 w-4" />
            {{ $tChoice("forms.fields.type", 0) }}
          </FormLabel>
          <FormControl>
            <Select v-bind="componentField" :disabled="isLoadingTypes">
              <SelectTrigger>
                <SelectValue :placeholder="isLoadingTypes ? $t('Loading...') : $t('Koks posėdžio tipas?')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="type in meetingTypes" :key="type.id" :value="type.id">
                  {{ type.title }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <Button type="submit" :disabled="loading || isLoadingTypes">
        {{ buttonLabel }}
      </Button>
    </div>
  </Form>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { ref, computed, onMounted } from "vue";
import { Form } from "vee-validate";
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';

import Icons from "@/Types/Icons/filled";

// Import Shadcn UI components
import { Button } from "@/Components/ui/button";
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/Components/ui/form";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { DateTimePicker } from "@/Components/ui/date-picker";

const emit = defineEmits<{
  (event: "submit", form: any): void;
}>();

const props = withDefaults(defineProps<{
  loading?: boolean;
  meeting: App.Entities.Meeting;
  meetingTypes?: Array<{id: number, title: string, model_type: string}>;
  submitLabel?: string;
}>(), {
  submitLabel: undefined,
});

// Define schema using Zod - single datetime field
const schema = toTypedSchema(
  z.object({
    start_time: z.date({
      required_error: $t("validation.required", { attribute: $t("forms.fields.date") }),
    }),
    type_id: z.number({
      required_error: $t("validation.required", { attribute: $tChoice("forms.fields.type", 0) }),
    }),
  })
);

// Determine initial values from meeting prop
const initialValues = computed(() => ({
  start_time: props.meeting?.start_time ? new Date(props.meeting.start_time) : undefined,
  type_id: (props.meeting as any)?.type_id
}));

// Handle form submission
const onSubmit = (values: Record<string, any>) => {
  const dt = values.start_time as Date;
  
  // Format date in local timezone without conversion to UTC
  const localISOString = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000))
    .toISOString()
    .slice(0, 19)
    .replace('T', ' ');
  
  const formData = {
    start_time: localISOString,
    type_id: values.type_id as number,
  };
  
  emit("submit", formData);
};

// Computed label for submit button
const buttonLabel = computed(() => {
  return props.submitLabel || $t("Išsaugoti");
});

// State for managing meeting types
const meetingTypes = ref(props.meetingTypes || []);
const isLoadingTypes = ref(!props.meetingTypes);

const fetchMeetingTypes = async () => {
  try {
    const response = await fetch(route("api.types.index"));
    const data = await response.json();
    meetingTypes.value = data.filter((type: { model_type: string }) => type.model_type === "App\\Models\\Meeting");
  } catch (error) {
    console.error('Failed to fetch meeting types:', error);
    meetingTypes.value = [];
  } finally {
    isLoadingTypes.value = false;
  }
};

// Only fetch if types weren't provided via props
onMounted(() => {
  if (!props.meetingTypes) {
    fetchMeetingTypes();
  }
});
</script>
