<template>
  <PageContent :title="$t('settings.pages.cadences.title')" :back-url="route('settings.index')">
    <div class="space-y-6">
      <UpsertModelLayout>
        <AdminForm :model="defaultsForm" @submit:form="submitDefaults">
          <FormElement>
            <template #title>
              {{ $t('cadences.defaults.title') }}
            </template>
            <template #description>
              {{ $t('cadences.defaults.description') }}
            </template>

            <div class="grid gap-4 sm:grid-cols-2">
              <FormFieldWrapper
                id="default_start_month_day"
                :label="$t('cadences.defaults.start_month_day')"
                :error="defaultsForm.errors.default_start_month_day"
              >
                <Input id="default_start_month_day" v-model="defaultsForm.default_start_month_day" placeholder="07-01" />
              </FormFieldWrapper>

              <FormFieldWrapper
                id="default_end_month_day"
                :label="$t('cadences.defaults.end_month_day')"
                :error="defaultsForm.errors.default_end_month_day"
              >
                <Input id="default_end_month_day" v-model="defaultsForm.default_end_month_day" placeholder="06-30" />
              </FormFieldWrapper>
            </div>

            <p class="mt-3 text-sm text-muted-foreground">
              {{ $t('cadences.defaults.preview') }}:
              <span class="font-mono">{{ preview }}</span>
            </p>
          </FormElement>
        </AdminForm>
      </UpsertModelLayout>

      <SectionCard :title="$t('cadences.global.title')" :count="globalCadences.length">
        <template #action>
          <Button type="button" size="xs" variant="outline" :disabled="crud.processing.value" @click="startAdding">
            <Plus class="size-3.5" />
            {{ $t('cadences.actions.add') }}
          </Button>
        </template>

        <p class="mb-3 text-sm text-muted-foreground">
          {{ $t('cadences.global.description') }}
        </p>

        <CadenceList
          :cadences="globalCadences"
          :empty-message="$t('cadences.global.empty')"
          :editing-id="crud.editingId.value"
          :adding="crud.adding.value"
          :processing="crud.processing.value"
          :prefill="prefill"
          @edit="crud.editingId.value = $event"
          @cancel-edit="crud.editingId.value = null"
          @cancel-add="crud.adding.value = false"
          @create="value => crud.create(value, null)"
          @update="crud.update"
          @delete="crud.destroy"
        />
      </SectionCard>

      <!--
        Read-only on purpose: an override belongs to the institution that uses it, so it is
        created and edited there. This is the roll-up that tells you which bodies have one.
      -->
      <SectionCard :title="$t('cadences.overrides.title')" :count="overrideGroups.length">
        <p class="mb-3 text-sm text-muted-foreground">
          {{ $t('cadences.overrides.description') }}
        </p>

        <p v-if="overrideGroups.length === 0" class="py-4 text-center text-sm text-muted-foreground">
          {{ $t('cadences.overrides.empty') }}
        </p>

        <ul v-else class="divide-y divide-border rounded-md border border-border">
          <li v-for="group in overrideGroups" :key="group.institutionId" class="flex flex-wrap items-center justify-between gap-2 p-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium">
                {{ group.institutionName }}
              </p>
              <p class="truncate text-xs text-muted-foreground">
                {{ $t('cadences.overrides.count', { count: group.cadences.length }) }} ·
                {{ group.span }}
              </p>
            </div>

            <Button as-child size="xs" variant="ghost">
              <Link :href="route('institutions.edit', group.institutionId)">
                {{ $t('cadences.overrides.open') }}
                <ExternalLink class="size-3.5" />
              </Link>
            </Button>
          </li>
        </ul>
      </SectionCard>
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, Plus } from 'lucide-vue-next';

import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { CadenceList, prefillFrom, useCadenceCrud, type CadenceDraft, type CadenceRow } from '@/Components/Cadences';
import { SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps<{
  cadences: CadenceRow[];
  settings: {
    default_start_month_day: string;
    default_end_month_day: string;
  };
}>();

const defaultsForm = useForm({ ...props.settings });
const crud = useCadenceCrud(null);
const prefill = ref<CadenceDraft | null>(null);

const globalCadences = computed(() => props.cadences.filter(cadence => cadence.institution_id === null));

const overrideGroups = computed(() => {
  const groups = new Map<string, { institutionId: string; institutionName: string; cadences: CadenceRow[] }>();

  for (const cadence of props.cadences) {
    if (cadence.institution_id === null) continue;

    const existing = groups.get(cadence.institution_id);

    if (existing) {
      existing.cadences.push(cadence);
    } else {
      groups.set(cadence.institution_id, {
        institutionId: cadence.institution_id,
        institutionName: cadence.institution_name ?? cadence.institution_id,
        cadences: [cadence],
      });
    }
  }

  return [...groups.values()].map(group => ({
    ...group,
    span: `${group.cadences[0].start_date} — ${group.cadences[group.cadences.length - 1].end_date}`,
  }));
});

/** Mirrors CadenceSettings::windowFor() so the preview matches what "Add" prefills. */
const preview = computed(() => {
  const year = new Date().getFullYear();
  const start = `${year}-${defaultsForm.default_start_month_day}`;
  const sameYearEnd = `${year}-${defaultsForm.default_end_month_day}`;
  const end = sameYearEnd <= start ? `${year + 1}-${defaultsForm.default_end_month_day}` : sameYearEnd;

  return `${start} → ${end}`;
});

const submitDefaults = () => defaultsForm.post(route('settings.cadences.defaults'), { preserveScroll: true });

function startAdding(): void {
  prefill.value = prefillFrom(globalCadences.value, defaultsForm.data());
  crud.adding.value = true;
}
</script>
