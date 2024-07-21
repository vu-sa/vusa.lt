<template>
  <section
    class="z-5 relative grid h-9 grid-cols-[min-content,_1fr,_40px] rounded-lg border border-zinc-200/70 bg-stone-50 px-12 dark:border-zinc-800/30 dark:bg-[rgb(23,_23,_25)] md:px-12 lg:px-16 xl:px-28">
    <Link href="/"
      class="my-auto mr-6 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-200 dark:hover:text-vusa-red">
    {{
      $page.props.tenant?.shortname
        ? $t($page.props.tenant?.shortname)
        : "VU SA"
    }}
    </Link>
    <nav ref="secondMenuScrollSection" class="mr-2 inline-flex items-center gap-4 overflow-hidden whitespace-nowrap">
      <MainPageLink v-for="link in $page.props.tenant?.links" :key="link?.id" :main-page-link="link" />
    </nav>
    <div class="my-auto">
      <FadeTransition appear>
        <NButton v-if="arrivedState.right === false" quaternary circle size="tiny" class="right-0 top-0 my-auto"
          @click="scrollSecondMenuToRight">
          <template #icon>
            <IFluentChevronRight16Regular />
          </template>
        </NButton>
      </FadeTransition>
    </div>
  </section>
</template>

<script setup lang="tsx">
import { Link } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { useScroll } from "@vueuse/core";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MainPageLink from "./MainPageLink.vue";

defineProps<{
  links: any;
}>();

const secondMenuScrollSection = ref<HTMLElement | null>(null);

const scrollSecondMenuToRight = () => {
  secondMenuScrollSection.value?.scrollBy({
    left: 150,
    behavior: "smooth",
  });
};

const { arrivedState, measure } = useScroll(secondMenuScrollSection);
window.addEventListener("resize", measure);

onMounted(() => {
  setTimeout(() => {
    measure();
  }, 100);
});
</script>
