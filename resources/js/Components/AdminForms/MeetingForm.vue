<template>
  <Form @submit="onSubmit" :validation-schema="schema" :initial-values="initialValues">
    <div class="space-y-4">
      <div class="flex flex-wrap gap-4">
        <FormField v-slot="{ componentField }" name="date" class="flex-1 min-w-[240px]">
          <FormItem>
            <FormLabel class="inline-flex items-center gap-1">
              <component :is="Icons.DATE" class="h-4 w-4" />
              {{ $t("forms.fields.date") }}
            </FormLabel>
            <FormControl>
              <DatePicker
                class="w-full"
                v-bind="componentField"
              />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
        
        <FormField v-slot="{ componentField }" name="time" class="min-w-[120px]">
          <FormItem>
            <FormLabel class="inline-flex items-center gap-1">
              {{ $t("forms.fields.time") }}
            </FormLabel>
            <FormControl>
              <TimePicker 
                v-bind="componentField"
                :hour-range="[7, 22]"
                :minute-step="5"
              />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
      </div>

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
import { 
  getLocalTimeZone, 
  today as todayFunction, 
} from '@internationalized/date';

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
import { DatePicker } from "../ui/date-picker";
import { TimePicker, type TimeValue } from '@/Components/ui/time-picker'

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

// Define schema using Zod with a single validation approach
const schema = toTypedSchema(
  z.object({
    date: z.date({
      required_error: $t("validation.required", { attribute: $t("forms.fields.date") }),
    }),
    time: z.object({
      hour: z.number({ required_error: $t("validation.required", { attribute: $t("forms.fields.time") }) }),
      minute: z.number({ required_error: $t("validation.required", { attribute: $t("forms.fields.time") }) })
        .min(0, $t("validation.min.numeric", { attribute: $t("forms.fields.time"), min: 0 }))
        .max(59, $t("validation.max.numeric", { attribute: $t("forms.fields.time"), max: 59 })),
    }, { 
      required_error: $t("validation.required", { attribute: $t("forms.fields.time") })
    }),
    type_id: z.number({
      required_error: $t("validation.required", { attribute: $tChoice("forms.fields.type", 0) }),
    }),
  })
);

// Compute initial date from meeting's start_time
const meetingDate = computed(() => {
  if (props.meeting?.start_time) {
    return new Date(props.meeting.start_time);
  }
  return undefined;
});

// Determine initial values from meeting prop
const initialValues = computed(() => ({
  date: meetingDate.value,
  time: meetingDate.value ? {
    hour: meetingDate.value.getHours(),
    minute: meetingDate.value.getMinutes()
  } : null,
  type_id: props.meeting?.type_id
}));

// Handle form submission with typed values
const onSubmit = (values) => {
  const dt = new Date(values.date);
  
  // Time is required so should always be present at this point
  dt.setHours(values.time.hour, values.time.minute);
  
  // Format date in local timezone without conversion to UTC
  // This formats as YYYY-MM-DDTHH:mm:ss.sss in local timezone
  const localISOString = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000))
    .toISOString()
    .slice(0, 19)
    .replace('T', ' ');
  
  const formData = {
    start_time: localISOString,
    type_id: values.type_id,
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
    meetingTypes.value = data.filter((type) => type.model_type === "App\\Models\\Meeting");
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
