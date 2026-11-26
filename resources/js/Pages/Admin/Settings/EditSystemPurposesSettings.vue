<template>
  <FormPage
    :mode="canEdit ? 'edit' : 'view'"
    :title="$t('settings.pages.purposes.title')"
    :lead="$t('settings.purposes.intro')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="form.post(route('settings.purposes.update'), { preserveScroll: true })"
  >
    <FormPanel
      :title="$t('settings.purposes.responsibilities_title')"
      :description="$t('settings.purposes.responsibilities_description')"
      :icon="Compass"
    >
      <FormFieldWrapper
        v-for="purpose in purposes"
        :id="`purpose_${purpose.key}`"
        :key="purpose.key"
        v-slot="{ controlAttrs }"
        :label="purpose.label"
        :hint="purpose.drives"
        :error="form.errors[`purposes.${purpose.key}`]"
      >
        <SpotlightPopover
          v-if="purpose.key === 'reservation_handling' && canEdit"
          class="w-full"
          :title="purpose.label"
          :description="purpose.drives"
          :is-dismissed="reservationSpotlight.isDismissed.value"
          @dismiss="reservationSpotlight.dismiss()"
        >
          <NativeSelect
            v-bind="controlAttrs"
            :id="`purpose_${purpose.key}`"
            v-model="form.purposes[purpose.key]"
            :options="responsibilityOptions"
            :placeholder="$t('settings.purposes.responsibility_none')"
            placeholder-selectable
            @change="reservationSpotlight.dismiss()"
            @pointerdown="reservationSpotlight.dismiss()"
          />
        </SpotlightPopover>
        <NativeSelect
          v-else
          v-bind="controlAttrs"
          :id="`purpose_${purpose.key}`"
          v-model="form.purposes[purpose.key]"
          :options="responsibilityOptions"
          :placeholder="$t('settings.purposes.responsibility_none')"
          placeholder-selectable
        />
      </FormFieldWrapper>
    </FormPanel>

    <FormPanel
      :title="$t('settings.purposes.duty_types_title')"
      :description="$t('settings.purposes.duty_types_description')"
      :icon="Users"
    >
      <FormFieldWrapper
        v-for="part in dutyTypes"
        :id="`duty_type_${part.key}`"
        :key="part.key"
        v-slot="{ controlAttrs }"
        :label="part.label"
        :hint="fallbackHint(part) ?? part.drives"
        :error="form.errors[`duty_types.${part.key}`]"
      >
        <TypeTreeSelect
          v-bind="controlAttrs"
          :id="`duty_type_${part.key}`"
          v-model="form.duty_types[part.key]"
          :types="dutyTypeOptions"
          :placeholder="$t('settings.purposes.responsibility_none')"
          placeholder-selectable
        />
      </FormFieldWrapper>
    </FormPanel>
  </FormPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Compass, Users } from '@lucide/vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import SpotlightPopover from '@/Components/Patterns/SpotlightPopover.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import { NativeSelect } from '@/Components/ui/native-select';
import { TypeTreeSelect } from '@/Components/AdminForms/TypeTree';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

interface Purpose { key: string; label: string; drives: string; responsibility_id: string | null }
interface DutyTypePart { key: string; label: string; drives: string; type_id: number | null; effective_type_id: number | null }

const props = defineProps<{
  canEdit: boolean;
  purposes: Purpose[];
  responsibilities: { id: string; title: string }[];
  dutyTypes: DutyTypePart[];
  dutyTypeOptions: { id: number; title: string; parent_id: number | null }[];
}>();

const reservationSpotlight = useFeatureSpotlight('reservation-handling-v1');

const responsibilityOptions = computed(() => props.responsibilities.map(item => ({ value: item.id, label: item.title })));

const form = useForm({
  purposes: Object.fromEntries(props.purposes.map(purpose => [purpose.key, purpose.responsibility_id])) as Record<string, string | null>,
  duty_types: Object.fromEntries(props.dutyTypes.map(part => [part.key, part.type_id])) as Record<string, number | null>,
});

function fallbackHint(part: DutyTypePart): string | null {
  if (form.duty_types[part.key] !== null) {
    return null;
  }

  return `${part.drives} ${$t('settings.purposes.fallback')}`;
}
</script>
