<template>
  <Form ref="meetingFormRef" v-slot="{ values, setFieldValue }" :validation-schema="schema" :initial-values="initialValues"
    @submit="onSubmit">
    <div class="space-y-6">
      <!-- Meeting Type (Radio Selection) -->
      <FormField v-slot="{ componentField }" name="type">
        <FormItem>
          <FormLabel class="inline-flex items-center gap-1">
            <component :is="Icons.TYPE" class="h-4 w-4" />
            {{ $tChoice("forms.fields.type", 0) }}
          </FormLabel>
          <FormControl>
            <RadioGroup 
              :model-value="componentField.modelValue ?? '__null__'" 
              @update:model-value="(val) => setFieldValue('type', val === '__null__' ? null : val)"
              class="space-y-2 mt-2"
            >
              <div class="space-y-2 pl-2">
                <div v-for="typeOption in meetingTypeOptions" :key="typeOption.value ?? 'null'" class="flex items-center space-x-2">
                  <RadioGroupItem :id="`type-${typeOption.value ?? 'null'}`" :value="typeOption.value ?? '__null__'" />
                  <Label :for="`type-${typeOption.value ?? 'null'}`" class="cursor-pointer flex items-center gap-2">
                    {{ typeOption.label }}
                    <span v-if="typeOption.isDateOnly" class="text-xs text-muted-foreground">
                      ({{ $t('tik data') }})
                    </span>
                  </Label>
                </div>
              </div>
            </RadioGroup>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <!-- Date and Time Selection -->
      <FormField v-slot="{ componentField, value }" name="start_time">
        <FormItem>
          <FormLabel class="inline-flex items-center gap-1">
            <component :is="Icons.DATE" class="h-4 w-4" />
            {{ isEmailMeeting(values.type) ? $t("forms.fields.date") : `${$t("forms.fields.date")} / ${$t("forms.fields.time")}` }}
          </FormLabel>
          <FormControl>
            <!-- Date-only picker for email meetings -->
            <DatePicker
              v-if="isEmailMeeting(values.type)"
              class="w-full"
              :model-value="componentField.modelValue"
              @update:model-value="componentField['onUpdate:modelValue']"
            />
            <!-- DateTime picker for other meeting types -->
            <DateTimePicker
              v-else
              class="w-full"
              :model-value="componentField.modelValue"
              @update:model-value="componentField['onUpdate:modelValue']"
              @blur="(e) => componentField.onBlur(e as Event)"
              :hour-range="[7, 22]"
              :minute-step="5"
            />
          </FormControl>
          <FormDescription v-if="isWeekendTime(value)" class="text-amber-600 dark:text-amber-400">
            {{ $t('Pasirinktas savaitgalio laikas') }}
          </FormDescription>
          <FormMessage />
        </FormItem>
      </FormField>

      <!-- Description -->
      <FormField v-slot="{ componentField }" name="description">
        <FormItem>
          <FormLabel class="inline-flex items-center gap-1">
            <component :is="Icons.DESCRIPTION" class="h-4 w-4" />
            {{ $t('Aprašymas') }}
          </FormLabel>
          <FormControl>
            <Textarea v-bind="componentField" :placeholder="$t('Trumpas posėdžio aprašymas (neprivaloma)')"
              class="resize-none" rows="3" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <!-- Submit Button -->
      <Button type="submit" :disabled="loading">
        <Loader2 v-if="loading" class="h-4 w-4 animate-spin mr-2" />
        {{ submitLabel }}
      </Button>
    </div>
  </Form>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { computed, useTemplateRef, watch, nextTick } from "vue";
import { Form } from "vee-validate";
import { Loader2 } from "lucide-vue-next";

import Icons from "@/Types/Icons/filled";
import { useMeetingForm } from "@/Composables/useMeetingForm";

// Import Shadcn UI components
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
  FormDescription,
} from "@/Components/ui/form";
import {
  RadioGroup,
  RadioGroupItem,
} from "@/Components/ui/radio-group";
import { Label } from "@/Components/ui/label";
import { DateTimePicker, DatePicker } from "@/Components/ui/date-picker";

const emit = defineEmits<{
  (event: "submit", form: any): void;
}>();

const props = withDefaults(defineProps<{
  loading?: boolean;
  meeting?: App.Entities.Meeting | Record<string, any>;
  submitLabel?: string;
}>(), {
  submitLabel: undefined,
});

// Use shared meeting form logic
const {
  meetingTypeOptions,
  isEmailMeeting,
  isWeekendTime,
  extendedSchema: schema,
  formatMeetingData,
  getInitialValues,
} = useMeetingForm();

// Local state
const meetingFormRef = useTemplateRef<typeof Form>("meetingFormRef");

// Initial values
const initialValues = computed(() => getInitialValues(props.meeting || {}));

// Submit label
const submitLabel = computed(() => props.submitLabel || $t('Išsaugoti'));

const onSubmit = (values: Record<string, any>) => {
  emit("submit", formatMeetingData(values));
};

// Watch for changes to meeting prop and synchronize form state
watch(() => props.meeting, (newMeeting) => {
  if (newMeeting && Object.keys(newMeeting).length > 0) {
    nextTick(() => {
      meetingFormRef.value?.setValues(getInitialValues(newMeeting));
    });
  }
}, { deep: true });
</script>
