<template>
  <div v-if="institution.image_url" class="flex flex-col">
    <img
      class="center mx-auto max-h-40 w-5/6 cursor-pointer rounded-sm bg-cover object-cover transition hover:shadow-lg"
      :src="institution.image_url"
      @click="inertiaVisitOnClick(institution.alias)"
    />
    <h2
      class="mx-auto mt-4 w-fit cursor-pointer text-center font-bold text-gray-900 transition hover:text-vusa-red dark:text-zinc-50"
      @click="inertiaVisitOnClick(institution.alias)"
    >
      {{ institutionName }}
    </h2>
    <div
      v-if="isPadalinys(institution)"
      class="flex flex-wrap justify-center gap-2"
    >
      <NButton
        round
        size="small"
        @click.stop="
          Inertia.visit(
            route('padalinys.contacts.alias', {
              alias: 'koordinatoriai',
              padalinys: institution.alias,
              lang: $page.props.locale,
            })
          )
        "
        >{{ $t("Koordinatoriai") }}</NButton
      >
      <NButton
        round
        size="small"
        @click.stop="
          Inertia.visit(
            route('padalinys.contacts.alias', {
              alias: 'kuratoriai',
              padalinys: institution.alias,
              lang: $page.props.locale,
            })
          )
        "
        >{{ $t("Kuratoriai") }}</NButton
      >
      <NButton
        round
        size="small"
        @click.stop="
          Inertia.visit(
            route('padalinys.contacts.alias', {
              alias: 'studentu-atstovai',
              padalinys: institution.alias,
              lang: $page.props.locale,
            })
          )
        "
        >{{ $t("Studentų atstovai") }}</NButton
      >
    </div>
  </div>
  <div v-else class="flex flex-col justify-center">
    <div>
      <h2
        class="w-full cursor-pointer text-center font-bold text-gray-900 duration-500 hover:text-vusa-red dark:text-zinc-50"
        @click="inertiaVisitOnClick(institution.alias)"
      >
        {{ institutionName }}
      </h2>
      <div
        v-if="isPadalinys(institution)"
        class="mt-4 flex justify-center gap-2"
      >
        <NButton
          round
          size="small"
          @click.stop="
            Inertia.visit(
              route('padalinys.contacts.alias', {
                alias: 'koordinatoriai',
                padalinys: institution.alias,
                lang: $page.props.locale,
              })
            )
          "
          >{{ $t("Koordinatoriai") }}</NButton
        >
        <NButton
          round
          size="small"
          @click.stop="
            Inertia.visit(
              route('padalinys.contacts.alias', {
                alias: 'kuratoriai',
                padalinys: institution.alias,
                lang: $page.props.locale,
              })
            )
          "
          >{{ $t("Kuratoriai") }}</NButton
        >
        <NButton
          round
          size="small"
          @click.stop="
            Inertia.visit(
              route('padalinys.contacts.alias', {
                alias: 'studentu-atstovai',
                padalinys: institution.alias,
                lang: $page.props.locale,
              })
            )
          "
          >{{ $t("Studentų atstovai") }}</NButton
        >
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Inertia } from "@inertiajs/inertia";
import { NButton } from "naive-ui";
import { computed } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

const props = defineProps<{
  institution: App.Models.Institution;
}>();

const isPadalinys = (institution: App.Models.Institution) => {
  // check if institution type is null
  if (institution.type === null) return false;
  return institution.type.alias === "vu-sa-padaliniai";
};

const inertiaVisitOnClick = (alias: string) => {
  Inertia.visit(
    route("main.contacts.alias", {
      alias: alias,
      lang: usePage().props.value.locale,
    })
  );
};

const institutionName = computed(() => {
  const locale = usePage().props.value.locale;

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
</script>
