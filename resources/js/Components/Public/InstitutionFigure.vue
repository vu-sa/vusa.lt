<template>
  <div
    class="grid grid-cols-1 gap-8"
    :class="{
      'md:grid-cols-[1fr_2fr]': institution.image_url && !onlyVertical,
    }"
  >
    <img
      v-if="institution.image_url && !imageError"
      :src="institution.image_url"
      :alt="institution.name ?? ''"
      class="h-56 w-full rounded-sm object-cover shadow-lg"
      :class="[imageError ? 'hidden' : '']"
      @error="imageError = true"
    />
    <div class="w-full" :class="[imageError ? 'col-span-full' : '']">
      <div>
        <a
          class="inline-block w-fit"
          :href="
            route('contacts.institution', {
              institution: institution.id,
              subdomain:
                institution.padalinys?.alias === 'vusa'
                  ? 'www'
                  : institution.padalinys?.alias ?? 'www',
              lang: $page.props.app.locale,
            })
          "
        >
          <h2
            class="mb-0 w-fit text-2xl font-black leading-5 text-zinc-800 transition-colors hover:text-vusa-red dark:text-zinc-100"
          >
            {{ institutionName }}
          </h2>
        </a>
      </div>
      <small
        v-for="institutionType in institution.types"
        :key="institutionType.id"
        class="mb-4 block text-zinc-500"
      >
        {{ institutionType.title }}
      </small>
      <slot name="more" />
      <!--TODO: better solution for displaying description or remove completely -->
      <!--<div class="mb-5">
        <NEllipsis
          v-if="isMobile"
          expand-trigger="click"
          line-clamp="3"
          :tooltip="true"
        >
          <p
            class="prose prose-sm dark:prose-invert"
            v-html="institutionDescription"
          />
        </NEllipsis>
        <p
          v-else
          class="prose prose-sm dark:prose-invert"
          v-html="institutionDescription"
        /> 
      </div> -->
    </div>
  </div>
</template>

<script setup lang="tsx">
import { NEllipsis } from "naive-ui";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps<{
  institution: App.Entities.Institution;
  isMobile?: boolean;
  onlyVertical?: boolean;
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
