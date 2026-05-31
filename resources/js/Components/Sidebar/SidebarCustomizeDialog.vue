<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('Pritaikyti šoninę juostą') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Pasirink, kurios sekcijos rodomos šoninėje juostoje, ir nutempk, kad pakeistum jų tvarką. Logotipas, paskyros meniu ir pagrindinė navigacija visada matomi.') }}
        </DialogDescription>
      </DialogHeader>

      <div class="py-2">
        <div ref="listEl" class="grid gap-1">
          <div
            v-for="key in orderList" :key="key"
            class="flex items-center gap-2 rounded-md px-2 py-2.5 hover:bg-muted/50 transition-colors">
            <GripVertical class="handle size-4 shrink-0 cursor-grab text-muted-foreground active:cursor-grabbing" />
            <component :is="sectionMeta[key].icon" class="size-4 shrink-0 text-muted-foreground" />
            <Label :for="`section-${key}`" class="flex-1 cursor-pointer text-sm font-normal">
              {{ $t(sectionMeta[key].label) }}
            </Label>

            <Switch
              :id="`section-${key}`"
              :model-value="isSectionVisible(key)"
              @update:model-value="(value: boolean) => setSectionVisibility(key, value)" />
          </div>
        </div>
      </div>

      <!-- Appearance -->
      <div class="border-t pt-3">
        <div class="flex items-center gap-2 rounded-md px-2 py-2.5">
          <Rows3 class="size-4 shrink-0 text-muted-foreground" />
          <div class="flex-1">
            <Label for="density-compact" class="cursor-pointer text-sm font-normal">
              {{ $t('Kompaktiškas vaizdas') }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('Sumažina tarpus tarp elementų.') }}
            </p>
          </div>
          <Switch
            id="density-compact"
            :model-value="density === 'compact'"
            @update:model-value="(value: boolean) => setDensity(value ? 'compact' : 'comfortable')" />
        </div>
      </div>

      <DialogFooter>
        <Button variant="ghost" size="sm" @click="handleReset">
          {{ $t('Atstatyti numatytuosius') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, type Component } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Zap,
  Star,
  Radio,
  LifeBuoy,
  History,
  GripVertical,
  Minus,
  Pin,
  Rows3,
} from 'lucide-vue-next';

import { useUIPreferences, type ToggleableSection } from '@/Composables/useUIPreferences';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<(e: 'update:open', value: boolean) => void>();

const {
  isSectionVisible,
  setSectionVisibility,
  orderedSections,
  setSectionOrder,
  resetSections,
  density,
  setDensity,
} = useUIPreferences();

const sectionMeta: Record<ToggleableSection, { label: string; icon: Component }> = {
  quick_actions: { label: 'Greiti veiksmai', icon: Zap },
  pinned: { label: 'Prisegti', icon: Pin },
  recently_visited: { label: 'Neseniai aplankyta', icon: History },
  followed_institutions: { label: 'Sekamos institucijos', icon: Star },
  spacer: { label: 'Tarpas', icon: Minus },
  start_fm: { label: 'START FM', icon: Radio },
  secondary: { label: 'Pagalba', icon: LifeBuoy },
};

// Local, drag-mutable copy of the order. useSortable reorders this array in
// place. We deliberately do NOT continuously watch `orderedSections` here:
// doing so resets the list immediately after a drop and the items snap back.
// The list is re-seeded from the source of truth only when the dialog opens.
const orderList = ref<ToggleableSection[]>([...orderedSections.value]);

const listEl = ref<HTMLElement | null>(null);

// Native HTML5 drag-and-drop (no forceFallback): the fallback clone is
// appended to <body>, which reka-ui sets to `pointer-events: none` while a
// modal dialog is open — that makes the drag unresponsive. Native DnD works
// inside the dialog. Persist on the next tick so useSortable has finished
// mutating `orderList` from the drop.
const { start, stop } = useSortable(listEl, orderList, {
  handle: '.handle',
  animation: 150,
  onEnd: () => nextTick(() => setSectionOrder([...orderList.value])),
});

// The dialog content is teleported and only mounted while open, so the
// sortable target appears/disappears. Re-seed from the source of truth and
// re-bind sortable whenever the dialog opens.
watch(() => props.open, async (isOpen) => {
  stop();
  if (isOpen) {
    orderList.value = [...orderedSections.value];
    await nextTick();
    if (listEl.value) {
      start();
    }
  }
}, { immediate: true });

const handleReset = () => {
  resetSections();
  orderList.value = [...orderedSections.value];
};
</script>
