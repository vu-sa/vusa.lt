<template>
  <PageContent>
  <Head :title="$t('Pradinis')" />

    <section class="mb-8">
      <h2 class="mb-4 flex items-center gap-2">
        <IFluentLightbulbFilament24Filled class="text-vusa-yellow" />
        <span>{{ $t("Greitieji veiksmai")
          }}</span>
      </h2>
      <div class="flex flex-wrap items-center gap-4">
        <QActCreateMeeting />
        <QActSurveyButton />
        <QActFocusGroupButton />
        <Link :href="route('reservations.create')">
        <QuickActionButton>
          {{
            $t("Kurti rezervaciją")
          }}
          <template #icon>
            <Icons.RESERVATION />
          </template>
        </QuickActionButton>
        </Link>
      </div>
    </section>
  </PageContent>
</template>

<script setup lang="tsx">
import { Head, Link } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/filled";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import QActCreateMeeting from "@/Components/Buttons/QActCreateMeeting.vue";
import QActFocusGroupButton from "@/Components/Buttons/QActFocusGroupButton.vue";
import QActSurveyButton from "@/Components/Buttons/QActSurveyButton.vue";
import QuickActionButton from "@/Components/Buttons/QuickActionButton.vue";

defineProps<{
  currentUser: App.Entities.User;
}>();

const atstovaiPassword = import.meta.env.VITE_ATSTOVAI_PASSWORD ?? "";
const archyvasPassword = import.meta.env.VITE_ARCHYVAS_PASSWORD ?? "";
</script>

<style scoped>
h2 {
  font-weight: 900;
  font-size: 1.25rem;
  line-height: 1.75rem;
  font-kerning: auto;
  /* more compact */
  letter-spacing: -0.02em;
}
</style>
