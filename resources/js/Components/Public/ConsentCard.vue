<template>
  <div class="fixed inset-x-0 bottom-0 z-50 flex justify-center p-4 sm:p-6">
    <section
      data-slot="cookie-consent"
      class="w-full max-w-xl border border-border border-t-2 border-t-brand bg-card p-5 text-card-foreground sm:p-6"
    >
      <div class="flex items-start gap-3">
        <IFluentCookies24Regular class="mt-0.5 size-5 shrink-0 text-brand" />
        <p class="text-sm leading-relaxed text-muted-foreground">
          {{ $t("Naudojame seanso slapukus, kurie yra privalomi tinklalapio veikimui.") }}
          {{ $t("Lankomumo statistiką renkame be slapukų ir be asmens duomenų.") }}
        </p>
      </div>

      <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div v-if="privacyPageUrl" class="flex items-center justify-center sm:justify-start">
          <Button as="a" :href="privacyPageUrl" target="_blank" rel="noopener noreferrer" size="public-sm" variant="brand-outline">
            {{ $t("Privatumo politika") }}
          </Button>
        </div>

        <Button size="public-sm" variant="brand" class="w-full sm:w-auto" @click="acknowledge">
          {{ $t("Supratau") }}
        </Button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Button } from '@/Components/ui/button';
import { useCookieConsent } from '@/Composables/useCookieConsent';

const { acknowledge } = useCookieConsent();

// Resolved server-side from SiteSettings::privacy_page_id, so it already points at the right
// language record. Null when no page is configured — better to hide the link than to send
// visitors to a 404. This used to be a hardcoded `${app.url}/privatumas`.
const privacyPageUrl = computed(() => usePage().props.organization.privacyPageUrl);
</script>
