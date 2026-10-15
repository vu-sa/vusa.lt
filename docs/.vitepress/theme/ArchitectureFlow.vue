<template>
  <figure class="af">
    <figcaption class="af__title">
      {{ flow.title[locale] }}
    </figcaption>
    <p class="af__hint">
      {{ text.hint }}
    </p>

    <ClientOnly>
      <FlowCanvas :flow :locale />
    </ClientOnly>

    <ul class="af__legend" :aria-label="text.legend">
      <li v-for="kind in kinds" :key="kind" :class="`af-kind--${kind}`">
        {{ kindLabels[kind][locale] }}
      </li>
    </ul>

    <details class="af__steps" :open="stepsOpen">
      <summary>{{ text.steps }}</summary>
      <ol>
        <li v-for="node in flow.nodes" :key="node.id" :class="`af-kind--${node.kind}`">
          <strong>{{ node.label[locale] }}</strong> – {{ node.detail[locale] }}
          <a v-if="node.file" :href="fileUrl(node.file)" target="_blank" rel="noopener">
            <code>{{ node.file }}</code>
          </a>
        </li>
      </ol>
    </details>
  </figure>
</template>

<script setup lang="ts">
import { useData } from 'vitepress'
import { computed, defineAsyncComponent, onMounted, ref } from 'vue'
import { fileUrl, flows, kindLabels, type FlowName, type Locale } from './architecture/flows'

// Vue Flow touches `window` on import, so the canvas loads only in the browser; the step list renders in SSR for search.
const FlowCanvas = defineAsyncComponent(() => import('./architecture/FlowCanvas.vue'))

const props = defineProps<{
  flow: FlowName
  locale?: Locale
}>()

const { lang } = useData()

const locale = computed<Locale>(() => props.locale ?? (lang.value.startsWith('en') ? 'en' : 'lt'))
const flow = computed(() => flows[props.flow])
const kinds = computed(() => [...new Set(flow.value.nodes.map(node => node.kind))])

// The canvas is cramped on a phone, so the text version starts open there.
const stepsOpen = ref(false)
onMounted(() => {
  stepsOpen.value = window.matchMedia?.('(max-width: 640px)').matches ?? false
})

const text = computed(() => ({
  lt: {
    hint: 'Užvedus pelę ant žingsnio arba jį paspaudus, parodomas paaiškinimas ir failas. Diagramą galima tempti ir didinti.',
    legend: 'Spalvų reikšmės',
    steps: 'Visi žingsniai tekstu',
  },
  en: {
    hint: 'Hover over or tap a step to see its explanation and file. The diagram can be dragged and zoomed.',
    legend: 'Colour key',
    steps: 'All steps as text',
  },
}[locale.value]))
</script>

<style>
.af-kind--client { --af-kind: var(--vp-c-green-1); }
.af-kind--http { --af-kind: var(--vp-c-indigo-1); }
.af-kind--logic { --af-kind: var(--vp-c-purple-1); }
.af-kind--data { --af-kind: var(--vusa-yellow-dark); }
.af-kind--async { --af-kind: var(--vp-c-gray-1); }
.af-kind--response { --af-kind: var(--vp-c-text-2); }
.af-kind--error { --af-kind: var(--vp-c-danger-1); }

.af-node--client { --af-kind: var(--vp-c-green-1); }
.af-node--http { --af-kind: var(--vp-c-indigo-1); }
.af-node--logic { --af-kind: var(--vp-c-purple-1); }
.af-node--data { --af-kind: var(--vusa-yellow-dark); }
.af-node--async { --af-kind: var(--vp-c-gray-1); }
.af-node--response { --af-kind: var(--vp-c-text-2); }
.af-node--error { --af-kind: var(--vp-c-danger-1); }
</style>

<style scoped>
.af {
  margin: 24px 0;
}

.af__title {
  font-weight: 600;
}

.af__hint {
  margin: 4px 0 12px;
  color: var(--vp-c-text-2);
  font-size: 14px;
}

.af__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 16px;
  margin: 12px 0 0;
  padding: 0;
  list-style: none;
  font-size: 13px;
  color: var(--vp-c-text-2);
}

.af__legend li {
  margin: 0;
  padding-left: 10px;
  border-left: 4px solid var(--af-kind);
}

.af__steps {
  margin-top: 12px;
}

.af__steps summary {
  padding: 10px 0;
  cursor: pointer;
  font-weight: 600;
}

.af__steps ol {
  padding-left: 2.2em;
}

.af__steps li {
  padding-left: 8px;
  border-left: 3px solid var(--af-kind);
}
</style>
