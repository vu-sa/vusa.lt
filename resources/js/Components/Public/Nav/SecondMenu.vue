<template>
  <section
    class="z-5 relative grid w-screen grid-cols-[min-content,_1fr,_40px] bg-neutral-50 px-8 shadow-sm dark:border-b dark:border-zinc-800 dark:bg-zinc-900 md:px-8 lg:px-16 xl:px-28"
  >
    <Link
      href="/"
      class="my-auto mr-6 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-200 dark:hover:text-vusa-red"
      >{{
        $page.props.padalinys?.shortname
          ? $t($page.props.padalinys?.shortname)
          : "VU SA"
      }}
    </Link>
    <nav
      ref="secondMenuScrollSection"
      class="mr-2 inline-flex gap-4 overflow-x-auto whitespace-nowrap py-3"
    >
      <MainPageLink
        v-for="link in $page.props.padalinys?.links"
        :key="link?.id"
        :main-page-link="link"
      ></MainPageLink>
    </nav>
    <div class="my-auto">
      <FadeTransition appear>
        <NButton
          v-if="arrivedState.right === false"
          quaternary
          circle
          size="tiny"
          class="right-0 top-0 my-auto"
          @click="scrollSecondMenuToRight"
        >
          <template #icon>
            <NIcon :component="ChevronRight16Regular"></NIcon>
          </template>
        </NButton>
      </FadeTransition>
    </div>
  </section>
</template>

<script setup lang="tsx">
import { ChevronRight16Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/vue3";
import { NButton, NIcon } from "naive-ui";
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
