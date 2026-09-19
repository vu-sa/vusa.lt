import { computed, type Component } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Home, Plus, Search, Settings } from 'lucide-vue-next';

import { getEntityTypeDefinition } from '@/Constants/entityTypes';

export type ActionCategory = 'navigation' | 'create' | 'action';

export interface CommandAction {
  id: string;
  label: string;
  keywords: string[];
  icon: Component;
  category: ActionCategory;
  action: () => void;
  shortcut?: string;
}

export function useCommandActions() {
  const page = usePage<PageProps>();

  const actions = computed<CommandAction[]>(() => {
    const navigation = (page.props.adminNavigation?.workspaces ?? [])
      .flatMap(workspace => workspace.sections)
      .map(section => ({
        id: `nav-${section.key}`,
        label: $t(section.label),
        keywords: [section.key, section.routeName],
        icon: section.entityType ? getEntityTypeDefinition(section.entityType)?.icon ?? Home : Home,
        category: 'navigation' as const,
        action: () => router.visit(route(section.routeName, section.routeParams)),
      }));

    const create = (page.props.adminNavigation?.workspaces ?? [])
      .flatMap(workspace => workspace.createActions)
      .map(action => ({
        id: `create-${action.key}`,
        label: $t(action.label),
        keywords: [action.key, action.target.kind],
        icon: Plus,
        category: 'create' as const,
        action: () => {
          if (action.target.kind === 'route') {
            router.visit(route(action.target.routeName));
          }
        },
      }));

    return [
      ...navigation,
      ...create,
      {
        id: 'nav-search',
        label: $t('Paieška'),
        keywords: ['search', 'paieska'],
        icon: Search,
        category: 'navigation',
        action: () => router.visit(route('search.index')),
      },
      {
        id: 'nav-profile',
        label: $t('Profilis'),
        keywords: ['profile', 'profilis'],
        icon: Settings,
        category: 'navigation',
        action: () => router.visit(route('profile')),
      },
    ];
  });

  const filterActions = (query: string): CommandAction[] => {
    const normalized = query.trim().toLowerCase();

    if (!normalized) {
      return actions.value;
    }

    return actions.value.filter(action => [action.label, ...action.keywords]
      .some(value => value.toLowerCase().includes(normalized)));
  };

  return { actions, filterActions };
}
