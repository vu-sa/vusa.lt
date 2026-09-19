<template>
  <Dialog :open @update:open="close">
    <DialogContent class="max-w-xl">
      <DialogHeader>
        <DialogTitle>{{ $t('Sujungti įrašus') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Pasirinkite įrašą, kurį paliksite. Kiti pasirinkti įrašai bus panaikinti, o jų ryšiai perkelti į paliekamą įrašą.') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <div v-if="records.length > 1" class="space-y-2">
          <p class="text-sm font-medium">
            {{ $t('Paliekamas įrašas') }}
          </p>
          <div class="grid gap-2">
            <Button
              v-for="record in records"
              :key="String(record.id)"
              :variant="String(targetId) === String(record.id) ? 'secondary' : 'outline'"
              class="h-auto justify-start px-3 py-2 text-left"
              @click="targetId = record.id"
            >
              <span class="min-w-0">
                <span class="block truncate">{{ record.label }}</span>
                <span v-if="record.context" class="block truncate text-xs font-normal text-muted-foreground">{{ record.context }}</span>
              </span>
            </Button>
          </div>
        </div>

        <template v-else>
          <label class="space-y-2">
            <span class="text-sm font-medium">{{ $t('Sujungti su…') }}</span>
            <Input v-model="query" :placeholder="$t('Pradėkite rašyti pavadinimą')" @input="search" />
          </label>
          <div v-if="isSearching" class="text-sm text-muted-foreground">
            {{ $t('Ieškoma…') }}
          </div>
          <div v-else-if="candidates.length" class="grid gap-2">
            <Button
              v-for="candidate in candidates"
              :key="String(candidate.id)"
              :variant="String(targetId) === String(candidate.id) ? 'secondary' : 'outline'"
              class="h-auto justify-start px-3 py-2 text-left"
              @click="targetId = candidate.id"
            >
              <span class="min-w-0">
                <span class="block truncate">{{ candidate.label }}</span>
                <span v-if="candidate.context" class="block truncate text-xs font-normal text-muted-foreground">{{ candidate.context }}</span>
              </span>
            </Button>
          </div>
        </template>

        <p v-if="targetId" class="rounded-md bg-muted px-3 py-2 text-sm text-muted-foreground">
          {{ $t('Bus paliktas pasirinktas įrašas, o :count kiti įrašai bus sujungti į jį.', { count: sourceIds.length }) }}
        </p>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="close">
          {{ $t('Cancel') }}
        </Button>
        <Button :disabled="!targetId || isSubmitting" @click="submit">
          {{ $t('Sujungti') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

export interface MergeRecord {
  id: string | number;
  label: string;
  context?: string | null;
}

const props = defineProps<{
  open: boolean;
  type: 'users' | 'duties' | 'study-programs' | 'tags';
  records: MergeRecord[];
  submitUrl: string;
  targetField: string;
  sourceField: string;
}>();

const emit = defineEmits<{
  close: [];
  merged: [];
}>();

const query = ref('');
const candidates = ref<MergeRecord[]>([]);
const targetId = ref<string | number | null>(null);
const isSearching = ref(false);
const isSubmitting = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | undefined;

const sourceIds = computed(() => props.records
  .map(record => record.id)
  .filter(id => String(id) !== String(targetId.value)));

watch(() => props.open, (open) => {
  if (!open) {
    return;
  }

  query.value = '';
  candidates.value = [];
  targetId.value = null;
});

const search = () => {
  clearTimeout(searchTimeout);

  if (query.value.trim().length < 2) {
    candidates.value = [];
    return;
  }

  searchTimeout = setTimeout(async () => {
    isSearching.value = true;

    try {
      const url = route('api.v1.admin.mergeCandidates.index', {
        type: props.type,
        source_ids: props.records.map(record => record.id),
        query: query.value.trim(),
      });
      const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
      const body = await response.json() as { data?: MergeRecord[] };
      candidates.value = body.data ?? [];
    }
    finally {
      isSearching.value = false;
    }
  }, 250);
};

const close = () => emit('close');

const submit = () => {
  if (!targetId.value || sourceIds.value.length === 0) {
    return;
  }

  isSubmitting.value = true;
  router.post(props.submitUrl, {
    [props.targetField]: targetId.value,
    [props.sourceField]: sourceIds.value,
  }, {
    preserveScroll: true,
    onSuccess: () => emit('merged'),
    onFinish: () => { isSubmitting.value = false; },
  });
};
</script>
