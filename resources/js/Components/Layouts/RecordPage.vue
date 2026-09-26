<template>
  <div class="min-h-full">
    <Head>
      <title>{{ headTitle }}</title>
    </Head>

    <div class="mx-auto w-full max-w-[90rem]">
      <header class="border-b border-border pt-6 pb-6 sm:pt-10 sm:pb-8">
        <div class="flex min-w-0 items-start gap-4">
          <div v-if="$slots.identity" class="shrink-0">
            <slot name="identity" />
          </div>

          <div :class="['min-w-0 flex-1', actionsBesideTitle && 'lg:grid lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:gap-x-6']">
            <p :class="['flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-brand', actionsBesideTitle && 'lg:col-span-2']" data-slot="record-eyebrow">
              <component :is="entityDefinition.icon" v-if="entityDefinition" class="size-4 shrink-0" aria-hidden="true" />
              {{ $t(entityLabel) }}
              <template v-if="eyebrowSuffix">
                <span aria-hidden="true">·</span>
                {{ eyebrowSuffix }}
              </template>
            </p>
            <h1
              ref="titleElement"
              :class="[
                'mt-3 min-w-0 text-balance text-foreground',
                titleVoice === 'sentence'
                  ? 'text-2xl leading-tight font-semibold tracking-tight sm:text-3xl'
                  : 'u-display text-2xl leading-[1.12] tracking-[0.02em] sm:text-3xl xl:text-[2.125rem]',
                titleExpanded ? undefined : 'line-clamp-3',
                actionsBesideTitle && 'lg:col-start-1 lg:row-start-2',
              ]"
              data-slot="record-title"
            >
              {{ title }}
            </h1>
            <button
              v-if="titleOverflows || titleExpanded"
              type="button"
              :class="[
                // A grid item stretches, and a button centres its label, so it has to be pinned left.
                'mt-1 justify-self-start text-left text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground pointer-coarse:min-h-11',
                actionsBesideTitle && 'lg:col-start-1 lg:row-start-3',
              ]"
              :aria-expanded="titleExpanded"
              data-testid="record-title-toggle"
              @click="titleExpanded = !titleExpanded"
            >
              {{ titleExpanded ? $t('Rodyti mažiau') : $t('Rodyti daugiau') }}
            </button>
            <div :class="['mt-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between', actionsBesideTitle && 'lg:col-start-2 lg:row-start-2 lg:mt-3']">
              <div v-if="status || $slots.subtitle" class="flex flex-wrap items-center gap-3">
                <StatusBadge v-if="status" :status />
                <slot name="subtitle" />
              </div>

              <div class="flex shrink-0 flex-wrap items-center gap-2 lg:ml-auto">
                <div v-if="navigation" class="flex h-11 items-center border border-border">
                  <Button
                    variant="ghost"
                    :size="navigation.previousLabel ? 'default' : 'icon-lg'"
                    voice="plain"
                    :class="['h-full', navigation.previousLabel ? 'gap-1 px-2 tabular-nums' : 'w-11']"
                    :disabled="!navigation.previousHref"
                    :aria-label="navigation.previousAriaLabel ?? $t('Ankstesnis įrašas')"
                    @click="visit(navigation.previousHref)"
                  >
                    <ChevronLeft class="size-4" />
                    <span v-if="navigation.previousLabel" class="text-sm">{{ navigation.previousLabel }}</span>
                  </Button>
                  <slot name="navigation-label" :navigation>
                    <span class="min-w-20 px-2 text-center text-sm font-medium text-muted-foreground">
                      {{ navigation.label ?? `${navigation.position} / ${navigation.total}` }}
                    </span>
                  </slot>
                  <Button
                    variant="ghost"
                    :size="navigation.nextLabel ? 'default' : 'icon-lg'"
                    voice="plain"
                    :class="['h-full', navigation.nextLabel ? 'gap-1 px-2 tabular-nums' : 'w-11']"
                    :disabled="!navigation.nextHref"
                    :aria-label="navigation.nextAriaLabel ?? $t('Kitas įrašas')"
                    @click="visit(navigation.nextHref)"
                  >
                    <span v-if="navigation.nextLabel" class="text-sm">{{ navigation.nextLabel }}</span>
                    <ChevronRight class="size-4" />
                  </Button>
                </div>

                <ActionControl
                  v-if="primaryAction"
                  :action="primaryAction"
                  primary
                  @select="emit('action', $event)"
                />

                <!-- The root renders no element, so the breakpoint class lives on the trigger. -->
                <DropdownMenu v-if="menuActions.length">
                  <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="icon-lg" class="hidden size-11 md:inline-flex" :aria-label="$t('Daugiau veiksmų')" data-testid="record-overflow-trigger">
                      <MoreHorizontal class="size-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem
                      v-for="action in menuActions"
                      :key="action.key"
                      :class="action.destructive ? 'text-destructive focus:text-destructive' : undefined"
                      @select="selectOverflow(action)"
                    >
                      <component :is="action.icon" v-if="action.icon" class="mr-2 size-4" />
                      {{ action.label }}
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>

                <Sheet v-if="menuActions.length">
                  <SheetTrigger as-child>
                    <Button variant="outline" size="icon-lg" class="size-11 md:hidden" :aria-label="$t('Daugiau veiksmų')">
                      <MoreHorizontal class="size-4" />
                    </Button>
                  </SheetTrigger>
                  <SheetContent side="bottom" class="max-h-[85vh]">
                    <SheetHeader>
                      <SheetTitle>{{ $t('Veiksmai') }}</SheetTitle>
                    </SheetHeader>
                    <div class="grid gap-2 px-4 pb-6">
                      <SheetClose v-for="action in menuActions" :key="action.key" as-child>
                        <Button
                          variant="ghost"
                          class="u-touch justify-start"
                          :class="action.destructive ? 'text-destructive' : undefined"
                          @click="selectOverflow(action)"
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
          </div>
        </div>
      </header>

      <RuledGrid v-if="facts.length" as="dl" :columns="factColumns" data-slot="record-facts">
        <div
          v-for="fact in facts"
          :key="fact.key"
          :class="[
            'flex min-w-0 flex-col px-4 py-4',
            fact.status ? statusRoleParts[fact.status.role].surface : undefined,
            fact.surfaceClass,
          ]"
          :data-status-role="fact.status?.role"
        >
          <dt :class="['text-[11px] font-bold uppercase tracking-[0.18em]', fact.labelClass ?? 'text-muted-foreground']" :aria-label="fact.labelIcon ? fact.label : undefined">
            <component :is="fact.labelIcon" v-if="fact.labelIcon" class="size-3.5" aria-hidden="true" />
            <template v-else>
              {{ fact.label }}
            </template>
          </dt>
          <dd class="mt-auto min-w-0 pt-1 text-sm font-medium text-foreground">
            <slot :name="`fact-${fact.key}`" :fact>
              <span v-if="fact.status" :class="['inline-flex items-center gap-1.5 font-semibold', statusRoleParts[fact.status.role].text]">
                <component :is="fact.status.icon" class="size-4 shrink-0" aria-hidden="true" />
                {{ $t(fact.status.label) }}
              </span>
              <span v-if="fact.status && fact.detail" class="mt-0.5 block text-xs font-normal text-muted-foreground" data-slot="fact-detail">
                {{ fact.detail }}
              </span>
              <a
                v-else-if="fact.href && fact.external"
                :href="fact.href"
                target="_blank"
                rel="noopener noreferrer"
                :class="FACT_LINK_CLASS"
              >
                {{ fact.value }}
              </a>
              <Link v-else-if="fact.href" :href="fact.href" :class="FACT_LINK_CLASS">
                {{ fact.value }}
              </Link>
              <span v-else>{{ fact.value }}</span>
            </slot>
          </dd>
        </div>
      </RuledGrid>

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
          class="-mb-px inline-flex h-12 items-center gap-2 border-b-2 px-4 text-xs font-bold uppercase tracking-wide"
          :class="section.value === currentSection
            ? 'border-brand text-foreground'
            : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="currentSection = section.value"
        >
          <component :is="section.icon" v-if="section.icon" class="size-4 shrink-0" aria-hidden="true" />
          {{ section.label }}
          <span v-if="section.count" class="text-xs font-normal">{{ section.count }}</span>
        </button>
      </nav>

      <div :class="$slots.aside ? 'lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-10' : undefined">
        <div class="min-w-0">
          <div class="mt-8 space-y-10 md:space-y-0">
            <section
              v-for="section in sections"
              :key="section.value"
              :class="section.value === currentSection ? 'md:block' : 'md:hidden'"
              :aria-labelledby="`record-section-${section.value}`"
            >
              <h2 :id="`record-section-${section.value}`" class="mb-4 flex items-center gap-2 text-xl font-semibold text-foreground md:hidden">
                <component :is="section.icon" v-if="section.icon" class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ section.label }}
              </h2>
              <slot :name="section.value" />
            </section>
          </div>

          <section v-if="$slots.activity" class="mt-12 border-t border-border pt-8">
            <slot name="activity" />
          </section>
        </div>

        <!-- The shell's sticky chrome sits inside the same scroll area, so offset by its height. -->
        <aside v-if="$slots.aside" class="mt-8 lg:sticky lg:top-[calc(var(--shell-chrome-height,0px)+1.5rem)] lg:self-start">
          <slot name="aside" />
        </aside>
      </div>

      <slot />

      <ActivityLogSheet
        v-if="historySubject"
        v-model:open="historyOpen"
        :subject-type="historySubject.type"
        :subject-id="historySubject.id"
        hide-trigger
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useResizeObserver } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft, ChevronRight, History, MoreHorizontal } from 'lucide-vue-next';

