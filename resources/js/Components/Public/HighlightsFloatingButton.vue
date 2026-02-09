<template>
  <!-- Desktop: Inline sticky sidebar card (visible on lg+) -->
  <aside
    v-if="highlights && highlights.length > 0"
    class="fixed right-6 top-1/3 z-40 hidden w-72 lg:block"
  >
    <div class="rounded-xl border bg-card p-4 shadow-lg">
      <div class="mb-3 flex items-center gap-2 border-b pb-2">
        <LightbulbIcon class="h-5 w-5 text-primary" />
        <h4 class="font-semibold">
          Svarbiausia
        </h4>
      </div>
      <ul class="space-y-3">
        <li
          v-for="(highlight, index) in highlights"
          :key="index"
          class="flex items-start gap-3"
        >
          <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary">
            {{ index + 1 }}
          </span>
          <span class="text-sm leading-relaxed text-foreground">{{ highlight }}</span>
        </li>
      </ul>
    </div>
  </aside>

  <!-- Mobile/Tablet: Floating button that opens a sheet (visible below lg) -->
  <div v-if="highlights && highlights.length > 0" class="fixed bottom-20 right-4 z-50 lg:hidden">
    <Sheet v-model:open="isSheetOpen">
      <SheetTrigger as-child>
        <Button
          variant="default"
          size="icon"
          class="h-12 w-12 rounded-full shadow-lg hover:shadow-xl transition-shadow"
        >
          <LightbulbIcon class="h-5 w-5" />
          <span class="sr-only">Svarbiausia</span>
        </Button>
      </SheetTrigger>
      <SheetContent side="bottom" class="rounded-t-xl">
        <SheetHeader class="text-left">
          <SheetTitle class="flex items-center gap-2">
            <LightbulbIcon class="h-5 w-5 text-primary" />
            Svarbiausia
          </SheetTitle>
        </SheetHeader>
        <div class="mt-4 pb-4">
          <ul class="space-y-4">
            <li
              v-for="(highlight, index) in highlights"
              :key="index"
              class="flex items-start gap-3"
            >
              <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-medium text-primary">
                {{ index + 1 }}
              </span>
              <span class="text-base leading-relaxed text-foreground">{{ highlight }}</span>
            </li>
          </ul>
        </div>
      </SheetContent>
    </Sheet>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { LightbulbIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';

defineProps<{
  highlights?: string[] | null;
}>();

const isSheetOpen = ref(false);
</script>
