<template>
  <Dialog v-model:open="open">
    <DialogContent class="max-w-sm">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Link2 class="size-4" aria-hidden="true" />
          {{ $t('Pridėti instituciją') }}
        </DialogTitle>
        <DialogDescription class="mt-2 text-sm text-muted-foreground">
          {{ $t('Kartais atstovavimo organai posėdžiauja kartu. Jei šis posėdis bendras su kitomis institucijomis, pasirink jas iš susijusių.') }}
        </DialogDescription>
      </DialogHeader>
      <div class="space-y-4 pt-2">
        <Skeleton v-if="availableInstitutions === undefined" class="h-10 w-full" />
        <Select v-else v-model="institutionId">
          <SelectTrigger>
            <SelectValue :placeholder="$t('Pasirink instituciją')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in options" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
        <p v-if="availableInstitutions !== undefined && options.length === 0" class="text-sm text-muted-foreground">
          {{ $t('Nėra galimų institucijų pridėti.') }}
        </p>
        <div class="flex justify-end gap-2">
          <Button variant="outline" size="sm" @click="open = false">
            {{ $t('Atšaukti') }}
          </Button>
          <Button size="sm" :disabled="!institutionId" @click="attach">
            {{ $t('Pridėti') }}
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Link2 } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Skeleton } from '@/Components/ui/skeleton';
import type { AttachableMeetingInstitution } from '@/Types/MeetingPage';

/** Joins another related institution to a meeting held jointly. */
const props = defineProps<{
  meetingId: string;
  attachedIds: string[];
  /** Undefined while the page loads the optional prop. */
  availableInstitutions?: AttachableMeetingInstitution[];
}>();

const open = defineModel<boolean>('open', { required: true });

const institutionId = ref('');

const options = computed(() => {
  const attached = new Set(props.attachedIds);
  return (props.availableInstitutions ?? [])
    .filter(institution => !attached.has(institution.id))
    .map(institution => ({
      label: institution.tenant_shortname ? `${institution.name} (${institution.tenant_shortname})` : institution.name,
      value: institution.id,
    }));
});

function attach(): void {
  if (!institutionId.value) return;
  // Institutions decide which pending documents can be linked, and who the tasks reach.
  router.post(route('meetings.institutions.attach', props.meetingId), {
    institution_id: institutionId.value,
  }, {
    preserveScroll: true,
    except: ['documents'],
    onSuccess: () => {
      open.value = false;
      institutionId.value = '';
    },
  });
}
</script>