import ActionControl from './RecordPageAction.vue';

import { RuledGrid, type RuledGridColumns } from '@/Components/Brand';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Sheet, SheetClose, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { statusRoleParts, type StatusPresentation } from '@/Constants/statuses';

export interface RecordPageSection {
  value: string;
  label: string;
  count?: number | null;
  icon?: Component;
}

export interface RecordFact {
  key: string;
  label: string;
  labelIcon?: Component;
  value?: string | number | null;
  surfaceClass?: string;
  labelClass?: string;
  href?: string;
  /** Opens in a new tab (e.g. the public page); internal hrefs are Inertia visits. */
  external?: boolean;
  /** Renders the fact as a status: the cell takes the role's surface, the value its icon and ink. */
  status?: StatusPresentation;
  /** A quiet line under a status, e.g. what the status rests on. */
  detail?: string | null;
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
  /** Replaces "3 / 12", e.g. while stepping through only the incomplete items. */
  label?: string;
  /** Short text beside ‹ ›, e.g. the neighbouring meeting's date. */
  previousLabel?: string | null;
  nextLabel?: string | null;
  previousAriaLabel?: string | null;
  nextAriaLabel?: string | null;
}

const props = withDefaults(defineProps<{
  title: string;
  entityType: string;
  eyebrowSuffix?: string | null;
  status?: StatusPresentation;
  facts?: RecordFact[];
  sections?: RecordPageSection[];
  primaryAction?: RecordAction;
  overflowActions?: RecordAction[];
  navigation?: RecordNavigationContext;
  /** Adds "Pakeitimų istorija" to ⋯: rarely needed, so it never takes room in the page. */
  historySubject?: { type: string; id: string };
  /** Long sentence titles (agenda items) read better unshouted than in the display face. */
  titleVoice?: 'display' | 'sentence';
  /** Keeps short record titles and their actions on one row at desktop widths. */
  actionsBesideTitle?: boolean;
}>(), {
  status: undefined,
  eyebrowSuffix: undefined,
  facts: () => [],
  sections: () => [],
  primaryAction: undefined,
  overflowActions: () => [],
  navigation: undefined,
  historySubject: undefined,
  titleVoice: 'display',
});

