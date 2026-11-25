<template>
  <div id="duty-former-names" class="-mt-2 space-y-1" data-testid="duty-former-names">
    <FieldHistoryDisclosure v-model:open="open" :label="$t('Ankstesni pavadinimai')" :count="rows.length">
      <li class="px-3 py-2 text-xs text-muted-foreground">
        {{ $t('Seni laikotarpiai rodomi su tuo metu galiojusiu pavadinimu.') }}
      </li>
      <li
        v-for="(former, index) in rows"
        :key="index"
        class="grid gap-2 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_10rem_auto] sm:items-end"
        data-testid="former-name-row"
      >
        <div class="space-y-1">
          <Label :for="`former-name-lt-${index}`" class="flex items-center gap-2 text-xs text-muted-foreground">
            LT
            <span v-if="former.auto" class="border border-border bg-secondary px-1.5 text-xs font-medium text-foreground">{{ $t('Naujas') }}</span>
          </Label>
          <Input :id="`former-name-lt-${index}`" v-model="former.name.lt" :disabled="former.auto" />
        </div>
        <div class="space-y-1">
          <Label :for="`former-name-en-${index}`" class="text-xs text-muted-foreground">EN</Label>
          <Input :id="`former-name-en-${index}`" v-model="former.name.en" :disabled="former.auto" />
        </div>
        <div class="space-y-1">
          <Label class="text-xs text-muted-foreground">{{ $t('Naudotas iki') }}</Label>
          <DatePicker
            :model-value="former.used_until"
            :disabled="former.auto"
            @update:model-value="(value?: Date) => former.used_until = value ? formatDate(value) : ''"
          />
        </div>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          class="u-touch text-destructive"
          :aria-label="$t('Pašalinti')"
          data-testid="former-name-remove"
          @click="emit('remove', index)"
        >
          <Trash2 class="size-3.5" />
        </Button>
      </li>
      <li class="p-2">
        <Button type="button" variant="ghost" size="sm" voice="sentence" class="u-touch" @click="emit('add')">
          <Plus class="size-3.5" />
          {{ $t('Pridėti ankstesnį pavadinimą') }}
        </Button>
      </li>
    </FieldHistoryDisclosure>
    <p v-if="error" class="text-xs text-destructive" role="alert">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Trash2 } from '@lucide/vue';

import { FieldHistoryDisclosure } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatDate } from '@/Utils/dateTime';

import type { FormerNameRow } from './dutyFormRows';

/** The names a duty went by before, so older terms show the name in force then. Edited in place. */
defineProps<{
  rows: FormerNameRow[];
  error?: string;
}>();

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
  add: [];
  remove: [index: number];
}>();
</script>
