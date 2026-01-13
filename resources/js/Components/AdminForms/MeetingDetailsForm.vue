<template>
  <div class="space-y-6">
    <Form ref="meetingForm" v-slot="{ values, setFieldValue }" :validation-schema="schema" :initial-values
      @submit="onSubmit">
      <div class="space-y-6 pt-1">
        <!-- Date and Time Selection -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 mb-3">
            <Calendar class="h-4 w-4" />
            <h4 class="font-medium">
              {{ $t('Kada vyks susitikimas?') }}
            </h4>
          </div>

          <FormField v-slot="{ componentField, value }" name="start_time">
            <FormItem>
              <FormLabel>
                {{ $t("forms.fields.date") }} / {{ $t("forms.fields.time") }} <span class="text-destructive">*</span>
              </FormLabel>
              <FormControl>
                <DateTimePicker 
                  class="w-full" 
                  :model-value="componentField.modelValue"
                  @update:model-value="componentField['onUpdate:modelValue']"
                  @blur="(e) => componentField.onBlur(e as Event)"
                  :min-date="minDate" 
                  :max-date="maxDate"
                  :hour-range="[7, 22]" 
                  :minute-step="5" 
                />
              </FormControl>
              <FormDescription v-if="isWeekendTime(value)"
                class="text-amber-600 dark:text-amber-400">
                {{ $t('Pasirinktas savaitgalio laikas') }}
              </FormDescription>
              <FormMessage />
            </FormItem>
          </FormField>
        </div>

        <!-- Meeting Type (Radio Selection) -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 mb-3">
            <component :is="Icons.TYPE" class="h-4 w-4" />
            <h4 class="font-medium">
              {{ $t('Koks posėdžio tipas?') }}
            </h4>
          </div>

          <FormField v-slot="{ componentField }" name="type_id">
            <FormItem>
              <FormLabel>
                {{ $tChoice("forms.fields.type", 0) }} <span class="text-destructive">*</span>
              </FormLabel>
              <FormControl>
                <RadioGroup 
                  :model-value="componentField.modelValue != null ? String(componentField.modelValue) : undefined" 
                  @update:model-value="(val) => componentField['onUpdate:modelValue']?.(Number(val))"
                  :disabled="!props.meetingTypes" 
                  class="space-y-2"
                >
                  <!-- Recommended Types -->
                  <div v-if="recommendedTypes.length > 0" class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">
                      {{ $t('Rekomenduojami') }}
                    </p>
                    <div class="space-y-2 pl-2">
                      <div v-for="type in recommendedTypes" :key="`rec-${type.id}`" class="flex items-center space-x-2">
                        <RadioGroupItem :id="`rec-${type.id}`" :value="String(type.id)" />
                        <Label :for="`rec-${type.id}`" class="flex items-center gap-2 cursor-pointer">
                          <Badge variant="secondary" class="text-xs">{{ $t('Rekomenduojama') }}</Badge>
                          {{ type.title }}
                        </Label>
                      </div>
                    </div>
                  </div>

                  <!-- Other Types -->
                  <div v-if="otherTypes.length > 0" class="space-y-2">
                    <p v-if="recommendedTypes.length > 0" class="text-sm font-medium text-muted-foreground pt-2">
                      {{ $t('Kiti tipai') }}
                    </p>
                    <div class="space-y-2 pl-2">
                      <div v-for="type in otherTypes" :key="type.id" class="flex items-center space-x-2">
                        <RadioGroupItem :id="`type-${type.id}`" :value="String(type.id)" />
                        <Label :for="`type-${type.id}`" class="cursor-pointer">{{ type.title }}</Label>
                      </div>
                    </div>
                  </div>
                </RadioGroup>
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>
        </div>

        <!-- Optional Details -->
        <Collapsible v-model:open="showOptionalDetails">
          <CollapsibleTrigger as-child>
            <Button type="button" variant="ghost" size="sm"
              class="w-full justify-between p-3 border border-dashed bg-muted/40">
              {{ $t('Papildoma informacija') }}
              <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': showOptionalDetails }" />
            </Button>
          </CollapsibleTrigger>

          <CollapsibleContent class="mt-4 space-y-4 p-3 rounded-md bg-muted/30">
            <FormField v-slot="{ componentField }" name="description">
              <FormItem>
                <FormLabel>{{ $t('Aprašymas') }}</FormLabel>
                <FormControl>
                  <Textarea v-bind="componentField" :placeholder="$t('Trumpas posėdžio aprašymas (neprivaloma)')"
                    class="resize-none" rows="3" />
                </FormControl>
                <FormDescription>
                  {{ $t('Papildoma informacija apie posėdį') }}
                </FormDescription>
                <FormMessage />
              </FormItem>
            </FormField>
          </CollapsibleContent>
        </Collapsible>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center gap-2" />

          <div class="flex items-center gap-2">
            <Button type="submit" :disabled="loading || !props.meetingTypes?.length" class="inline-flex items-center gap-2">
              <span v-if="loading" class="inline-flex items-center gap-2">
                <Loader2 class="h-4 w-4 animate-spin" />
                <span>{{ $t('Tikrinama...') }}</span>
              </span>
              <span v-else class="inline-flex items-center gap-2">
                <span>{{ $t('Toliau') }}</span>
                <ArrowRight class="h-4 w-4" />
              </span>
            </Button>
          </div>
        </div>
      </div>
    </Form>
  </div>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { ref, computed, onMounted, useTemplateRef, watch, nextTick } from "vue";
