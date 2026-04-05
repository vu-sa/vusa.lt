<template>
  <footer class="w-full border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900" role="contentinfo">
    <div class="mx-auto max-w-7xl px-4 py-6 md:py-8 md:px-8">
      <div class="grid gap-4 md:gap-6 md:grid-cols-2 lg:grid-cols-3">
        <!-- Organization info -->
        <section class="space-y-3" aria-labelledby="organization-info">
          <h2 id="organization-info" class="text-base font-bold text-zinc-900 dark:text-zinc-100">
            {{ $t("Vilniaus universiteto Studentų atstovybė") }}
          </h2>
          <address class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400 not-italic">
            <p>{{ $t("Įmonės kodas") }}: 193077294</p>
            <p>{{ $t("PVM mokėtojo kodas") }}: LT100015645710</p>
            <p>
              {{ $t("Finansiniais klausimais kreipkitės el. paštu") }}:
              <a href="mailto:saskaitos@vusa.lt" class="text-vusa-red hover:underline">
                saskaitos@vusa.lt
              </a>
            </p>
          </address>
        </section>

        <!-- Social media and StartFM -->
        <section class="space-y-0" aria-labelledby="social-media">
          <h2 id="social-media" class="sr-only">
            {{ $t("accessibility.social_media_and_radio") }}
          </h2>
          <nav aria-label="Social media links" class="flex flex-wrap gap-2">
            <FacebookButton>
              {{ $t("Facebook") }}
            </FacebookButton>
            <InstagramButton>
              {{ $t("Instagram") }}
            </InstagramButton>
          </nav>
          <StartFM size="small">
            Įsijunk StartFM
          </StartFM>
        </section>

        <!-- Contact info -->
        <section class="space-y-3" aria-labelledby="contact-info">
          <h2 id="contact-info" class="text-base font-bold text-zinc-900 dark:text-zinc-100">
            {{ $t("Kontaktai") }}
          </h2>
          <address class="space-y-2 not-italic">
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
              <IFluentPhone20Regular class="flex-shrink-0 w-4 h-4 text-vusa-red" aria-hidden="true" />
              <a href="tel:+37052687144" class="hover:text-vusa-red transition-colors">
                +370 5 268 7144
              </a>
            </div>
            <div class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-400">
              <IFluentBuilding20Regular class="flex-shrink-0 w-4 h-4 text-vusa-red mt-0.5" aria-hidden="true" />
              <div>
                <p>{{ $t("Universiteto g. 3, Observatorijos kiemelis") }}</p>
                <p>{{ $t("01513, Vilnius, Lietuva") }}</p>
              </div>
            </div>
          </address>
        </section>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import FacebookButton from '../Nav/FacebookButton.vue';
import InstagramButton from '../Nav/InstagramButton.vue';
import StartFM from '../Nav/StartFM.vue';
</script>
