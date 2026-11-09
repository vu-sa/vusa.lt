<template>
  <div class="min-h-full">
    <Head>
      <title>{{ headTitle }}</title>
    </Head>

    <div class="mx-auto w-full max-w-[90rem]">
      <header class="border-b border-border pt-6 pb-6 sm:pt-10 sm:pb-8">
        <!-- Holds three clamped title lines plus the fold toggle on desktop so the facts strip and aside below do not jump between records.
             Beside-title actions keep the title to one row, where that reserve is only empty space. -->
        <div :class="['flex min-w-0 items-start gap-4', !actionsBesideTitle && 'lg:min-h-42']" data-slot="record-identity">
          <div v-if="$slots.identity" class="shrink-0">
            <slot name="identity" />
          </div>

          <div :class="['min-w-0 flex-1', actionsBesideTitle && 'lg:grid lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start lg:gap-x-6']">
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
              <slot name="title">
                {{ title }}
              </slot>
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
            <!-- Under the title even when the actions sit beside it, so a sentence never squeezes into their column. -->
            <div v-if="$slots.lead" :class="['mt-2 max-w-3xl text-sm text-muted-foreground', actionsBesideTitle && 'lg:col-start-1 lg:row-start-4']" data-slot="record-lead">
              <slot name="lead" />
            </div>
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

                <RecordActionMenu :actions="menuActions" trigger-size="default" @select="selectOverflow" />
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
              <!-- Block-level, so the icon cannot stretch the line box past the other facts' values. -->
              <template v-if="fact.status">
                <span :class="['flex items-center gap-1.5 font-semibold', statusRoleParts[fact.status.role].text]">
                  <component :is="fact.status.icon" class="size-4 shrink-0" aria-hidden="true" />
                  {{ $t(fact.status.label) }}
                </span>
                <span v-if="fact.detail" class="mt-0.5 block text-xs font-normal text-muted-foreground" data-slot="fact-detail">
                  {{ fact.detail }}
                </span>
              </template>
              <a
                v-else-if="safeHref(fact.href) && fact.external"
                :href="safeHref(fact.href)"
                target="_blank"
                rel="noopener noreferrer"
                :class="FACT_LINK_CLASS"
              >
                {{ fact.value }}
              </a>
              <Link v-else-if="safeHref(fact.href)" :href="safeHref(fact.href)" :class="FACT_LINK_CLASS">
                {{ fact.value }}
              </Link>
              <span v-else>{{ fact.value }}</span>
              <span v-if="!fact.status && fact.detail" class="mt-0.5 block text-xs font-normal text-muted-foreground" data-slot="fact-detail">
                {{ fact.detail }}
              </span>
            </slot>
          </dd>
        </div>
      </RuledGrid>

      <div v-if="$slots.alert" class="mt-6">
        <slot name="alert" />
      </div>

      <!-- `empty:` hides the gap when the strip has nothing to offer (a read-only viewer). -->
      <div v-if="$slots['next-actions']" class="mt-6 empty:hidden" data-slot="record-next-actions">
        <slot name="next-actions" />
      </div>

      <div v-if="hasTabs" ref="tabsAnchor" aria-hidden="true" />
      <!-- Sticky under the shell chrome, at its section tabs' height and padding, so a long section never strands its tabs off screen. -->
      <nav
        v-if="hasTabs"
        ref="tabsNav"
        :class="[
          'sticky top-[var(--shell-chrome-height,0px)] z-30 mt-8 hidden border-b border-border md:block',
          'bg-background/95 backdrop-blur-sm [.a11y-contrast_&]:bg-background [.a11y-contrast_&]:backdrop-blur-none',
        ]"
        :aria-label="$t('Įrašo skyriai')"
      >
        <div ref="tabList" role="tablist" class="flex overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <button
            v-for="section in sections"
            :id="`record-tab-${recordId}-${section.value}`"
            :key="section.value"
            type="button"
            role="tab"
            :aria-selected="section.value === currentSection"
            :aria-controls="`record-panel-${recordId}-${section.value}`"
            :tabindex="section.value === currentSection ? 0 : -1"
            :data-section="section.value"
            :class="[
              '-mb-px inline-flex h-10 shrink-0 items-center gap-2 border-b-2 px-3 pointer-coarse:h-11',
              'text-xs font-bold uppercase tracking-wide whitespace-nowrap',
              section.value === currentSection
                ? 'border-brand text-foreground'
                : 'border-transparent text-muted-foreground hover:text-foreground',
            ]"
            @click="selectSection(section.value)"
            @keydown="onTabKeydown"
          >
            <component :is="section.icon" v-if="section.icon" class="size-4 shrink-0" aria-hidden="true" />
            {{ section.label }}
            <span v-if="section.count" class="text-xs font-normal">{{ section.count }}</span>
          </button>
        </div>
      </nav>

      <!-- The phone selector and desktop tabs share the same section state. -->
      <div v-if="hasTabs" class="sticky top-[var(--shell-chrome-height,0px)] z-30 mt-8 border-b border-border bg-background py-3 md:hidden" data-slot="record-section-switcher">
        <label :for="switcherId" class="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
          {{ $t('Rodyti') }}
        </label>
        <NativeSelect :id="switcherId" :model-value="mobileSection" :options="switcherOptions" @update:model-value="pickMobileSection" @change="selectSection(($event.target as HTMLSelectElement).value)" />
      </div>

      <div :class="$slots.aside ? 'lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-10' : undefined">
        <!-- At least a screen tall below the tabs, so a short section never pulls the page up under the reader;
             the comments count towards it, so they follow the first section instead of a screen below it. -->
        <div :class="['min-w-0', hasTabs && 'md:min-h-[calc(var(--shell-scroll-height,100svh)-var(--shell-chrome-height,0px)-4.5rem)]']" data-slot="record-body">
          <div
            :class="['mt-8 space-y-10', sectionLayout !== 'stacked' && 'md:space-y-0']"
            data-slot="record-sections"
          >
            <section
              v-for="section in sections"
              :id="`record-panel-${recordId}-${section.value}`"
              :key="section.value"
              :class="[
                sectionLayout === 'stacked' ? undefined : section.value === currentSection ? 'md:block' : 'md:hidden',
                hasTabs && section.value !== mobileSection && 'max-md:hidden',
                'scroll-mt-[calc(var(--shell-chrome-height,0px)+1rem)]',
              ]"
              :role="hasTabs && isDesktop ? 'tabpanel' : undefined"
              :aria-labelledby="hasTabs && isDesktop ? `record-tab-${recordId}-${section.value}` : `record-section-${recordId}-${section.value}`"
            >
              <h2
                :id="`record-section-${recordId}-${section.value}`"
                :class="[
                  'mb-4 flex items-center gap-2 text-xl font-semibold text-foreground',
                  sectionLayout !== 'stacked' && 'md:hidden',
                  hasTabs && 'max-md:sr-only',
                ]"
              >
                <component :is="section.icon" v-if="section.icon" class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ section.label }}
              </h2>
              <slot :name="section.value" />
            </section>
          </div>

          <!-- Hidden, not unmounted, so the thread and any draft survive a tab switch. -->
          <section
            v-if="$slots.activity"
            class="mt-12"
            :class="{
              'md:hidden': hasTabs && currentSection !== sections[0]?.value,
              'max-md:hidden': hasTabs && mobileSection !== sections[0]?.value,
            }"
            data-slot="record-activity"
          >
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
import { computed, nextTick, ref, useId, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useMediaQuery, useResizeObserver } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft, ChevronRight, History } from '@lucide/vue';

