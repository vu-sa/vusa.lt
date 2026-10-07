<template>
  <SheetForm
    v-model:open="open"
    :title="link ? $t('Redaguoti ryšį') : $t('Pridėti ryšį')"
    :description="$t('Rodyklė rodo, kas ką mato: šaltinio nariai mato tikslo posėdžius ir darbotvarkes.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :disabled="!link && !form.other_id"
    @cancel="reset"
    @submit="submit"
  >
    <!-- Picking replaces the sheet's content: no dialog inside a dialog. -->
    <div v-if="picking" class="flex min-h-0 flex-col gap-3" data-testid="relationship-link-picker">
      <Button type="button" variant="ghost" size="sm" voice="sentence" class="u-touch self-start" @click="picking = false">
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ $t('Atgal') }}
      </Button>
      <InlineCollectionSelect
        collection="institutions"
        :disabled-ids="new Set([`institutions-${recordId}`])"
        :confirm-label="$t('Pasirinkti')"
        :search-placeholder="$t('Ieškoti institucijos pagal pavadinimą...')"
        @confirm="pickInstitution"
      />
    </div>

    <template v-else>
      <FormFieldWrapper
        id="relationship-other"
        :label="subject === 'institution' ? $t('Institucija') : $t('Institucijos tipas')"
        :error="form.errors.other_institution_id ?? form.errors.other_type_id"
        :required="!link"
      >
        <p v-if="link" class="text-sm font-medium">
          {{ link.other.name }}
        </p>
        <Button
          v-else-if="subject === 'institution'"
          id="relationship-other"
          type="button"
          variant="outline"
          class="u-touch w-full justify-between font-normal"
          @click="picking = true"
        >
          <span class="truncate" :class="{ 'text-muted-foreground': !otherName }">
            {{ otherName || $t('Pasirinkti instituciją…') }}
          </span>
          <ChevronsUpDown class="size-4 opacity-50" aria-hidden="true" />
        </Button>
        <SingleSelect
          v-else
          id="relationship-other"
          v-model="selectedType"
          :options="typeChoices"
          label-field="name"
          :placeholder="$t('Pasirinkti tipą…')"
          :empty-text="$t('Nieko nerasta')"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        v-if="!link"
        id="relationship-direction"
        :label="$t('Kryptis')"
        :hint="form.direction === 'outgoing'
          ? $t(':record nariai matys pasirinktą', { record: recordName })
          : $t('Pasirinktos nariai matys :record', { record: recordName })"
      >
        <FormSegmentedControl
          v-model="form.direction"
          :options="directionOptions"
          :aria-label="$t('Kryptis')"
          test-id-prefix="relationship-direction"
        />
      </FormFieldWrapper>

      <FormFieldWrapper id="relationship-kind" :label="$t('Ryšio pobūdis')" :error="form.errors.kind">
        <NativeSelect id="relationship-kind" v-model="form.kind" :options="kinds" />
      </FormFieldWrapper>

      <div class="flex items-start gap-3">
        <Switch id="relationship-mutual" v-model="form.mutual" class="mt-0.5" />
        <div>
          <Label for="relationship-mutual" class="cursor-pointer font-medium">{{ $t('Abipusis ryšys') }}</Label>
          <p class="text-xs text-muted-foreground">
            {{ $t('Abiejų pusių nariai matys vieni kitų posėdžius ir darbotvarkes.') }}
          </p>
        </div>
      </div>

      <div v-if="subject === 'type' && !link" class="flex items-start gap-3">
        <Switch id="relationship-cross-tenant" v-model="form.cross_tenant" class="mt-0.5" />
        <div>
          <Label for="relationship-cross-tenant" class="cursor-pointer font-medium">{{ $t('Centrinis → padaliniai') }}</Label>
          <p class="text-xs text-muted-foreground">
            {{ $t('Šaltinio tipo institucijos Centriniame biure susiejamos su tikslo tipo institucijomis visuose padaliniuose. Išjungus ryšys galioja tik to paties padalinio viduje.') }}
          </p>
        </div>
      </div>
    </template>
  </SheetForm>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeft, ChevronsUpDown } from 'lucide-vue-next';

import type { RelationKindOption, RelationshipLinkRow, RelationshipSubject, RelationshipTypeOption } from './types';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { SheetForm } from '@/Components/Patterns';
import FormSegmentedControl, { type FormSegmentOption } from '@/Components/Patterns/FormSegmentedControl.vue';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import { Switch } from '@/Components/ui/switch';
import { InlineCollectionSelect } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

const props = defineProps<{
  subject: RelationshipSubject;
  recordId: string;
  recordName: string;
  /** Editing this link; creating when null. */
  link: RelationshipLinkRow | null;
  kinds: RelationKindOption[];
  typeOptions?: RelationshipTypeOption[];
}>();

const open = defineModel<boolean>('open', { required: true });

type Direction = 'outgoing' | 'incoming';

const form = useForm({
  other_id: '',
  direction: 'outgoing' as Direction,
  kind: 'related',
  mutual: false,
  cross_tenant: false,
});

const picking = ref(false);
const otherName = ref('');

const directionOptions = computed<FormSegmentOption<Direction>[]>(() => [
  { value: 'outgoing', label: $t('Ši → kita') },
  { value: 'incoming', label: $t('Kita → ši') },
]);

// The record's own type stays selectable: a self-link relates same-type institutions.
const typeChoices = computed<RelationshipTypeOption[]>(() => (props.typeOptions ?? []).map(option => option.id === props.recordId
  ? { ...option, name: `${option.name} (${$t('to paties tipo institucijos')})` }
  : option));

const selectedType = computed({
  get: () => typeChoices.value.find(option => option.id === form.other_id) ?? null,
  set: (option: RelationshipTypeOption | null) => { form.other_id = option?.id ?? ''; },
});

watch(open, (isOpen) => {
  if (!isOpen) {
    return;
  }

  form.defaults({
    other_id: props.link?.other.id ?? '',
    direction: props.link?.direction ?? 'outgoing',
    kind: props.link?.kind ?? 'related',
    mutual: props.link?.mutual ?? false,
    cross_tenant: props.link?.cross_tenant ?? false,
  });
  reset();
}, { immediate: true });

function reset(): void {
  form.reset();
  form.clearErrors();
  picking.value = false;
  otherName.value = props.link?.other.name ?? '';
}

function pickInstitution(hits: NormalizedSearchHit[]): void {
  form.other_id = hits[0]?.recordId ?? '';
  otherName.value = hits[0]?.title ?? '';
  picking.value = false;
}

function submit(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
    },
  };

  if (props.link) {
    form.transform(data => ({ kind: data.kind, mutual: data.mutual }))
      .patch(route(props.subject === 'institution' ? 'institutionLinks.update' : 'institutionTypeLinks.update', props.link.id), options);

    return;
  }

  if (props.subject === 'institution') {
    form.transform(({ other_id, direction, kind, mutual }) => ({ other_institution_id: other_id, direction, kind, mutual }))
      .post(route('institutions.links.store', props.recordId), options);

    return;
  }

  form.transform(({ other_id, ...data }) => ({ other_type_id: other_id, ...data }))
    .post(route('institutionTypes.links.store', props.recordId), options);
}

</script>
