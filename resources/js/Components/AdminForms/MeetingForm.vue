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
            <Select v-bind="componentField">
              <SelectTrigger>
                <SelectValue :placeholder="$t('Koks posėdžio tipas?')" />
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

      <Button type="submit" :disabled="loading">
        {{ $t("Toliau") }}...
      </Button>
    </div>
  </Form>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { ref, computed, inject } from "vue";
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

const props = defineProps<{
  loading?: boolean;
  meeting: App.Entities.Meeting;
}>();

// Access shared form state from parent component
const formState = inject('meetingFormState');

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

// Determine initial values, using stored state first, then passed prop, then default
const initialValues = {
  // Use state date if available, otherwise use prop or undefined
  date: formState?.meetingData?.date ? new Date(formState.meetingData.date) : 
        props.meeting?.date ? new Date(props.meeting.date) : undefined,
  
  // Use state time if available, otherwise use prop or null
  time: formState?.meetingData?.date ? {
    hour: new Date(formState.meetingData.date).getHours(),
    minute: new Date(formState.meetingData.date).getMinutes()
  } : props.meeting?.date ? {
    hour: new Date(props.meeting.date).getHours(),
    minute: new Date(props.meeting.date).getMinutes()
  } : null,
  
  // Use state type_id if available, otherwise use prop
  type_id: formState?.meetingData?.type_id || props.meeting?.type_id
};

// Handle form submission with typed values
const onSubmit = (values) => {
  const dt = new Date(values.date);
  
  // Time is required so should always be present at this point
  dt.setHours(values.time.hour, values.time.minute);
  
  const formData = {
    start_time: dt.toISOString(),
    type_id: values.type_id,
  };
  
  // Update state
  if (formState) {
    formState.meetingData = formData;
  }
  
  emit("submit", formData);
};

const fetchMeetingTypes = async () => {
  const response = await fetch(route("api.types.index"));
  const data = await response.json();

  return data.filter((type) => type.model_type === "App\\Models\\Meeting");
};

const meetingTypes = await fetchMeetingTypes();
</script>