import RecordActionMenu from '@/Components/Patterns/RecordActionMenu.vue';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import { RuledGrid, type RuledGridColumns } from '@/Components/Brand';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { statusRoleParts, type StatusPresentation } from '@/Constants/statusIcons';
import { safeHref } from '@/Utils/safeHref';

import ActionControl from './RecordPageAction.vue';

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
  /** A quiet line under the value, e.g. what a status rests on. */
  detail?: string | null;
}

export type RecordAction = AdminRecordAction;

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
  sectionLayout?: 'auto' | 'stacked';
  primaryAction?: RecordAction;
  overflowActions?: RecordAction[];
  navigation?: RecordNavigationContext;
  /** Adds "Pakeitimų istorija" to ⋯: rarely needed, so it never takes room in the page. */
  historySubject?: { type: string; id: string };
  /** Long sentence titles (agenda items) read better unshouted than in the display face. */
  titleVoice?: 'display' | 'sentence';
  /** Keeps short record titles and their actions on one row at desktop widths. */
  actionsBesideTitle?: boolean;
  /** Query key (e.g. `tab`) that records the open section, so a tab change is a step back can undo. */
  sectionQueryKey?: string;
}>(), {
  status: undefined,
  eyebrowSuffix: undefined,
  facts: () => [],
  sections: () => [],
  sectionLayout: 'auto',
  primaryAction: undefined,
  overflowActions: () => [],
  navigation: undefined,
  historySubject: undefined,
  titleVoice: 'display',
  sectionQueryKey: undefined,
});

const emit = defineEmits<{
  action: [key: string];
}>();

const recordId = useId();
const currentSection = defineModel<string>('section', { default: '' });
const hasTabs = computed(() => props.sectionLayout !== 'stacked' && props.sections.length >= 2);

if (!currentSection.value && props.sections.length) {
  currentSection.value = props.sections[0]!.value;
}

// A deep link (`?tab=files`) opens that section, whatever the page would start on.
if (props.sectionQueryKey) {
  const linked = new URL(usePage().url, window.location.origin).searchParams.get(props.sectionQueryKey);
  if (linked && linked !== currentSection.value && props.sections.some(section => section.value === linked)) {
    currentSection.value = linked;
  }
}

