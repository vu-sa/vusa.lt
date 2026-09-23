import type { Component } from 'vue';
import { Bell, ChartNoAxesCombined, FileStack, Folder, HardDrive, LayoutDashboard, ListTree, Mail, Settings2, Tags } from 'lucide-vue-next';

import type { AdminSection } from '@/Composables/useAdminNavigation';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';

const sectionIcons: Record<string, Component> = {
  apzvalga: LayoutDashboard,
  pranesimai: Bell,
  kategorijos: Tags,
  failai: Folder,
  nustatymai: Settings2,
  sistemos_busena: ChartNoAxesCombined,
  laisku_eile: Mail,
  rep_metrics: ChartNoAxesCombined,
  pagalbos_uzklausos: ListTree,
  sharepoint_failai: HardDrive,
};

export const sectionIcon = (section: AdminSection): Component =>
  getEntityTypeDefinition(section.entityType ?? '')?.icon ?? sectionIcons[section.key] ?? FileStack;
