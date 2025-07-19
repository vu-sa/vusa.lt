<template>
  <!-- <SummerCamps v-if="$page.props.app.locale === 'lt'" /> -->
    <RichContentParser class="max-w-full" :content="content?.parts" />
</template>

<script setup lang="ts">
import RichContentParser from "@/Components/RichContentParser.vue";

defineProps<{
  content: unknown;
}>();

// Home page doesn't need breadcrumbs - they're cleared by PublicLayout

//const SummerCamps = defineAsyncComponent(
//  // eslint-disable-next-line no-secrets/no-secrets
//  () => import("@/Components/Public/FullWidth/SummerCamps.vue"),
//);
</script>
