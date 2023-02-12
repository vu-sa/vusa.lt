<template>
  <figure class="grid grid-cols-[1fr_2fr] gap-8">
    <img
      :src="institution.image_url"
      alt="institution image"
      class="h-36 w-full rounded-md object-cover shadow-sm"
      :class="[imageError ? 'hidden' : '']"
      @error="imageError = true"
    />
    <header class="w-full" :class="[imageError ? 'col-span-full' : '']">
      <figcaption>
        <a
          :href="
            route('contacts.alias', {
              alias: institution.alias,
              padalinys: institution.alias,
              lang: $page.props.app.locale,
            })
          "
        >
          <h2 class="mb-0 font-black transition-colors hover:text-vusa-red">
            {{ institutionName }}
          </h2>
        </a>
        <small
          v-for="institutionType in institution.types"
          :key="institutionType.id"
          class="text-zinc-500"
        >
          {{ institutionType.title }}
        </small>
        <div class="mt-3 flex flex-wrap gap-2">
          <a
            v-for="section in padaliniaiSections"
            :key="section.alias"
            :href="
              route('contacts.alias', {
                alias: section.alias,
                padalinys: institution.alias,
                lang: $page.props.app.locale,
              })
            "
          >
            <NButton round size="tiny">{{ $t(section.title) }}</NButton>
          </a>
        </div>
      </figcaption>
    </header>
  </figure>
</template>

<script setup lang="tsx">
import { Link, usePage } from "@inertiajs/vue3";
import { NButton } from "naive-ui";
import { computed, ref } from "vue";

const props = defineProps<{
  institution: App.Entities.Institution;
}>();

const imageError = ref(false);

const institutionName = computed(() => {
  const locale = usePage().props.app.locale;

  if (locale === "en") {
    return (
      props.institution.extra_attributes?.en?.short_name ??
      props.institution.extra_attributes?.en?.name ??
      props.institution.short_name ??
      props.institution.name
    );
  }

  return props.institution.short_name ?? "";
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
