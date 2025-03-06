<template>
  <section class="pt-8 last:pb-2">
    <!-- <header>
        <NBreadcrumb v-if="navigationItemId != null" class="mb-4 flex w-full">
          <NBreadcrumbItem v-for="breadcrumb in breadcrumbTree" :key="breadcrumb?.parent_id" :clickable="false">
            {{ breadcrumb?.name }}
            <template #separator>
              <NIcon>
                <HatGraduation20Filled />
              </NIcon>
            </template>
</NBreadcrumbItem>
</NBreadcrumb>
</header> -->
    <article class="grid grid-cols-1 gap-x-12" :class="{ 'lg:grid-cols-[1fr_250px]': anchorLinks }">
      <h1 class="col-span-full col-start-1 inline-flex gap-4">
        <span class="text-gray-900 dark:text-white">{{ page.title }}</span>
      </h1>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="typography flex max-w-prose flex-col gap-4 py-4 text-base leading-7">
        <RichContentParser :content="page.content?.parts" />
      </div>
      <aside v-if="anchorLinks" class="sticky top-48 hidden h-fit lg:block">
        <NAnchor ignore-gap :bound="160">
          <template v-for="link in anchorLinks" :key="link.href">
            <NAnchorLink :title="link.title" :href="link.href" />
            <template v-for="child in link.children" :key="child.href">
              <NAnchorLink :title="child.title" :href="child.href" :indent="true" />
            </template>
          </template>
        </NAnchor>
      </aside>
    </article>
  </section>
  <FeedbackPopover />
</template>

<script setup lang="ts">
import FeedbackPopover from "@/Components/Public/FeedbackPopover.vue";
import RichContentParser from "@/Components/RichContentParser.vue";

const props = defineProps<{
  navigationItemId: number;
  page: Record<string, any>;
}>();

//const mainNavigation = usePage().props.mainNavigation;

//const getBreadcrumbTree = (navigationItemId: number) => {
//  const breadcrumbTree = [];
//  while (navigationItemId) {
//    // find array MainNavigation item by navigationItemId and add it to breadcrumbTree
//    const navigationItem = mainNavigation.find(
//      (item) => item.id === navigationItemId,
//    );
//    breadcrumbTree.unshift(navigationItem);
//    navigationItemId = navigationItem?.parent_id;
//  }
//  return breadcrumbTree;
//};
//
//const breadcrumbTree = getBreadcrumbTree(props.navigationItemId);

const anchorLinks = props.page.content?.parts?.reduce((acc: any, part: any) => {
  if (part.type === "tiptap") {
    const partHeadings = part.json_content?.content?.filter(
      (node: any) => node.type === "heading",
    );

    // check for h2 and h3 elements, if h3, nest it under h2
    const headings = partHeadings?.reduce((acc: any, node: any) => {
      if (node.attrs.level === 2) {
        acc.push({
          title: node.content[0].text,
          href: `#${node.attrs.id}`,
          children: [],
        });
      } else if (node.attrs.level === 3) {

        // Sometimes the h3 may come before h2, we need to check for that
        if (acc[acc.length - 1]?.children) {
          acc[acc.length - 1]?.children.push({
            title: node.content[0].text,
            href: `#${node.attrs.id}`,
          });
        } else {
          acc.push({
            title: node.content[0].text,
            href: `#${node.attrs.id}`,
            children: [],
          });
        }
      }
      return acc;
    }, []);

    acc.push(...headings);
  }

  return acc;
}, []);

</script>

<style>
.n-breadcrumb ul {
  display: flex;
  flex-wrap: wrap;
}

/* offset scroll of content on anchor link click which uses h2 and h3 elements */
/* *:target { */
/*   scroll-margin-top: 160px; */
/* } */
</style>
