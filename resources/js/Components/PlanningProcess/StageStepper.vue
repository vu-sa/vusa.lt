<template>
  <nav aria-label="Planavimo etapai">
    <ol class="flex items-center gap-0 w-full overflow-x-auto">
      <li
        v-for="(stage, index) in stages"
        :key="stage.number"
        class="flex items-center flex-1 min-w-0"
      >
        <div class="flex flex-col items-center gap-1 min-w-0 flex-1">
          <div
            class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium shrink-0 transition-colors"
            :class="[
              stage.number < currentStage || currentStage > 5
                ? 'bg-green-500 text-white'
                : stage.number === currentStage
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-muted text-muted-foreground',
            ]"
          >
            <CheckIcon v-if="stage.number < currentStage || currentStage > 5" class="w-4 h-4" />
            <span v-else>{{ stage.number }}</span>
          </div>
          <span
            class="text-xs text-center leading-tight px-1 hidden sm:block"
            :class="[
              currentStage > 5
                ? 'text-green-600 dark:text-green-400'
                : stage.number === currentStage
                  ? 'text-primary font-medium'
                  : stage.number < currentStage
                    ? 'text-green-600 dark:text-green-400'
                    : 'text-muted-foreground',
            ]"
          >
            {{ stage.label }}
          </span>
        </div>

        <div
          v-if="index < stages.length - 1"
          class="flex-1 h-0.5 mx-1 min-w-4 transition-colors"
          :class="stage.number < currentStage || currentStage > 5 ? 'bg-green-400' : 'bg-muted'"
        />
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { Check as CheckIcon } from "lucide-vue-next";

const props = defineProps<{
  currentStage: number;
}>();

const stages = computed(() => [
  { number: 1, label: $t("I. Pasiruošimas") },
  { number: 2, label: $t("II. Susitikimai") },
  { number: 3, label: $t("III. Dokumentai") },
  { number: 4, label: $t("IV. MVT") },
  { number: 5, label: $t("V. Monitoringas") },
]);
</script>
