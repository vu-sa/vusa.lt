<template>
  <PageContent
    :title="
      $tChoice('forms.new_model', 0, {
        model: $tChoice('entities.planningProcess.model', 1),
      })
    "
    :back-url="route('planningProcesses.index')"
    :heading-icon="CalendarLtr24Regular"
  >
    <UpsertModelLayout>
      <form @submit.prevent="submit">
        <div class="flex flex-col gap-6">
          <div class="grid gap-2">
            <Label for="tenant_id">{{ capitalize($tChoice("entities.tenant.model", 1)) }}</Label>
            <Select v-model="form.tenant_id">
              <SelectTrigger id="tenant_id">
                <SelectValue :placeholder="$t('Pasirinkite padalinį')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                  {{ tenant.shortname }}
                </SelectItem>
              </SelectContent>
            </Select>
            <span v-if="form.errors.tenant_id" class="text-destructive text-sm">
              {{ form.errors.tenant_id }}
            </span>
          </div>

          <div class="grid gap-2">
            <Label for="academic_year_start">{{ capitalize($t("entities.planningProcess.academic_year")) }}</Label>
            <Input
              id="academic_year_start"
              v-model.number="form.academic_year_start"
              type="number"
              :min="2020"
              :max="2100"
            />
            <span v-if="form.errors.academic_year_start" class="text-destructive text-sm">
              {{ form.errors.academic_year_start }}
            </span>
          </div>

          <div class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="outline" as-child>
              <Link :href="route('planningProcesses.index')">{{ $t("Atšaukti") }}</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              {{ $t("Sukurti") }}
            </Button>
          </div>
        </div>
      </form>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm, Link } from "@inertiajs/vue3";
import { transChoice as $tChoice, trans as $t } from "laravel-vue-i18n";
import { capitalize } from "vue";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";

defineProps<{
  tenants: App.Entities.Tenant[];
}>();

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm(
    $tChoice("entities.planningProcess.model", 2),
    "planningProcesses.index",
    $tChoice("forms.new_model", 0, { model: $tChoice("entities.planningProcess.model", 1) }),
    Icons.PLANNING_PROCESS,
  )
);

const form = useForm({
  tenant_id: null as number | null,
  academic_year_start: new Date().getFullYear() as number,
  moderator_user_id: null as string | null,
});

const submit = () => {
  form.post(route("planningProcesses.store"), { preserveScroll: true });
};
</script>
