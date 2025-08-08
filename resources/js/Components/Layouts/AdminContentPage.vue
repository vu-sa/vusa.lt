<template>
  <Head v-if="title">
    <title>{{ $t(title) }}</title>
  </Head>

  <div class="flex flex-col min-h-full">
    <!-- Pre-header area -->
    <div class="mb-4" v-if="$slots['above-header']">
      <slot name="above-header" />
    </div>

    <!-- Header section with title and actions -->
    <header 
      v-if="title" 
      class="flex items-center justify-between mb-6 gap-6"
    >
      <div class="flex items-center gap-3">
        <!-- Back button (if not an index page) -->
        <Button 
          v-if="!isIndex && backUrl" 
          variant="ghost" 
          size="icon"
          class="h-8 w-8" 
          @click="back"
          aria-label="Go back"
        >
          <ArrowLeft class="h-4 w-4" />
        </Button>

        <!-- Page title with optional icon -->
        <div class="flex items-center gap-3">
          <component :is="headingIcon" v-if="headingIcon" class="h-6 w-6 text-primary" />
          <h1 class="text-2xl font-semibold tracking-tight text-foreground">
            <slot name="title">{{ $t(title) }}</slot>
          </h1>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <!-- Custom create button slot or default create button -->
        <slot name="create-button">
          <Link v-if="isIndex && createUrl" :href="createUrl">
            <Button class="gap-1.5" size="sm">
              <Plus class="h-4 w-4" />
              <span>{{ $t("forms.add") }}</span>
            </Button>
          </Link>
        </slot>

        <!-- Additional header items -->
        <slot name="after-heading" />
        <slot name="aside-header" />
      </div>
    </header>

    <slot name="below-header" />
    
    <!-- Optional header divider -->
    <Separator v-if="title && headerDivider" class="mb-6" />

    <!-- Main content layout -->
    <div class="flex-1">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import { ArrowLeft, Plus } from "lucide-vue-next";

import { Separator } from "@/Components/ui/separator";
import { Button } from "@/Components/ui/button";

const props = defineProps<{
  backUrl?: string;
  createUrl?: string;
  headerDivider?: boolean;
  headingIcon?: any;
  title?: string;
}>();

const isIndex = computed(() => {
  return route().current("*.index");
});

const back = () => window.history.back();
</script>
