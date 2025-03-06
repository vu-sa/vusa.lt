<template>
  <StructureGraphWrapper :nodes :edges />
</template>

<script setup lang="ts">
import StructureGraphWrapper from "@/Features/StructureGraph/StructureGraphWrapper.vue";
import { usePage } from "@inertiajs/vue3";
import { MarkerType } from "@vue-flow/core"
import { trans as $t } from "laravel-vue-i18n";

const nodes = [
  {
    id: 'node-ark',
    type: 'multiple-handle',
    data: { label: $t('VU SA ataskaitinė-rinkiminė konferencija'), nodeClass: 'bg-vusa-red', textClass: 'uppercase font-extrabold text-white', handles: ['right', 'left', 'bottom'] },
    style: { lineHeight: '1.25', width: '340px' },
    position: { x: 10, y: 0 },
  },
  {
    id: 'node-prezidentas',
    type: 'multiple-handle',
    data: { label: $t('Prezidentas'), nodeClass: 'bg-zinc-800', textClass: ['uppercase text-white font-extrabold'], handles: ['top', 'left-bottom', 'right-bottom', 'right-top'] },
    style: { lineHeight: '1.25' },
    position: { x: 104, y: 90 },
  },
  {
    id: 'node-cb',
    type: 'multiple-handle',
    data: { label: $t('Central Office'), nodeClass: 'bg-zinc-400', textClass: ['uppercase text-white font-extrabold'], handles: ['left'] },
    style: { lineHeight: '1.25', width: '120px' },
    position: { x: 370, y: 73 },
  },
  {
    id: 'node-revizija',
    type: 'multiple-handle',
    data: { label: $t('Revizijos komisija'), nodeClass: 'bg-zinc-400', textClass: ['uppercase text-white font-extrabold'], handles: ['left'] },
    style: { lineHeight: '1.25', width: '120px' },
    position: { x: 420, y: usePage().props.app.locale === 'lt' ? 0 : -9 },
  },
  {
    id: 'node-taryba',
    type: 'multiple-handle',
    data: { label: $t('Taryba'), nodeClass: 'bg-vusa-yellow', textClass: ['uppercase text-white font-extrabold'], handles: ['top', 'right', 'bottom-left'] },
    style: { lineHeight: '1.25' },
    position: { x: 5, y: 170 },
  },
  {
    id: 'node-parlamentas',
    type: 'multiple-handle',
    data: { label: $t('Parlamentas'), nodeClass: 'bg-vusa-yellow', textClass: ['uppercase text-white font-extrabold'], handles: ['top', 'left', 'right'] },
    style: { lineHeight: '1.25' },
    position: { x: usePage().props.app.locale === 'lt' ? 205 : 255, y: 170 },
  },
  {
    id: 'node-park',
    type: 'multiple-handle',
    data: { label: $t('VU SA P ataskaitinė-rinkiminė konferencija'), nodeClass: 'bg-vusa-red', textClass: 'uppercase font-extrabold text-white', handles: ['left', 'bottom', 'right', 'bottom-right'] },
    style: { lineHeight: '1.25', width: '260px' },
    position: { x: 50, y: 250 },
  },
  {
    id: 'node-pirmininkas',
    type: 'multiple-handle',
    data: { label: $t('Pirmininkas'), nodeClass: 'bg-zinc-800', textClass: ['uppercase text-white font-extrabold'], handles: ['left', 'top', 'bottom'] },
    style: { lineHeight: '1.25' },
    position: { x: 105, y: 340 },
  },
  {
    id: 'node-pkvo',
    type: 'multiple-handle',
    data: { label: $t('VU SA P kolegialus valdymo organas'), nodeClass: 'bg-vusa-red', textClass: 'uppercase font-extrabold text-white', handles: ['top', 'top-right'] },
    style: { lineHeight: '1.25', width: '260px' },
    position: { x: 50, y: 410 },
  },
]

const edges = [
  {
    id: 'edge-ark-prezidentas',
    source: 'node-ark',
    target: 'node-prezidentas',
    sourceHandle: 'bottom',
    targetHandle: 'top',
    type: 'step',
    label: $t('Renka'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-prezidentas-cb',
    source: 'node-prezidentas',
    target: 'node-cb',
    sourceHandle: 'right-top',
    targetHandle: 'left',
    type: 'smoothstep',
    label: $t('Paskiria'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-ark-revizija',
    source: 'node-ark',
    target: 'node-revizija',
    sourceHandle: 'right',
    targetHandle: 'left',
    type: 'smoothstep',
    label: $t('Renka'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-prezidentas-taryba',
    source: 'node-prezidentas',
    target: 'node-taryba',
    sourceHandle: 'left-bottom',
    targetHandle: 'top',
    type: 'smoothstep',
    label: $t('Įeina į'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-prezidentas-parlamentas',
    source: 'node-prezidentas',
    target: 'node-parlamentas',
    sourceHandle: 'right-bottom',
    targetHandle: 'top',
    type: 'smoothstep',
    label: $t('Įeina į'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-park-ark',
    source: 'node-park',
    target: 'node-ark',
    sourceHandle: 'left',
    targetHandle: 'left',
    type: 'smoothstep',
    label: $t('Deleguoja 10 narių į'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-taryba-parlamentas',
    source: 'node-taryba',
    target: 'node-parlamentas',
    sourceHandle: 'right',
    targetHandle: 'left',
    type: 'smoothstep',
    label: $t('Įeina į'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-park-pirmininkas',
    source: 'node-park',
    target: 'node-pirmininkas',
    sourceHandle: 'bottom',
    targetHandle: 'top',
    type: 'smoothstep',
    label: $t('Renka'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-pirmininkas-taryba',
    source: 'node-pirmininkas',
    target: 'node-taryba',
    sourceHandle: 'left',
    targetHandle: 'bottom-left',
    type: 'smoothstep',
    label: $t('Įeina į'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-park-parlamentas',
    source: 'node-park',
    target: 'node-parlamentas',
    sourceHandle: 'right',
    targetHandle: 'right',
    type: 'smoothstep',
    label: $t('Deleguoja 2 narius į'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-park-pkvo',
    source: 'node-park',
    target: 'node-pkvo',
    sourceHandle: 'bottom-right',
    targetHandle: 'top-right',
    type: 'smoothstep',
    label: $t('Renka'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
  {
    id: 'edge-pirmininkas-pkvo',
    source: 'node-pirmininkas',
    target: 'node-pkvo',
    sourceHandle: 'bottom',
    targetHandle: 'top',
    type: 'smoothstep',
    label: $t('Įeina į'),
    markerEnd: {
      type: MarkerType.ArrowClosed,
    },
  },
]
</script>
