<template>
  <div
    class="grid grid-cols-1 gap-8"
    :class="{ 'md:grid-cols-[1fr_2fr]': institution.image_url }"
  >
    <img
      v-if="institution.image_url && !imageError"
      :src="institution.image_url"
      alt="institution image"
      class="h-36 w-full rounded-md object-cover shadow-sm"
      :class="[imageError ? 'hidden' : '']"
      @error="imageError = true"
    />
    <div class="w-full" :class="[imageError ? 'col-span-full' : '']">
      <div>
        <a
          :href="
            route('contacts.institution', {
              institution: institution.id,
              subdomain:
                institution.padalinys?.alias === 'vusa'
                  ? 'www'
                  : institution.padalinys?.alias ?? institution.alias,
              lang: $page.props.app.locale,
            })
          "
        >
          <p
            class="mb-0 text-xl font-black leading-5 text-zinc-800 transition-colors hover:text-vusa-red dark:text-zinc-100"
          >
            {{ institutionName }}
          </p>
        </a>
        <small
          v-for="institutionType in institution.types"
          :key="institutionType.id"
          class="text-zinc-500"
        >
          {{ institutionType.title }}
        </small>
        <div
          v-if="institution.alias === institution.padalinys?.alias"
          class="mt-3 flex flex-wrap gap-2"
        >
          <a
            v-for="section in padaliniaiSections"
            :key="section.alias"
            :href="
              route('contacts.alias', {
                institution: section.alias,
                subdomain:
                  institution.alias === 'vusa' ? 'www' : institution.alias,
                lang: $page.props.app.locale,
              })
            "
          >
            <NButton round size="tiny">{{ $t(section.title) }}</NButton>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="tsx">
import { NButton } from "naive-ui";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps<{
  institution: App.Entities.Institution;
}>();

const imageError = ref(false);

const institutionName = computed(() => {
  const locale = usePage().props.app.locale;

  if (locale === "en") {
    return (
      props.institution.extra_attributes?.en?.name ??
      props.institution.extra_attributes?.en?.short_name ??
      props.institution.name ??
      props.institution.short_name
    );
  }

  return props.institution.name ?? props.institution.short_name ?? "";
});

const padaliniaiSections = [
  {
    title: "Koordinatoriai",
    alias: "koordinatoriai",
  },
  {
    title: "Kuratoriai",
    alias: "kuratoriai",
  },
  {
    title: "Studentų atstovai",
    alias: "studentu-atstovai",
  },
];
</script>
