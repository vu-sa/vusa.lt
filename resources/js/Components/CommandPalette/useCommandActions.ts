import { computed, type Component } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Bell, Plus, Radio, Settings, ShieldCheck } from 'lucide-vue-next';

import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import { useActionWindow, type OpenOptions } from '@/Composables/useActionWindow';
import { sectionHref, useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useStartFm } from '@/Composables/useStartFm';

export type ActionCategory = 'navigation' | 'create' | 'action';

export interface CommandAction {
  id: string;
  label: string;
  keywords: string[];
  icon: Component;
  category: ActionCategory;
  action: () => void;
  shortcut?: string;
  /** The catalog workspace this entry lives in: its secondary label, and what ranks it first. */
  workspaceKey?: string;
  workspaceLabel?: string;
  /** Present on catalog sections, which can be pinned. */
  page?: { routeName: string; href: string; title: string };
}

/** Catalog `screen` targets are ActionWindow screens; each one is the first screen of a flow. */
const flowForScreen: Record<string, OpenOptions['flow']> = {
  'meeting.institution': 'meeting.create',
  'checkin.institution': 'check-in',
  'meeting.pick': 'meeting.complete',
};

export function useCommandActions() {
  const { workspaces, activeWorkspace } = useAdminNavigation();
  const actionWindow = useActionWindow();
  const startFm = useStartFm();

  const actions = computed<CommandAction[]>(() => {
    const navigation = workspaces.value.flatMap(workspace => workspace.sections.map((section): CommandAction => {
      const href = sectionHref(section);
      const label = $t(section.label);
      // Pins are stored by path (that is what visit tracking records), so pin by the relative URL.
      const pinHref = route(section.routeName, section.routeParams, false);

      return {
        id: `nav-${workspace.key}-${section.key}`,
        label,
        keywords: [section.key, section.routeName],
        icon: (section.entityType && getEntityTypeDefinition(section.entityType)?.icon) || workspaceIcon(workspace.key),
        category: 'navigation',
        action: () => router.visit(href),
        workspaceKey: workspace.key,
        workspaceLabel: $t(workspace.label),
        page: { routeName: section.routeName, href: pinHref, title: label },
      };
    }));

    const create = workspaces.value.flatMap(workspace => workspace.createActions.map((action): CommandAction => ({
      id: `create-${action.key}`,
      label: $t(action.label),
      keywords: [action.key, action.target.kind],
      icon: (action.entityType && getEntityTypeDefinition(action.entityType)?.icon) || Plus,
      category: 'create',
      action: () => {
        if (action.target.kind === 'route') {
          router.visit(route(action.target.routeName));
        }
        else {
          actionWindow.open({ flow: flowForScreen[action.target.screen] });
        }
      },
      workspaceKey: workspace.key,
      workspaceLabel: $t(workspace.label),
    })));

    return [
      ...navigation,
      ...create,
      {
        id: 'nav-profile',
        label: $t('Profilis'),
        keywords: ['profile', 'profilis'],
        icon: Settings,
        category: 'navigation',
        action: () => router.visit(route('profile')),
      },
      {
        id: 'nav-roles',
        label: $t('shell.account.roles'),
        keywords: ['roles', 'duties', 'permissions', 'roles ir pareigybes', 'teises'],
        icon: ShieldCheck,
        category: 'navigation',
        action: () => router.visit(route('profile.roles')),
      },
      {
        id: 'nav-profile-notifications',
        label: $t('shell.account.notifications'),
        keywords: ['notifications', 'pranesimai', 'nustatymai'],
        icon: Bell,
        category: 'navigation',
        action: () => router.visit(route('profile.notifications')),
      },
      {
        id: 'action-start-fm',
        label: $t('Klausyti START FM'),
        keywords: ['start fm', 'radijas', 'radio'],
        icon: Radio,
        category: 'action',
        action: startFm.open,
      },
    ];
  });

  /** Entries in the workspace the user is standing in come first; order is otherwise the catalog's. */
  const rankByWorkspace = <T extends { workspaceKey?: string }>(items: T[]): T[] => {
    const current = activeWorkspace.value?.key;

    return [...items].sort((a, b) => Number(b.workspaceKey === current) - Number(a.workspaceKey === current));
  };

  const filterActions = (query: string): CommandAction[] => {
    const normalized = query.trim().toLowerCase();
    const matching = normalized
      ? actions.value.filter(action => [action.label, action.workspaceLabel ?? '', ...action.keywords]
          .some(value => value.toLowerCase().includes(normalized)))
      : actions.value;

    return rankByWorkspace(matching);
  };

  /** The catalog workspace that owns an entity type, so search hits can rank by workspace too. */
  const workspaceKeyForEntity = (entityType: string): string | undefined =>
    workspaces.value.find(workspace => workspace.sections.some(section => section.entityType === entityType))?.key;

  return { actions, filterActions, rankByWorkspace, workspaceKeyForEntity, activeWorkspace };
}