const isDesktop = useMediaQuery('(min-width: 768px)');
const switcherId = useId();
// A link to a section other than the first (`?tab=`) opens just that section on a phone too.
const mobileSection = computed({ get: () => currentSection.value, set: value => { currentSection.value = value; } });
const switcherOptions = computed(() => [
  ...props.sections.map(section => ({
    value: section.value,
    label: section.count ? `${section.label} · ${section.count}` : section.label,
  })),
]);

watch(() => props.sections, (sections) => {
  if (!sections.some(section => section.value === mobileSection.value)) {
    const fallback = sections[0]?.value ?? '';
    if (props.sectionQueryKey) {
      const url = new URL(page.url, window.location.origin);
      url.searchParams.delete(props.sectionQueryKey);
      router.replace({ url: `${url.pathname}${url.search}${url.hash}`, preserveState: true, preserveScroll: true });
    }
    currentSection.value = fallback;
  }
});

// --- The open section in the URL: each tab change is a history entry --------------------------

const page = usePage();

const sectionInUrl = (url: string) => {
  if (!props.sectionQueryKey) {
    return null;
  }
  const value = new URL(url, window.location.origin).searchParams.get(props.sectionQueryKey);

  return props.sections.some(section => section.value === value) ? value : props.sections[0]?.value ?? null;
};

/** A section the reader picked: shown, and remembered as a step in the browser history. */
const selectSection = (value: string) => { currentSection.value = value; };

const recordSection = (value: string) => {

  if (!props.sectionQueryKey) {
    return;
  }
  const url = new URL(page.url, window.location.origin);
  url.searchParams.set(props.sectionQueryKey, value);
  const next = `${url.pathname}${url.search}${url.hash}`;
  if (next !== page.url) {
    // A client-side entry: no request, and the reveal below decides the scroll.
    router.push({ url: next, preserveScroll: true, preserveState: true });
  }
};

// Back and forward restore the URL; follow it without pushing again.
watch(() => page.url, (url) => {
  const requested = props.sectionQueryKey ? new URL(url, window.location.origin).searchParams.get(props.sectionQueryKey) : null;
  if (requested && !props.sections.some(section => section.value === requested)) {
    const normalized = new URL(url, window.location.origin);
    normalized.searchParams.delete(props.sectionQueryKey!);
    router.replace({ url: `${normalized.pathname}${normalized.search}${normalized.hash}`, preserveState: true, preserveScroll: true });
  }
  const value = sectionInUrl(url);
  if (value && value !== currentSection.value) {
    currentSection.value = value;
  }
}, { immediate: true });

const pickMobileSection = (value: string | number | null | undefined) => {
  if (value != null) selectSection(String(value));
};

const tabsAnchor = ref<HTMLElement | null>(null);
const tabList = ref<HTMLElement | null>(null);

/** Keeps the new section's top in view: a stuck tab bar would otherwise leave the reader mid-page or below a short section. */
const revealSection = (value: string) => {
  if (!isDesktop.value) {
    document.getElementById(`record-panel-${recordId}-${value}`)?.scrollIntoView({ block: 'start' });
    return;
  }

  const anchor = tabsAnchor.value;
  const scrollArea = anchor?.closest<HTMLElement>('[data-slot="admin-scroll-area"]');
  if (anchor && scrollArea) {
    const chrome = Number.parseFloat(getComputedStyle(scrollArea).getPropertyValue('--shell-chrome-height')) || 0;
    const anchorTop = scrollArea.scrollTop + anchor.getBoundingClientRect().top - scrollArea.getBoundingClientRect().top - chrome;
    if (scrollArea.scrollTop > anchorTop) {
      scrollArea.scrollTop = anchorTop;
    }
  }

  const list = tabList.value;
  const tab = [...(list?.querySelectorAll<HTMLElement>('[role="tab"]') ?? [])].find(element => element.dataset.section === value);
  if (list && tab) {
    if (tab.offsetLeft < list.scrollLeft) {
      list.scrollLeft = tab.offsetLeft;
    }
    else if (tab.offsetLeft + tab.offsetWidth > list.scrollLeft + list.clientWidth) {
      list.scrollLeft = tab.offsetLeft + tab.offsetWidth - list.clientWidth;
    }
  }
};

watch(currentSection, (value, previous) => {
  if (value && previous && value !== sectionInUrl(page.url)) recordSection(value);
  if (hasTabs.value && value && previous) {
    void nextTick(() => revealSection(value));
  }
});

const onTabKeydown = (event: KeyboardEvent) => {
  const index = props.sections.findIndex(section => section.value === currentSection.value);
  const last = props.sections.length - 1;
  const next = {
    ArrowRight: index >= last ? 0 : index + 1,
    ArrowLeft: index <= 0 ? last : index - 1,
    Home: 0,
    End: last,
  }[event.key];
  if (next === undefined) {
    return;
  }

  event.preventDefault();
  selectSection(props.sections[next]!.value);
  void nextTick(() => tabList.value?.querySelector<HTMLElement>('[aria-selected="true"]')?.focus());
};

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

const selectOverflow = (key: string) => {
  if (key === '__history') historyOpen.value = true;
  else emit('action', key);
};
</script>
