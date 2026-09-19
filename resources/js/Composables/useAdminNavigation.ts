import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

type Section = NonNullable<PageProps['adminNavigation']>['workspaces'][number]['sections'][number];

export function useAdminNavigation() {
  const page = usePage<PageProps>();
  const workspaces = computed(() => page.props.adminNavigation?.workspaces ?? []);
  const sections = computed(() => workspaces.value.flatMap(workspace => workspace.sections));

  const sectionForRoute = (routeName: string): Section | undefined => sections.value.find(section => section.routeName === routeName);
  const hasCollectionAction = (routeName: string, key: string): boolean =>
    sectionForRoute(routeName)?.collectionActions.some(action => action.key === key) ?? false;

  return { workspaces, sections, sectionForRoute, hasCollectionAction };
}
