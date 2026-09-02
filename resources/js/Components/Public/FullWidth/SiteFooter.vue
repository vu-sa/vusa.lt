<template>
  <footer class="w-full border-t border-border bg-background" role="contentinfo">
    <div class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8 lg:py-16">
      <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
        <!-- Organization info -->
        <section class="space-y-3" aria-labelledby="organization-info">
          <h2 id="organization-info" class="u-eyebrow">
            {{ $t("Vilniaus universiteto Studentų atstovybė") }}
          </h2>
          <address class="space-y-1 text-sm not-italic text-muted-foreground">
            <p>{{ $t("Įmonės kodas") }}: {{ legal.company_code }}</p>
            <p>{{ $t("PVM mokėtojo kodas") }}: {{ legal.vat_code }}</p>
            <p>
              {{ $t("Finansiniais klausimais kreipkitės el. paštu") }}:
              <a :href="`mailto:${contacts.accounting}`" class="text-brand hover:underline">
                {{ contacts.accounting }}
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
          <h2 id="contact-info" class="u-eyebrow">
            {{ $t("Kontaktai") }}
          </h2>
          <address class="space-y-2 not-italic">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <IFluentPhone20Regular class="size-4 shrink-0 text-brand" aria-hidden="true" />
              <a :href="`tel:${contacts.phone}`" class="transition-colors hover:text-brand">
                +370 5 268 7144
              </a>
            </div>
            <div class="flex items-start gap-2 text-sm text-muted-foreground">
              <IFluentBuilding20Regular class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" />
              <div>
                <p>{{ legal.address.street }}</p>
                <p>{{ legal.address.city }}</p>
              </div>
            </div>
          </address>
        </section>
      </div>

      <div class="mt-10 flex justify-end border-t border-border pt-5">
        <button
          type="button"
          class="text-xs text-muted-foreground transition-colors hover:text-brand hover:underline"
          @click="reopen"
        >
          {{ $t("Slapukų nustatymai") }}
        </button>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import FacebookButton from '../Nav/FacebookButton.vue';
import InstagramButton from '../Nav/InstagramButton.vue';
import StartFM from '../Nav/StartFM.vue';

import { useCookieConsent } from '@/Composables/useCookieConsent';

const { reopen } = useCookieConsent();

// Registry details and contact addresses come from config/vusa.php via shared props, so the
// footer, the schema.org payload and the mail templates cannot disagree.
const page = usePage();
const contacts = computed(() => page.props.organization.contacts);
const legal = computed(() => page.props.organization.legal);
</script>
