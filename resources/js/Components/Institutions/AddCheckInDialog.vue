<template>
  <Dialog :open @update:open="() => emit('close')">
    <DialogContent class="sm:max-w-[520px]">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <CalendarOffIcon class="size-4 text-muted-foreground" />
          {{ $t('Pranešti apie posėdžio nebuvimą') }}
        </DialogTitle>
        <DialogDescription>
          {{ $t('Nurodykite laikotarpį, kada posėdžiai nėra planuojami. Tai padeda sekti atstovavimo organizavimą.') }}
        </DialogDescription>
      </DialogHeader>

      <!-- Info banner explaining what this does -->
      <div class="border border-border bg-secondary p-3">
        <div class="flex gap-3">
          <InfoIcon class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
          <div class="text-sm text-foreground">
            <p class="font-medium">
              {{ $t('Ką tai reiškia?') }}
            </p>
            <p class="mt-1 text-muted-foreground">
              {{ $t('Jei žinote, kad nurodytu laikotarpiu posėdžių nebus (pvz., atostogos, egzaminų sesija), pranešimas padės išvengti nereikalingų priminimų.') }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="props.institutionName" class="flex items-center gap-2 border border-border px-3 py-2">
        <BuildingIcon class="size-4 text-muted-foreground" />
        <span class="text-sm font-medium">{{ props.institutionName }}</span>
      </div>

      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label>{{ $t('Pradžios data') }}</Label>
            <DatePicker v-model="startDate" />
          </div>

          <div class="space-y-2">
            <Label>{{ $t('Pabaigos data') }}</Label>
            <DatePicker v-model="endDate" />
          </div>
        </div>

        <div class="space-y-2">
          <Label>{{ $t('Pastaba (neprivaloma)') }}</Label>
          <Textarea v-model="note" :placeholder="$t('Pvz., atostogų laikotarpis, neveiklus semestro laikas...')"
            rows="3" />
        </div>

        <p class="text-xs text-muted-foreground">
          {{ $t('Pradžios data gali būti bet kuri praeityje. Pabaigos data gali būti iki 3 mėnesių į priekį.') }}
        </p>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="emit('close')">
          {{ $t('Atšaukti') }}
        </Button>
        <Button variant="brand" @click="submit">
          <CalendarOffIcon class="mr-2 size-4" />
          {{ $t('Išsaugoti pranešimą') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { CalendarOff as CalendarOffIcon, Info as InfoIcon, Building as BuildingIcon } from 'lucide-vue-next';

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

const props = defineProps<{
  institutionId: string;
  open: boolean;
  institutionName?: string;
  reloadTenantIds?: Array<string | number>;
  /** Inertia props to reload on success (e.g., ['userInstitutions', 'tenantInstitutions']) */
  reloadProps?: string[];
  /** Optional initial start date (e.g., from drag selection) */
  initialStartDate?: Date;
  /** Optional initial end date (e.g., from drag selection) */
  initialEndDate?: Date;
}>();
const emit = defineEmits<(e: 'close') => void>();

const startDate = ref<Date>(props.initialStartDate ?? new Date(Date.now()));
const endDate = ref<Date>(props.initialEndDate ?? new Date(Date.now() + 14 * 24 * 60 * 60 * 1000));
const note = ref('');

// Update dates when props change (dialog opens with new values)
watch(() => props.initialStartDate, (newVal) => {
  if (newVal) startDate.value = newVal;
});
watch(() => props.initialEndDate, (newVal) => {
  if (newVal) endDate.value = newVal;
});

function formatDate(date: Date): string {
  return date.toISOString().split('T')[0] as string;
}

function submit() {
  router.post(route('institutions.check-ins.store', props.institutionId), {
    start_date: formatDate(startDate.value),
    end_date: formatDate(endDate.value),
    note: note.value || undefined,
  }, {
    onSuccess: () => {
      emit('close');
      // Build reload options with explicit props to refresh
      const reloadOptions: { only?: string[]; data?: Record<string, unknown> } = {};
      if (props.reloadProps && props.reloadProps.length > 0) {
        reloadOptions.only = props.reloadProps;
      }
      if (props.reloadTenantIds && props.reloadTenantIds.length > 0) {
        reloadOptions.data = { tenantIds: props.reloadTenantIds };
      }
      router.reload(reloadOptions);
    },
    preserveScroll: true,
  });
}
</script>