import { Form } from "vee-validate";
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';
import {
  getLocalTimeZone,
  today as todayFunction
} from '@internationalized/date';
import {
  Calendar,
  ChevronDown,
  ArrowRight,
  Loader2
} from "lucide-vue-next";

import Icons from "@/Types/Icons/filled";

// Import Shadcn UI components
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
import { Badge } from "@/Components/ui/badge";
import { Separator } from "@/Components/ui/separator";
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
  FormDescription,
} from "@/Components/ui/form";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/Components/ui/collapsible";
import {
  RadioGroup,
  RadioGroupItem,
} from "@/Components/ui/radio-group";
import { Label } from "@/Components/ui/label";
import { DateTimePicker } from "@/Components/ui/date-picker";

const emit = defineEmits<(event: "submit", form: any) => void>();

const props = defineProps<{
  loading?: boolean;
  meeting: any;
  meetingTypes?: Array<{ id: number, title: string, model_type: string }>;
  institutionId?: string;
}>();

// Local state
const showOptionalDetails = ref(false);
const meetingForm = useTemplateRef<typeof Form>("meetingForm");

const minDate = computed(() => {
  const today = todayFunction(getLocalTimeZone());
  return today;
});

const maxDate = computed(() => {
  const today = todayFunction(getLocalTimeZone());
  return today.add({ years: 1 });
});

const recommendedTypes = computed(() => {
  if (!props.institutionId || !props.meetingTypes) return [];

  // Logic to determine recommended types based on institution
  return props.meetingTypes.filter(type =>
    type.title.toLowerCase().includes('board') ||
    type.title.toLowerCase().includes('valdyb')
  ).slice(0, 2);
});

const otherTypes = computed(() => {
  if (!props.meetingTypes) return [];
  const recommendedIds = recommendedTypes.value.map(t => t.id);
  return props.meetingTypes.filter(type => !recommendedIds.includes(type.id));
});

// Form schema
const schema = toTypedSchema(
  z.object({
    start_time: z.date({
      required_error: $t("validation.required", { attribute: $t("forms.fields.date") }),
    }),
    type_id: z.number({
      required_error: $t("validation.required", { attribute: $tChoice("forms.fields.type", 0) }),
    }),
    description: z.string().optional(),
  })
);

// Initial values
const initialValues = computed(() => {
  const seed = props.meeting || {} as any
  const seedDate = seed.start_time ? new Date(seed.start_time) : undefined

  return {
    start_time: seedDate,
    type_id: seed.type_id || undefined,
    description: seed.description || ''
  }
});

const isWeekendTime = (date: Date | undefined | null): boolean => {
  if (!date) return false;
  const day = date.getDay();
  return day === 0 || day === 6; // Sunday or Saturday
};

const onSubmit = (values: Record<string, any>) => {
  const dt = values.start_time as Date;

  // Format date in local timezone
  const localISOString = new Date(dt.getTime() - (dt.getTimezoneOffset() * 60000))
    .toISOString()
    .slice(0, 19)
    .replace('T', ' ');

  const formData = {
    start_time: localISOString,
    type_id: values.type_id as number,
    description: values.description as string | undefined,
  };

  emit("submit", formData);
};

// Watch for changes to meeting prop and synchronize form state
watch(() => props.meeting, (newMeeting) => {
  if (newMeeting && Object.keys(newMeeting).length > 0) {
    nextTick(() => {
      const seedDate = newMeeting.start_time ? new Date(newMeeting.start_time) : undefined;

      meetingForm.value?.setValues({
        start_time: seedDate,
        type_id: newMeeting.type_id || undefined,
        description: newMeeting.description || ''
      });
    });
  }
}, { deep: true });
</script>