const emit = defineEmits<{
  action: [key: string];
}>();

const currentSection = defineModel<string>('section', { default: '' });

if (!currentSection.value && props.sections.length) {
  currentSection.value = props.sections[0]!.value;
}

const historyOpen = ref(false);

/** Some agenda item titles run to a paragraph; past three lines they fold behind a toggle. */
const titleElement = ref<HTMLElement | null>(null);
const titleExpanded = ref(false);
const titleOverflows = ref(false);

const measureTitle = () => {
  const element = titleElement.value;
  if (element && !titleExpanded.value) {
    // Ascenders and descenders poke a few pixels past a tight line box, so only a hidden
    // half-line or more counts as a fourth line.
    const lineHeight = Number.parseFloat(getComputedStyle(element).lineHeight) || 24;
    titleOverflows.value = element.scrollHeight - element.clientHeight > lineHeight / 2;
  }
};

useResizeObserver(titleElement, measureTitle);
watch(() => props.title, () => {
  titleExpanded.value = false;
  requestAnimationFrame(measureTitle);
});

const menuActions = computed<RecordAction[]>(() => {
  if (!props.historySubject) {
    return props.overflowActions;
  }

  // Before the destructive entries, which conventionally close the menu.
  const history: RecordAction = { key: '__history', label: $t('activity.title'), icon: History };
  const firstDestructive = props.overflowActions.findIndex(action => action.destructive);

  return firstDestructive === -1
    ? [...props.overflowActions, history]
    : [...props.overflowActions.slice(0, firstDestructive), history, ...props.overflowActions.slice(firstDestructive)];
});

const FACT_LINK_CLASS = 'text-brand underline decoration-brand/40 underline-offset-4 hover:decoration-brand';

const factColumns = computed<RuledGridColumns>(() => ({
  base: 1,
  sm: 2,
  lg: 3,
  xl: Math.max(1, Math.min(props.facts.length, 6)) as RuledGridColumns['base'],
}));

const entityDefinition = computed(() => getEntityTypeDefinition(props.entityType));
const entityLabel = computed(() => entityDefinition.value?.label ?? 'Įrašas');
const headTitle = computed(() => `${props.title} · ${$t(entityLabel.value)} · VU SA`);

const visit = (href?: string | null) => {
  if (href) {
    router.visit(href);
  }
};

/** A menu item is not a link element, so an action carrying `href` is followed here, as the primary one is. */
const selectOverflow = (action: RecordAction) => {
  if (action.key === '__history') {
    historyOpen.value = true;
  }
  else if (action.href && action.external) {
    window.open(action.href, '_blank', 'noopener');
  }
  else if (action.href) {
    router.visit(action.href);
  }
  else {
    emit('action', action.key);
  }
};
</script>
