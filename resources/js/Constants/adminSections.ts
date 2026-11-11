import { trans as $t } from 'laravel-vue-i18n';
import type { Component } from 'vue';
import { Bell, BookA, BookOpen, BookOpenCheck, ChartNoAxesCombined, ChartNoAxesColumn, FileStack, Folder, LayoutDashboard, ListTree, Mail, MailOpen, MailQuestion, Route, Settings2, Shapes, ShieldCheck, Tags, UsersRound, Waypoints } from '@lucide/vue';

import type { NavigationTileItem } from '@/Components/Patterns/NavigationTiles.vue';
import { sectionHref, type AdminSection } from '@/Composables/useAdminNavigation';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';

const sectionIcons: Record<string, Component> = {
  apzvalga: LayoutDashboard,
  kelias: Route,
  wiki: BookOpen,
  wiki_graph: Waypoints,
  wiki_mine: BookOpenCheck,
  rodykle: BookA,
  padaliniu_apzvalga: ChartNoAxesColumn,
  atstovai: UsersRound,
  pranesimai: Bell,
  atsakomybes: ShieldCheck,
  veiklos_uzklausos: MailQuestion,
  kategorijos: Tags,
  problemu_kategorijos: Tags,
  tipai: Shapes,
  failai: Folder,
  nustatymai: Settings2,
  sistemos_busena: ChartNoAxesCombined,
  laisku_eile: Mail,
  laisku_perziura: MailOpen,
  pagalbos_uzklausos: ListTree,
};

export const sectionIcon = (section: AdminSection): Component =>
  getEntityTypeDefinition(section.entityType ?? '')?.icon ?? sectionIcons[section.key] ?? FileStack;

export const sectionTile = (section: AdminSection): NavigationTileItem => ({
  key: section.key,
  href: sectionHref(section),
  label: $t(section.label),
  description: section.description ? $t(section.description) : null,
  icon: sectionIcon(section),
});
