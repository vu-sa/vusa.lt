<template>
  <DropdownMenu v-if="duties.length > 1 || selected === null">
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        data-testid="author-duty-picker"
        :class="[labelClass, 'transition-colors hover:text-foreground aria-expanded:text-foreground']"
        :aria-label="$t('discussions.author_duty.change', { duty: fullLabel })"
        :title="fullLabel"
      >
        <span class="shrink-0">{{ $t('discussions.author_duty.as') }}</span>
        <span class="min-w-0 truncate">
          <span class="font-semibold text-foreground">{{ selectedName }}</span>
          <span v-if="selectedPlace"> · {{ selectedPlace }}</span>
        </span>
        <span v-if="selected && selected.is_current === false" class="shrink-0">· {{ authorDutyPeriod(selected) }}</span>
        <ChevronDown class="size-3.5 shrink-0" aria-hidden="true" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="max-h-[min(24rem,70dvh)] w-[min(22rem,calc(100vw-2rem))] overflow-y-auto">
      <DropdownMenuLabel class="text-xs font-normal text-muted-foreground">
        {{ $t('discussions.author_duty.hint') }}
      </DropdownMenuLabel>
      <DropdownMenuRadioGroup :model-value="model ?? undefined" @update:model-value="value => model = String(value)">
        <DropdownMenuGroup v-for="group in groups" :key="group.key">
          <DropdownMenuLabel v-if="groups.length > 1" class="pt-2 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
            {{ group.label }}
          </DropdownMenuLabel>
          <DropdownMenuRadioItem
            v-for="duty in group.duties"
            :key="duty.dutiable_id"
            :value="duty.dutiable_id"
            class="items-start py-2 whitespace-normal"
            data-testid="author-duty-option"
          >
            <span class="min-w-0 break-words">
              <span class="block text-sm font-medium">{{ authorDutyName(duty, author) }}</span>
              <span class="block text-xs text-muted-foreground">{{ optionDetails(duty) }}</span>
            </span>
          </DropdownMenuRadioItem>
        </DropdownMenuGroup>
      </DropdownMenuRadioGroup>
    </DropdownMenuContent>
  </DropdownMenu>
  <p v-else :class="labelClass" :title="fullLabel" data-testid="author-duty-label">
    <span class="shrink-0">{{ $t('discussions.author_duty.as') }}</span>
    <span class="min-w-0 truncate">
      <span class="font-semibold text-foreground">{{ selectedName }}</span>
      <span v-if="selectedPlace"> · {{ selectedPlace }}</span>
    </span>
  </p>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from '@lucide/vue';

import { authorDutyName, authorDutyPeriod } from '@/Components/Discussions/authorDuty';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import type { CommentAuthorDuty } from '@/Types/discussions';

const props = defineProps<{ duties: CommentAuthorDuty[] }>();
const model = defineModel<string | null>({ default: null });

const labelClass = 'inline-flex min-w-0 max-w-full items-center gap-1 px-1.5 text-xs text-muted-foreground pointer-coarse:min-h-11';

const author = computed(() => (usePage().props.auth as { user?: { name: string; pronouns?: Record<string, string> | null } } | undefined)?.user ?? null);
// Former duties are never preselected, so someone who only held past ones starts without a duty.
const selected = computed(() => props.duties.find(duty => duty.dutiable_id === model.value) ?? null);
const selectedName = computed(() => (selected.value ? authorDutyName(selected.value, author.value) : $t('discussions.author_duty.none')));
const selectedPlace = computed(() => selected.value?.tenant ?? selected.value?.institution ?? '');

const fullLabel = computed(() => {
  if (!selected.value) return selectedName.value;
  return [selectedName.value, selected.value.institution, selected.value.is_current === false ? authorDutyPeriod(selected.value) : '']
    .filter(Boolean)
    .join(' · ');
});

function optionDetails(duty: CommentAuthorDuty): string {
  return [duty.institution, authorDutyPeriod(duty)].filter(Boolean).join(' · ');
}

const groups = computed(() => [
  { key: 'current', label: $t('discussions.author_duty.current'), duties: props.duties.filter(duty => duty.is_current !== false) },
  { key: 'former', label: $t('discussions.author_duty.former'), duties: props.duties.filter(duty => duty.is_current === false) },
].filter(group => group.duties.length > 0));
</script>
