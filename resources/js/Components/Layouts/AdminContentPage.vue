<template>
  <Head v-if="title">
    <title>{{ $t(title) }}</title>
  </Head>

  <div class="container">
    <!-- Pre-header area -->
    <div class="ml-4 mb-2" v-if="$slots['above-header']">
      <slot name="above-header" />
    </div>

    <!-- Header section with title and actions -->
    <header 
      v-if="title" 
      class="z-10 mb-4 mt-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
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
        <h1 class="my-0 inline-flex items-center gap-3 text-2xl font-semibold tracking-tight">
          <component :is="headingIcon" v-if="headingIcon" class="h-6 w-6" />
          <slot name="title">{{ $t(title) }}</slot>
        </h1>
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
        <div class="ml-auto" v-if="$slots['aside-header']">
          <slot name="aside-header" />
        </div>
      </div>
    </header>

    <slot name="below-header" />
    
    <!-- Optional header divider -->
    <Separator v-if="title && headerDivider" class="mb-6" />

    <!-- Main content layout -->
    <div class="grid gap-6" :class="aside ? 'md:grid-cols-[1fr_280px]' : ''">
      <!-- Main content area -->
      <FadeTransition appear>
        <div class="relative">
          <slot />
        </div>
      </FadeTransition>

      <!-- Aside/sidebar content if needed -->
      <div v-if="aside" class="order-first md:order-last">
        <div class="sticky top-20">
          <slot name="aside-card" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import { ArrowLeft, Plus } from "lucide-vue-next";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import { Separator } from "@/Components/ui/separator";
import { Button } from "@/Components/ui/button";

const props = defineProps<{
  aside?: boolean;
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
