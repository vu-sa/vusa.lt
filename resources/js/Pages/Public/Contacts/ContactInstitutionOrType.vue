<template>
  <FadeTransition appear>
    <div class="mx-auto max-w-7xl px-8">
      <div class="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6">
        <div class="col-span-full">
          <header
            class="relative w-full"
            :class="[institution.image_url ? 'h-64' : 'h-16']"
          >
            <img
              v-if="institution.image_url"
              :src="institution.image_url"
              :alt="institution.name ?? ''"
              class="h-full w-full rounded-sm object-cover duration-200"
              @error="imageError = true"
            />
            <div
              v-if="institution.image_url"
              class="absolute top-0 h-full w-full bg-gradient-to-b from-transparent to-zinc-900/90"
            ></div>
            <h1
              class="absolute bottom-0 flex gap-2 px-8 py-2"
              :class="{ 'text-zinc-100': institution.image_url }"
            >
              <template v-if="$page.props.app.locale === 'en'">
                {{ institution.extra_attributes?.en?.name ?? institution.name }}
              </template>
              <template v-else>
                {{ institution.name ?? "" }}
              </template>
              <NPopover
                v-if="institutionDescription"
                :scrollable="true"
                style="max-width: 70vw; max-height: 400px"
                :arrow-point-to-center="true"
                ><template #trigger>
                  <NBadge dot processing :offset="[-3, 10]">
                    <NButton size="large" text
                      ><template #icon
                        ><NIcon
                          :class="[
                            institution.image_url
                              ? 'text-zinc-100 transition hover:text-vusa-red'
                              : null,
                          ]"
                          :component="Info24Regular"
                        ></NIcon></template
                    ></NButton>
                  </NBadge>
                </template>
                <p
                  class="prose prose-sm dark:prose-invert"
                  v-html="institutionDescription"
              /></NPopover>
            </h1>
          </header>
          <div>
            <div></div>
          </div>
        </div>
        <!-- <template v-for="duty in institution"> -->
        <ContactCard
          v-for="contact in contacts"
          :key="contact.id"
          :contact="contact"
          :duties="contact.duties"
        >
        </ContactCard>
        <!-- </template> -->
      </div>
    </div>
  </FadeTransition>
</template>

<script setup lang="ts">
import { Head, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import { Info24Regular } from "@vicons/fluent";
import { NBadge, NButton, NIcon, NPopover } from "naive-ui";
import ContactCard from "@/Components/Public/ContactWithPhoto.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const props = defineProps<{
  contacts: Array<App.Entities.User>;
  institution: App.Entities.Institution;
}>();

const imageError = ref(false);

const institutionDescription = computed(() => {
  if (usePage().props.app.locale === "en") {
    return (
      props.institution.extra_attributes?.en?.description ??
      props.institution.description
    );
  }

  return props.institution.description ?? "";
});
</script>
