<template>
  <footer class="w-full border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 py-8 md:px-8">
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <!-- Organization info -->
        <div class="space-y-3">
          <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
            {{ $t("Vilniaus universiteto Studentų atstovybė") }}
          </h3>
          <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
            <p>{{ $t("Įmonės kodas") }}: 193077294</p>
            <p>{{ $t("PVM mokėtojo kodas") }}: LT100015645710</p>
            <p>
              {{$t("Finansiniais klausimais kreipkitės el. paštu")}}:
              <a href="mailto:saskaitos@vusa.lt" class="text-vusa-red hover:underline">
                saskaitos@vusa.lt
              </a>
            </p>
          </div>
        </div>

        <!-- Social media and StartFM -->
        <div class="space-y-2">
          <div class="flex flex-wrap gap-2">
            <FacebookButton>
              {{ $t("Facebook") }}
            </FacebookButton>
            <InstagramButton>
              {{ $t("Instagram") }}
            </InstagramButton>
          </div>
          <StartFM size="small">
            Įsijunk StartFM
          </StartFM>
        </div>

        <!-- Contact info -->
        <div class="space-y-2">
          <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
            {{ $t("Kontaktai") }}
          </h3>
          <div class="space-y-2">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
              <IFluentPhone20Regular class="flex-shrink-0 w-4 h-4 text-vusa-red" />
              <a href="tel:+37052687144" class="hover:text-vusa-red transition-colors">
                +370 5 268 7144
              </a>
            </div>
            <div class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-400">
              <IFluentBuilding20Regular class="flex-shrink-0 w-4 h-4 text-vusa-red mt-0.5" />
              <div>
                <p>{{ $t("Universiteto g. 3, Observatorijos kiemelis") }}</p>
                <p>{{ $t("01513, Vilnius, Lietuva") }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import FacebookButton from "../Nav/FacebookButton.vue";
import InstagramButton from "../Nav/InstagramButton.vue";
import StartFM from "../Nav/StartFM.vue";
</script>
