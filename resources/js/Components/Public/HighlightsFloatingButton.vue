<template>
  <!-- Desktop: Inline sticky sidebar card (visible on lg+) -->
  <aside
    v-if="highlights && highlights.length > 0"
    class="fixed right-6 top-1/3 z-40 hidden w-72 lg:block"
  >
    <div class="border border-border bg-card p-4">
      <div class="mb-3 flex items-center gap-2 border-b pb-2">
        <IFluentLightbulb24Regular class="size-5 text-brand" />
        <h4 class="font-semibold">
          {{ $t('Svarbiausia') }}
        </h4>
      </div>
      <ul class="space-y-3">
        <li
          v-for="(highlight, index) in highlights"
          :key="index"
          class="flex items-start gap-3"
        >
          <span class="flex size-6 shrink-0 items-center justify-center bg-secondary text-sm font-medium text-foreground">
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
          voice="brand"
          variant="default"
          size="icon"
          class="size-12 border border-brand bg-brand-fill text-brand-foreground hover:bg-brand-fill/90 dark:hover:bg-brand-fill/90"
        >
          <IFluentLightbulb24Regular class="size-5" />
          <span class="sr-only">{{ $t('Svarbiausia') }}</span>
        </Button>
      </SheetTrigger>
      <SheetContent side="bottom">
        <SheetHeader class="text-left">
          <SheetTitle class="flex items-center gap-2">
            <IFluentLightbulb24Regular class="size-5 text-brand" />
            {{ $t('Svarbiausia') }}
          </SheetTitle>
        </SheetHeader>
        <div class="mt-4 pb-4">
          <ul class="space-y-4">
            <li
              v-for="(highlight, index) in highlights"
              :key="index"
              class="flex items-start gap-3"
            >
              <span class="flex size-7 shrink-0 items-center justify-center bg-secondary text-sm font-medium text-foreground">
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
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import IFluentLightbulb24Regular from '~icons/fluent/lightbulb-24-regular';

defineProps<{
  highlights?: string[] | null;
}>();

const isSheetOpen = ref(false);
</script>
