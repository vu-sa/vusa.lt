<template>
  <SheetForm
    v-model:open="open"
    required-note
    :created-at="eventType?.created_at"
    :updated-at="eventType?.updated_at"
    :title="eventType ? $t('Redaguoti renginio tipą') : $t('Naujas renginio tipas')"
    :description="$t('Renginių tipai padeda grupuoti renginius pagal jų pobūdį.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <FormFieldWrapper
      id="event-type-name"
      :label="$t('forms.fields.title')"
      required
      :error="form.errors.name || form.errors['name.lt']"
    >
      <MultiLocaleInput id="event-type-name" v-model:input="form.name" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="event-type-slug"
      :label="$t('forms.fields.technical_slug')"
      required
      :error="form.errors.slug"
    >
      <Input id="event-type-slug" v-model="form.slug" placeholder="Pvz: mokymai" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="event-type-description"
      :label="$t('forms.fields.description')"
      :error="form.errors.description"
    >
      <MultiLocaleInput
        id="event-type-description"
        v-model:input="form.description"
        input-type="textarea"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="event-type-sort-order"
      :label="$t('forms.fields.sort_order')"
      :error="form.errors.sort_order"
    >
      <Input id="event-type-sort-order" v-model.number="form.sort_order" type="number" min="0" />
    </FormFieldWrapper>

    <div class="space-y-2 pt-2">
      <div class="flex items-center gap-3">
        <Checkbox
          id="event-type-is-active"
          :model-value="Boolean(form.is_active)"
          @update:model-value="val => form.is_active = val === true ? 1 : 0"
        />
        <Label for="event-type-is-active" class="cursor-pointer font-medium">
          {{ $t('forms.fields.is_active') }}
        </Label>
      </div>
      <p v-if="form.errors.is_active" class="text-xs text-destructive">
        {{ form.errors.is_active }}
      </p>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/AdminForms/MultiLocaleInput.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useSheetCrudForm } from '@/Composables/useSheetCrudForm';

interface EventTypeInput {
  created_at?: string | null;
  updated_at?: string | null;
  id?: string | number;
  name: { lt: string; en: string };
  slug: string;
  description: { lt: string; en: string } | null;
  sort_order: number;
  is_active: number | boolean;
}

interface EventTypeFormData {
  id?: string | number;
  name: { lt: string; en: string };
  slug: string;
  description: { lt: string; en: string };
  sort_order: number;
  is_active: number;
}

const props = defineProps<{
  open: boolean;
  eventType?: EventTypeInput | null;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  'saved': [];
}>();

const { open, form, reset, submit } = useSheetCrudForm<EventTypeFormData, EventTypeInput>({
  open: () => props.open,
  onUpdateOpen: value => emit('update:open', value),
  onSaved: () => emit('saved'),
  record: () => props.eventType,
  blank: () => ({
    name: { lt: '', en: '' },
    slug: '',
    description: { lt: '', en: '' },
    sort_order: 0,
    is_active: 1,
  }),
  fromRecord: raw => ({
    id: raw.id,
    name: typeof raw.name === 'object' && raw.name ? raw.name : { lt: String(raw.name ?? ''), en: '' },
    slug: raw.slug ?? '',
    description: typeof raw.description === 'object' && raw.description ? raw.description : { lt: String(raw.description ?? ''), en: '' },
    sort_order: raw.sort_order ?? 0,
    is_active: raw.is_active ? 1 : 0,
  }),
  storeUrl: () => route('eventTypes.store'),
  updateUrl: id => route('eventTypes.update', id),
});
</script>
