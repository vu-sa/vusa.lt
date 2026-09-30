<template>
  <div v-if="props.urls.length" class="mt-1">
    <details class="group">
      <summary
        :class="[
          'u-touch inline-flex cursor-pointer items-center gap-1.5',
          'text-xs font-bold uppercase tracking-wide text-foreground/80 hover:text-foreground transition-colors select-none',
        ]"
      >
        <ChevronDown class="size-3.5 transition-transform group-open:rotate-180" />
        <span>{{ $t('Ankstesni vieši adresai') }} ({{ props.urls.length }})</span>
      </summary>

      <div class="mt-2 border border-border bg-background">
        <ul class="divide-y divide-border">
          <li
            v-for="row in props.urls"
            :key="row.id ?? row.url"
            class="flex items-center justify-between gap-3 px-3 py-2 text-xs"
          >
            <div class="flex items-center gap-2 min-w-0">
              <span class="border border-border bg-secondary/60 px-1.5 py-0.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                {{ row.locale.toUpperCase() }}
              </span>
              <a
                :href="row.url"
                target="_blank"
                rel="noopener noreferrer"
                class="truncate font-mono text-muted-foreground hover:text-foreground hover:underline"
              >
                {{ row.url }}
              </a>
            </div>
            <Button
              v-if="row.id"
              variant="ghost"
              size="icon"
              class="shrink-0 text-muted-foreground hover:text-destructive"
              :aria-label="$t('Ištrinti')"
              data-testid="delete-public-url"
              @click="confirmDelete(props.destroyRoute(row.id))"
            >
              <Trash2 class="size-3.5" />
            </Button>
            <span v-else class="shrink-0 text-xs italic text-muted-foreground">
              {{ $t('Pasenusi nuoroda') }}
            </span>
          </li>
        </ul>
      </div>
    </details>

    <ConfirmDialog
      v-model:open="isOpen"
      :title="$t('Ištrinti šį adresą?')"
      :description="$t('Šis adresas nustos veikti ir nukreipti į dabartinį puslapį. Šio veiksmo negalima atšaukti.')"
      :confirm-label="$t('Ištrinti')"
      destructive
      @confirm="executeDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, Trash2 } from 'lucide-vue-next';

import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';

export interface PublicUrlRow {
  id?: number;
  url: string;
  locale: string;
  created_at?: string;
}

const props = defineProps<{
  /** Every row here is a retired permalink — the live URL is never stored, only computed and
   *  shown via the "Public" link in FormStatusHeader. */
  urls: PublicUrlRow[];
  /** Builds the destroy route for a given row id — kept caller-supplied so this stays entity-agnostic. */
  destroyRoute: (id: number) => string;
}>();

const isOpen = ref(false);
const targetDestroyUrl = ref<string | null>(null);

const confirmDelete = (url: string) => {
  targetDestroyUrl.value = url;
  isOpen.value = true;
};

const executeDelete = () => {
  if (targetDestroyUrl.value) {
    router.delete(targetDestroyUrl.value, {
      preserveScroll: true,
    });
    isOpen.value = false;
    targetDestroyUrl.value = null;
  }
};
</script>
