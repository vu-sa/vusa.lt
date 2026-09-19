<template>
  <AdminContentPage>
    <Head>
      <title>{{ headTitle }}</title>
    </Head>

    <div class="mx-auto w-full max-w-[90rem]">
      <header class="border-b border-border pb-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div class="flex min-w-0 items-start gap-4">
            <div v-if="$slots.identity" class="shrink-0">
              <slot name="identity" />
            </div>

            <div class="min-w-0 space-y-2">
              <EntityTypeMark :type="entityType" size="md" />
              <div class="flex flex-wrap items-center gap-3">
                <h1 class="u-display min-w-0 text-3xl leading-tight text-foreground lg:text-4xl">
                  {{ title }}
                </h1>
                <StatusBadge v-if="status" :status />
              </div>
              <slot name="subtitle" />
            </div>
          </div>

          <div class="flex shrink-0 flex-wrap items-center gap-2">
            <div v-if="navigation" class="flex items-center border border-border bg-card">
              <Button
                variant="ghost"
                size="icon"
                class="u-touch"
                :disabled="!navigation.previousHref"
                :aria-label="$t('Ankstesnis įrašas')"
                @click="visit(navigation.previousHref)"
              >
                <ChevronLeft class="size-4" />
              </Button>
              <span class="min-w-20 px-2 text-center text-sm font-medium text-muted-foreground">
                {{ navigation.position }} / {{ navigation.total }}
              </span>
              <Button
                variant="ghost"
                size="icon"
                class="u-touch"
                :disabled="!navigation.nextHref"
                :aria-label="$t('Kitas įrašas')"
                @click="visit(navigation.nextHref)"
              >
                <ChevronRight class="size-4" />
              </Button>
            </div>

            <ActionControl
              v-if="primaryAction"
              :action="primaryAction"
              primary
              @select="emit('action', $event)"
            />

            <DropdownMenu v-if="overflowActions.length" class="hidden md:block">
              <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon" class="u-touch" :aria-label="$t('Daugiau veiksmų')">
                  <MoreHorizontal class="size-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  v-for="action in overflowActions"
                  :key="action.key"
                  :class="action.destructive ? 'text-destructive focus:text-destructive' : undefined"
                  @select="emit('action', action.key)"
                >
                  <component :is="action.icon" v-if="action.icon" class="mr-2 size-4" />
                  {{ action.label }}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

            <Sheet v-if="overflowActions.length">
              <SheetTrigger as-child>
                <Button variant="outline" size="icon" class="u-touch md:hidden" :aria-label="$t('Daugiau veiksmų')">
                  <MoreHorizontal class="size-4" />
                </Button>
              </SheetTrigger>
              <SheetContent side="bottom" class="max-h-[85vh]">
                <SheetHeader>
                  <SheetTitle>{{ $t('Veiksmai') }}</SheetTitle>
                </SheetHeader>
                <div class="grid gap-2 px-4 pb-6">
                  <SheetClose v-for="action in overflowActions" :key="action.key" as-child>
                    <Button
                      variant="ghost"
                      class="u-touch justify-start"
                      :class="action.destructive ? 'text-destructive' : undefined"
                      @click="emit('action', action.key)"
                    >
                      <component :is="action.icon" v-if="action.icon" class="mr-2 size-4" />
                      {{ action.label }}
                    </Button>
                  </SheetClose>
                </div>
              </SheetContent>
            </Sheet>
          </div>
        </div>
      </header>

      <dl v-if="facts.length" class="grid border-b border-border sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div
          v-for="fact in facts"
          :key="fact.key"
          class="min-w-0 border-b border-border px-4 py-4 last:border-b-0 sm:even:border-l lg:border-b-0 lg:border-l lg:first:border-l-0"
        >
          <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ fact.label }}
          </dt>
          <dd class="mt-1 min-w-0 text-sm font-medium text-foreground">
            <slot :name="`fact-${fact.key}`" :fact="fact">
              <a v-if="fact.href" :href="fact.href" class="underline underline-offset-4">
                {{ fact.value }}
              </a>
              <span v-else>{{ fact.value }}</span>
            </slot>
          </dd>
        </div>
      </dl>

      <div v-if="$slots.alert" class="mt-6">
        <slot name="alert" />
      </div>

      <nav v-if="sections.length >= 3" class="mt-8 hidden border-b border-border md:flex" :aria-label="$t('Įrašo skyriai')">
        <button
          v-for="section in sections"
          :key="section.value"
          type="button"
          role="tab"
          :aria-selected="section.value === currentSection"
          class="u-touch -mb-px inline-flex items-center gap-2 border-b-2 px-4 text-sm font-semibold uppercase tracking-wide"
          :class="section.value === currentSection
            ? 'border-primary text-foreground'
            : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="currentSection = section.value"
        >
          {{ section.label }}
          <span v-if="section.count" class="text-xs font-normal">{{ section.count }}</span>
        </button>
      </nav>

      <div class="mt-8 space-y-10 md:space-y-0">
        <section
          v-for="section in sections"
          :key="section.value"
          :class="section.value === currentSection ? 'md:block' : 'md:hidden'"
          :aria-labelledby="`record-section-${section.value}`"
        >
          <h2 :id="`record-section-${section.value}`" class="mb-4 text-xl font-semibold text-foreground md:hidden">
            {{ section.label }}
          </h2>
          <slot :name="section.value" />
        </section>
      </div>

      <section v-if="$slots.activity" class="mt-12 border-t border-border pt-8">
        <slot name="activity" />
      </section>

      <slot />
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next';

import ActionControl from './RecordPageAction.vue';
import AdminContentPage from './AdminContentPage.vue';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Sheet, SheetClose, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import type { StatusPresentation } from '@/Constants/statuses';

export interface RecordPageSection {
  value: string;
  label: string;
  count?: number | null;
}

export interface RecordFact {
  key: string;
  label: string;
  value?: string | number | null;
  href?: string;
}

export interface RecordAction {
  key: string;
  label: string;
  icon?: Component;
  href?: string;
  external?: boolean;
  destructive?: boolean;
}

export interface RecordNavigationContext {
  position: number;
  total: number;
  previousHref?: string | null;
  nextHref?: string | null;
}

const props = withDefaults(defineProps<{
  title: string;
  entityType: string;
  status?: StatusPresentation;
  facts?: RecordFact[];
  sections?: RecordPageSection[];
  primaryAction?: RecordAction;
  overflowActions?: RecordAction[];
  navigation?: RecordNavigationContext;
}>(), {
  status: undefined,
  facts: () => [],
  sections: () => [],
  primaryAction: undefined,
  overflowActions: () => [],
  navigation: undefined,
});

const emit = defineEmits<{
  action: [key: string];
}>();

const currentSection = defineModel<string>('section', { default: '' });

if (!currentSection.value && props.sections.length) {
  currentSection.value = props.sections[0]!.value;
}

const entityLabel = computed(() => getEntityTypeDefinition(props.entityType)?.label ?? $t('Įrašas'));
const headTitle = computed(() => `${props.title} · ${$t(entityLabel.value)} · VU SA`);

const visit = (href?: string | null) => {
  if (href) {
    router.visit(href);
  }
};
</script>
