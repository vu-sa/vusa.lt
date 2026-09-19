import type { Component } from 'vue';
import { GraduationCap, Globe, Home, CalendarCheck, Settings2, Users } from 'lucide-vue-next';

/**
 * Icon per navigation-catalog workspace key. The catalog ships no icons (only `entityType` per
 * section), and a workspace is not an entity type, so this is the one presentation map for them.
 */
export const adminWorkspaceIcons: Record<string, Component> = {
  pradzia: Home,
  atstovavimas: GraduationCap,
  rezervacijos: CalendarCheck,
  svetaine: Globe,
  organizacija: Users,
  sistema: Settings2,
};

export const workspaceIcon = (key: string): Component => adminWorkspaceIcons[key] ?? Home;
