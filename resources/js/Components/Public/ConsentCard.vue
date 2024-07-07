<template>
  <div class="fixed bottom-0 z-50 w-screen">
    <div
      class="mx-auto mb-12 flex size-fit max-w-sm flex-wrap items-center justify-center gap-4 bg-white p-4 shadow-md dark:bg-zinc-700 sm:rounded-md md:max-w-none">
      <p class="typography flex h-fit items-center gap-4 px-4 text-sm">
        <IFluentCookies24Regular />
        <span>
          {{
            $t(
              "Naudojame seanso slapukus, kurie yra privalomi tinklalapio veikimui."
            )
          }}
        </span>
      </p>
      <div class="flex gap-2">
        <a :href="`${$page.props.app.url}/privatumas`" target="_blank">
          <NButton size="small" round secondary>{{
            $t("Privatumo politika")
            }}</NButton>
        </a>

        <NButton size="small" round secondary type="warning" @click="$emit('okayCookieConsent')">{{ $t("Gerai") }}
        </NButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineEmits(["okayCookieConsent"]);
</script>
