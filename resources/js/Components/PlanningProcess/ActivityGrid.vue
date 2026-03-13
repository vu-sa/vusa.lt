<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <div>
          <CardTitle class="text-base">{{ $t("IV etapas: Metų veiklos tinklelis (MVT)") }}</CardTitle>
          <CardDescription>{{ $t("Padalinio veiklų sąrašas su lygmenimis") }}</CardDescription>
        </div>
        <Button v-if="canUpdate" size="sm" @click="showAddForm = !showAddForm">
          {{ showAddForm ? $t("Atšaukti") : $t("Pridėti veiklą") }}
        </Button>
      </div>
    </CardHeader>
    <CardContent class="flex flex-col gap-4">
      <!-- Add activity form -->
      <div v-if="showAddForm && canUpdate" class="border rounded-lg p-4 flex flex-col gap-3 bg-muted/30">
        <div class="grid sm:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1">
            <Label>{{ $t("Veiklos pavadinimas") }}</Label>
            <Input v-model="addForm.name" :placeholder="$t('Pavadinimas')" />
          </div>
          <div class="flex flex-col gap-1">
            <Label>{{ $t("Mėnuo") }}</Label>
            <Select v-model="addForm.month">
              <SelectTrigger>
                <SelectValue :placeholder="$t('Pasirinkite mėnesį')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                  {{ m.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="flex flex-col gap-1">
            <Label>{{ $t("Atsakingas asmuo") }}</Label>
            <Input v-model="addForm.responsible_person" :placeholder="$t('Vardas Pavardė')" />
          </div>
          <div class="flex flex-col gap-1">
            <Label>{{ $t("Lygmuo") }}</Label>
            <Select v-model="addForm.level">
              <SelectTrigger>
                <SelectValue :placeholder="$t('Pasirinkite lygmenį')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="level in levelOptions" :key="level.value" :value="level.value">
                  {{ level.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <div class="flex gap-2">
          <Button size="sm" :disabled="addForm.processing || !addForm.name" @click="addActivity">
            {{ $t("Pridėti") }}
          </Button>
        </div>
      </div>

      <!-- Activities list -->
      <div v-if="activities.length === 0" class="text-sm text-muted-foreground">
        {{ $t("Veiklų nėra. Pridėkite pirmąją veiklą.") }}
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="text-left py-2 pr-4 font-medium">{{ $t("Veikla") }}</th>
              <th class="text-left py-2 pr-4 font-medium">{{ $t("Mėnuo") }}</th>
              <th class="text-left py-2 pr-4 font-medium">{{ $t("Atsakingas") }}</th>
              <th class="text-left py-2 pr-4 font-medium">{{ $t("Lygmuo") }}</th>
              <th v-if="canUpdate" class="py-2" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="activity in activities"
              :key="activity.id"
              class="border-b last:border-0 hover:bg-muted/30"
            >
              <!-- Editing row -->
              <template v-if="editingId === activity.id">
                <td class="py-2 pr-2">
                  <Input v-model="editForm.name" class="h-8 text-sm" />
                </td>
                <td class="py-2 pr-2">
                  <Select v-model="editForm.month">
                    <SelectTrigger class="h-8 text-sm">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                        {{ m.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </td>
                <td class="py-2 pr-2">
                  <Input v-model="editForm.responsible_person" class="h-8 text-sm" />
                </td>
                <td class="py-2 pr-2">
                  <Select v-model="editForm.level">
                    <SelectTrigger class="h-8 text-sm">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="level in levelOptions" :key="level.value" :value="level.value">
                        {{ level.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </td>
                <td class="py-2">
                  <div class="flex gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-7 w-7 text-green-600 hover:text-green-700"
                      :disabled="editForm.processing || !editForm.name"
                      @click="saveEdit(activity.id)"
                    >
                      <CheckIcon class="w-3.5 h-3.5" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-7 w-7"
                      @click="cancelEdit"
                    >
                      <XIcon class="w-3.5 h-3.5" />
                    </Button>
                  </div>
                </td>
              </template>

              <!-- Display row -->
              <template v-else>
                <td class="py-2 pr-4">{{ activity.name }}</td>
                <td class="py-2 pr-4">{{ monthName(activity.month) }}</td>
                <td class="py-2 pr-4">{{ activity.responsible_person ?? "—" }}</td>
                <td class="py-2 pr-4">
                  <Badge :variant="levelVariant(activity.level)" class="text-xs">
                    {{ levelLabel(activity.level) }}
                  </Badge>
                </td>
                <td v-if="canUpdate" class="py-2">
                  <div class="flex gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-7 w-7"
                      @click="startEdit(activity)"
                    >
                      <PencilIcon class="w-3.5 h-3.5" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-7 w-7 text-destructive hover:text-destructive"
                      @click="deleteActivity(activity.id)"
                    >
                      <Trash2Icon class="w-3.5 h-3.5" />
                    </Button>
                  </div>
                </td>
              </template>
            </tr>
          </tbody>
        </table>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { Check as CheckIcon, Pencil as PencilIcon, Trash2 as Trash2Icon, X as XIcon } from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  canUpdate: boolean;
}>();

const showAddForm = ref(false);
const editingId = ref<string | null>(null);

const activities = computed(
  () => (props.planningProcess.activities ?? []) as App.Entities.PlanningActivity[]
);

const months = computed(() => [
  { value: 1, label: $t("Sausis") },
  { value: 2, label: $t("Vasaris") },
  { value: 3, label: $t("Kovas") },
  { value: 4, label: $t("Balandis") },
  { value: 5, label: $t("Gegužė") },
  { value: 6, label: $t("Birželis") },
  { value: 7, label: $t("Liepa") },
  { value: 8, label: $t("Rugpjūtis") },
  { value: 9, label: $t("Rugsėjis") },
  { value: 10, label: $t("Spalis") },
  { value: 11, label: $t("Lapkritis") },
  { value: 12, label: $t("Gruodis") },
]);

const levelOptions = computed(() => [
  { value: "padalinio", label: $t("Padalinio") },
  { value: "strateginis", label: $t("Strateginis") },
  { value: "srities", label: $t("Srities") },
]);

const addForm = useForm({
  planning_process_id: props.planningProcess.id,
  name: "",
  month: 9 as number,
  responsible_person: "",
  level: "padalinio" as string,
  order: 0,
});

const editForm = useForm({
  name: "",
  month: 9 as number,
  responsible_person: "",
  level: "padalinio" as string,
});

const monthName = (month: number) => months.value.find((m) => m.value === month)?.label ?? "—";

const levelVariant = (level: string) =>
  ({ padalinio: "secondary", strateginis: "default", srities: "outline" } as const)[level] ??
  "secondary";

const levelLabel = (level: string) =>
  levelOptions.value.find((l) => l.value === level)?.label ?? level;

const addActivity = () => {
  addForm.post(route("planningActivities.store"), {
    preserveScroll: true,
    onSuccess: () => {
      addForm.reset("name", "responsible_person");
      showAddForm.value = false;
    },
  });
};

const startEdit = (activity: App.Entities.PlanningActivity) => {
  editingId.value = activity.id;
  editForm.name = activity.name;
  editForm.month = activity.month;
  editForm.responsible_person = activity.responsible_person ?? "";
  editForm.level = activity.level;
};

const cancelEdit = () => {
  editingId.value = null;
};

const saveEdit = (id: string) => {
  editForm.patch(route("planningActivities.update", id), {
    preserveScroll: true,
    onSuccess: () => {
      editingId.value = null;
    },
  });
};

const deleteActivity = (id: string) => {
  router.delete(route("planningActivities.destroy", id), { preserveScroll: true });
};
</script>
