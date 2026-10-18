<template>
  <div class="divide-y divide-border border-y border-border" data-slot="checklist-items">
    <details
      v-for="(item, index) in items"
      :id="`onboarding-${item.key}`"
      :key="item.key"
      :open="expanded === item.key"
      :data-item="item.key"
      @toggle="onToggle(item.key, $event)"
    >
      <summary class="flex min-h-11 cursor-pointer list-none items-start gap-3 py-4 focus-visible:outline-2 focus-visible:outline-ring">
        <Check v-if="item.status === 'done'" class="mt-0.5 size-5 shrink-0 text-status-success" aria-hidden="true" />
        <Minus v-else-if="item.status === 'skipped'" class="mt-0.5 size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
        <span v-else class="w-5 shrink-0 text-sm tabular-nums text-muted-foreground" aria-hidden="true">{{ index + 1 }}</span>
        <span class="min-w-0 flex-1 font-semibold">{{ $t(item.title) }}</span>
        <span v-if="item.status !== 'pending'" class="shrink-0 text-xs text-muted-foreground">{{ $t(`onboarding.${item.status}`) }}</span>
        <ChevronDown class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
      </summary>
      <div class="space-y-4 pb-6 pl-8">
        <p class="max-w-prose text-sm leading-relaxed text-muted-foreground">{{ $t(item.description) }}</p>
        <Button
          v-if="item.href"
          class="h-auto min-h-11 max-w-full whitespace-normal text-left"
          as-child
          variant="outline"
          voice="sentence"
        >
          <Link :href="item.href">{{ $t(item.actionLabel) }}<ArrowUpRight class="size-4" aria-hidden="true" /></Link>
        </Button>
        <slot :name="item.key" :item />
        <div class="flex flex-wrap items-center gap-2">
          <template v-if="item.status === 'pending'">
            <Button
              v-if="item.key !== 'push'"
              class="h-auto min-h-11 max-w-full whitespace-normal text-left"
              variant="outline"
              voice="sentence"
              :disabled="saving"
              :data-testid="`complete-${item.key}`"
              @click="emit('update', item.key, 'done')"
            >
              {{ $t(item.confirmLabel) }}
            </Button>
            <Button
              v-if="item.skipLabel"
              class="h-auto min-h-11 max-w-full whitespace-normal text-left"
              variant="ghost"
              voice="sentence"
              :disabled="saving"
              :data-testid="`skip-${item.key}`"
              @click="emit('update', item.key, 'skipped')"
            >
              {{ $t(item.skipLabel) }}
            </Button>
          </template>
          <Button
            v-else
            class="h-auto min-h-11 max-w-full whitespace-normal text-left"
            variant="ghost"
            voice="sentence"
            :disabled="saving"
            :data-testid="`reopen-${item.key}`"
            @click="emit('update', item.key, 'pending')"
          >
            {{ $t('onboarding.reopen') }}
          </Button>
        </div>
      </div>
    </details>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Check, ChevronDown, Minus } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ref } from 'vue';

import type { ChecklistItem, ChecklistItemStatus } from './types';

import { Button } from '@/Components/ui/button';

const props = defineProps<{ items: ChecklistItem[]; saving: boolean; initialItem?: string }>();
const emit = defineEmits<{ update: [item: string, status: ChecklistItemStatus] }>();
const expanded = ref(props.items.some(item => item.key === props.initialItem)
  ? props.initialItem
  : props.items.find(item => item.status === 'pending')?.key);

function onToggle(key: string, event: Event) {
  if ((event.target as HTMLDetailsElement).open) expanded.value = key;
}
</script>
