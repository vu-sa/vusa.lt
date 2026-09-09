<template>
  <SectionCard v-if="props.urls.length" :title="$t('Ankstesni vieši adresai')" :icon="Link2" :count="props.urls.length">
    <ul class="divide-y divide-border">
      <li v-for="row in props.urls" :key="row.id" class="flex items-center justify-between gap-2 py-1.5 first:pt-0 last:pb-0">
        <FormLinkButton :url="row.url" :label="row.locale.toUpperCase()" />
        <Button
          variant="ghost"
          size="icon-sm"
          class="shrink-0 text-muted-foreground hover:text-destructive"
          :aria-label="$t('Ištrinti')"
          data-testid="delete-public-url"
          @click="deleteWithInertia(props.destroyRoute(row.id))"
        >
          <Trash2 class="h-4 w-4" />
        </Button>
      </li>
    </ul>

    <DeleteConfirmationDialog
      v-model:is-open="isOpen"
      :title
      :message
      :is-deleting
      @confirm="executeDelete"
      @cancel="cancelDelete"
    />
  </SectionCard>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link2, Trash2 } from 'lucide-vue-next';

import FormLinkButton from './FormLinkButton.vue';

import { SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import DeleteConfirmationDialog from '@/Components/Dialogs/DeleteConfirmationDialog.vue';
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';

export interface PublicUrlRow {
  id: number;
  url: string;
  locale: string;
  created_at: string;
}

const props = defineProps<{
  /** Every row here is a retired permalink — the live URL is never stored, only computed and
   *  shown via the "Public" link in FormStatusHeader. */
  urls: PublicUrlRow[];
  /** Builds the destroy route for a given row id — kept caller-supplied so this stays entity-agnostic. */
  destroyRoute: (id: number) => string;
}>();

const { isOpen, isDeleting, title, message, executeDelete, cancelDelete, deleteWithInertia } = useDeleteConfirmation({
  title: $t('Ištrinti šį adresą?'),
  message: $t('Šis adresas nustos veikti ir nukreipti į dabartinį puslapį. Šio veiksmo negalima atšaukti.'),
});
</script>
