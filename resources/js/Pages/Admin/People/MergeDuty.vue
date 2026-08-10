<template>
  <PageContent :title="$t('Pareigybių suliejimas')" :heading-icon="DutyIcon">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="openPreview">
        <FormElement>
          <template #title>
            {{ $t('Sulieti pareigybes') }}
          </template>
          <template #description>
            <div class="typography">
              <p>
                Pasirinkus pareigybes, <strong>visi</strong> priskyrimai, tipai, rolės, ex-officio ryšiai ir padalinių kvotos bus perduoti į tikslinę pareigybę.
                Sutampantys to paties asmens priskyrimai bus sujungti automatiškai.
              </p>
              <p>
                Šis veiksmas yra <strong>iš dalies atstatomas</strong> — sujungtos pareigybės bus perkeltos į šiukšlinę, o ne ištrintos negrįžtamai.
              </p>
            </div>
          </template>

          <FormFieldWrapper id="target_duty_id" label="Tikslinė pareigybė (paliekama)" required>
            <Select v-model="targetIdString">
              <SelectTrigger>
                <SelectValue placeholder="Pasirinkite pareigybę" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="duty in dutyOptions" :key="duty.value" :value="duty.value">
                  <span class="flex items-center gap-2">
                    {{ duty.label }}
                    <span v-if="duty.institutionName" class="text-xs text-muted-foreground">{{ duty.institutionName }}</span>
                    <Badge v-if="duty.tenantShortname" size="tiny" variant="secondary">{{ duty.tenantShortname }}</Badge>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>

          <div v-if="targetDuty" class="flex items-center gap-2 text-xs text-muted-foreground">
            <Switch id="show-all-institutions" v-model="showAllInstitutions" />
            <Label for="show-all-institutions" class="cursor-pointer font-normal">
              Rodyti pareigybes iš visų institucijų (ne tik „{{ targetDuty.institutionName }}“)
            </Label>
          </div>

          <FormFieldWrapper id="source_duty_ids" label="Sujungiamos pareigybės (bus ištrintos)" required>
            <MultiSelect
              v-model="selectedSourceDuties"
              :options="sourceOptions"
              label-field="label"
              value-field="value"
              placeholder="Pasirinkite pareigybes"
            >
              <template #option="{ item }">
                <span class="flex items-center gap-2">
                  {{ item.label }}
                  <span v-if="item.institutionName" class="text-xs text-muted-foreground">{{ item.institutionName }}</span>
                  <Badge size="tiny" variant="outline">{{ item.dutiablesCount }} priskyrimų</Badge>
                </span>
              </template>
            </MultiSelect>
            <p v-if="!targetDuty" class="mt-1 text-xs text-muted-foreground">
              Pirmiausia pasirinkite tikslinę pareigybę.
            </p>
          </FormFieldWrapper>
        </FormElement>
      </AdminForm>

      <AlertDialog v-model:open="previewOpen">
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Patvirtinkite sujungimą</AlertDialogTitle>
            <AlertDialogDescription as-child>
              <div class="space-y-3 text-sm text-foreground">
                <p>
                  Į pareigybę <strong>{{ targetDuty?.label }}</strong> bus perkelta iš:
                </p>
                <ul class="list-disc space-y-1 pl-5">
                  <li v-for="source in selectedSourceDuties" :key="source.value">
                    <strong>{{ source.label }}</strong> — {{ source.dutiablesCount }} priskyrimų (esamų ir buvusių)
                  </li>
                </ul>
                <p class="text-muted-foreground">
                  Taip pat perkeliami: tipai, administracinės rolės, ex-officio ryšiai ir padalinių kvotos.
                  Sutampantys to paties asmens priskyrimai bus automatiškai sujungti į vieną.
                  Sujungtos pareigybės bus perkeltos į šiukšlinę.
                </p>
              </div>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Atšaukti</AlertDialogCancel>
            <AlertDialogAction :disabled="form.processing" @click="submitMerge">
              Sulieti
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/Components/ui/alert-dialog';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { DutyIcon } from '@/Components/icons';
import { resolveTranslatable } from '@/Composables/useDataTableColumns';

interface DutyWithMeta extends App.Entities.Duty {
  institution?: { id: string; name: unknown; tenant?: { shortname: string } | null } | null;
  dutiables_count?: number;
}

const props = defineProps<{
  duties: DutyWithMeta[];
  targetDutyId?: string | null;
}>();

const form = useForm({
  target_duty_id: props.targetDutyId ?? null as string | null,
  source_duty_ids: [] as string[],
});

const targetIdString = computed({
  get: () => form.target_duty_id ?? '',
  set: (val: string) => {
    form.target_duty_id = val || null;
    // Changing the target invalidates any cross-institution source picks.
    form.source_duty_ids = [];
  },
});

interface DutyOption {
  value: string;
  label: string;
  institutionId: string | null;
  institutionName: string | null;
  tenantShortname: string | null;
  dutiablesCount: number;
}

const dutyOptions = computed<DutyOption[]>(() => props.duties.map(duty => ({
  value: duty.id as string,
  label: resolveTranslatable(duty.name) ?? '',
  institutionId: duty.institution?.id ?? null,
  institutionName: duty.institution ? (resolveTranslatable(duty.institution.name) ?? null) : null,
  tenantShortname: duty.institution?.tenant?.shortname ?? null,
  dutiablesCount: duty.dutiables_count ?? 0,
})));

const targetDuty = computed(() => dutyOptions.value.find(d => d.value === form.target_duty_id) ?? null);

const showAllInstitutions = ref(false);

const sourceOptions = computed<DutyOption[]>(() => {
  if (!targetDuty.value) return [];

  return dutyOptions.value.filter((duty) => {
    if (duty.value === targetDuty.value?.value) return false;
    if (showAllInstitutions.value) return true;
    return duty.institutionId === targetDuty.value?.institutionId;
  });
});

const selectedSourceDuties = computed({
  get: () => sourceOptions.value.filter(opt => (form.source_duty_ids as string[]).includes(opt.value)),
  set: (items: DutyOption[]) => { form.source_duty_ids = items.map(item => item.value); },
});

const previewOpen = ref(false);

function openPreview() {
  if (!form.target_duty_id || form.source_duty_ids.length === 0) return;
  previewOpen.value = true;
}

function submitMerge() {
  form.post(route('duties.mergeDuties'), {
    onSuccess: () => {
      previewOpen.value = false;
    },
  });
}
</script>
