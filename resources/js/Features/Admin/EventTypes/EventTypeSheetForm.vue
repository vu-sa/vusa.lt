<template>
  <SheetForm
    v-model:open="open"
    :title="eventType ? $t('Redaguoti renginio tipą') : $t('Naujas renginio tipas')"
    :description="$t('Renginių tipai padeda grupuoti renginius pagal jų pobūdį.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="event-type-name">{{ $t('forms.fields.title') }}</Label>
      <MultiLocaleInput id="event-type-name" v-model:input="form.name" />
      <p v-if="form.errors.name || form.errors['name.lt']" class="text-sm text-destructive">
        {{ form.errors.name || form.errors['name.lt'] }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="event-type-slug">Slug</Label>
      <Input id="event-type-slug" v-model="form.slug" placeholder="Pvz: mokymai" />
      <p v-if="form.errors.slug" class="text-sm text-destructive">
        {{ form.errors.slug }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="event-type-description">
        {{ $t('forms.fields.description') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span>
      </Label>
      <MultiLocaleInput
        id="event-type-description"
        v-model:input="form.description"
        input-type="textarea"
      />
      <p v-if="form.errors.description" class="text-sm text-destructive">
        {{ form.errors.description }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="event-type-sort-order">{{ $t('forms.fields.sort_order') }}</Label>
      <Input id="event-type-sort-order" v-model.number="form.sort_order" type="number" min="0" />
      <p v-if="form.errors.sort_order" class="text-sm text-destructive">
        {{ form.errors.sort_order }}
      </p>
    </div>

    <div class="space-y-2 pt-2">
      <div class="flex items-center gap-3">
        <Switch
          id="event-type-is-active"
          :model-value="!!form.is_active"
          @update:model-value="val => form.is_active = val ? 1 : 0"
        />
        <Label for="event-type-is-active" class="cursor-pointer font-medium">
          {{ $t('forms.fields.is_active') }}
        </Label>
      </div>
      <p v-if="form.errors.is_active" class="text-sm text-destructive">
        {{ form.errors.is_active }}
      </p>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, toRaw, watch } from 'vue';

import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

interface EventTypeInput {
  id?: string | number;
  name: { lt: string; en: string };
  slug: string;
  description: { lt: string; en: string } | null;
  sort_order: number;
  is_active: number | boolean;
}

const props = defineProps<{
  open: boolean;
  eventType?: EventTypeInput | null;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  'saved': [];
}>();

const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value),
});

const blank = (): EventTypeInput => ({
  name: { lt: '', en: '' },
  slug: '',
  description: { lt: '', en: '' },
  sort_order: 0,
  is_active: 1,
});

const form = useForm<EventTypeInput>(blank());

watch(() => props.eventType, (eventType) => {
  if (eventType) {
    const raw = structuredClone(toRaw(eventType));
    form.defaults({
      id: raw.id,
      name: typeof raw.name === 'object' && raw.name ? raw.name : { lt: String(raw.name ?? ''), en: '' },
      slug: raw.slug ?? '',
      description: typeof raw.description === 'object' && raw.description ? raw.description : { lt: String(raw.description ?? ''), en: '' },
      sort_order: raw.sort_order ?? 0,
      is_active: raw.is_active ? 1 : 0,
    });
  }
  else {
    form.defaults(blank());
  }
  form.reset();
  form.clearErrors();
}, { immediate: true });

function reset(): void {
  form.reset();
  form.clearErrors();
}

function submit(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
      emit('saved');
    },
  };

  if (props.eventType?.id) {
    form.patch(route('eventTypes.update', props.eventType.id), options);
  }
  else {
    form.post(route('eventTypes.store'), options);
  }
}
</script>
