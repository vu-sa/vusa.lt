<template>
  <Head :title="title" />
  <MetaIcons />
  <FullWindow>
    <MainNavigation></MainNavigation>
    <PageContent class="pt-32"> <slot /> </PageContent
  ></FullWindow>
</template>

<script setup>
import FullWindow from "@/Layouts/Partials/Public/FullWindow.vue";
import MainNavigation from "@/Layouts/Partials/Public/MainNavigation.vue";
import PageContent from "@/Layouts/Partials/Public/PageContent.vue";
import { Head } from "@inertiajs/inertia-vue3";
import MetaIcons from "@/Components/MetaIcons.vue";
</script>
