import type { Component } from 'vue';
import { Home, LayoutDashboard, Settings } from '@lucide/vue';

import {
  AgendaItemIcon,
  BannerIcon,
  CalendarIcon,
  DocumentIcon,
  DutyIcon,
  FileIcon,
  InstitutionIcon,
  MeetingIcon,
  NavigationIcon,
  NewsIcon,
  PageIcon,
  PermissionIcon,
  ProblemIcon,
  QuickLinkIcon,
  ReservationIcon,
  ResourceIcon,
  RoleIcon,
  StudyProgramIcon,
  TagIcon,
  TaskIcon,
  TenantIcon,
  TypeIcon,
  UserIcon,
} from '@/Components/icons';

const EXACT_ROUTE_ICONS: Record<string, Component> = {
  dashboard: Home,
};

/**
 * Resource icon by route prefix (the first segment of a route name).
 * Covers the admin resources that aren't curated nav destinations, so that
 * recent/pinned pages and the command palette show a meaningful per-resource
 * icon instead of a single generic one.
 */
const ROUTE_PREFIX_ICONS: Record<string, Component> = {
  dashboard: LayoutDashboard,
  profile: Settings,
  administration: Settings,
  institutions: InstitutionIcon,
  users: UserIcon,
  meetings: MeetingIcon,
  agendaItems: AgendaItemIcon,
  documents: DocumentIcon,
  files: FileIcon,
  tasks: TaskIcon,
  reservations: ReservationIcon,
  resources: ResourceIcon,
  duties: DutyIcon,
  news: NewsIcon,
  pages: PageIcon,
  banners: BannerIcon,
  types: TypeIcon,
  tags: TagIcon,
  calendar: CalendarIcon,
  eventTypes: CalendarIcon,
  studyPrograms: StudyProgramIcon,
  roles: RoleIcon,
  permissions: PermissionIcon,
  problems: ProblemIcon,
  quickLinks: QuickLinkIcon,
  navigation: NavigationIcon,
  tenants: TenantIcon,
};

/**
 * Resource icon from a URL path, e.g. "/mano/meetings/123" → MeetingIcon.
 * Admin pages are served through a generic catch-all `page` route, so the
 * stored route name is unreliable ("page") — the URL is the dependable signal.
 */
function iconFromPath(url: string): Component | undefined {
  let pathname = url;
  try {
    pathname = new URL(url, 'http://localhost').pathname;
  }
  catch {
    // `url` was already a bare path.
  }

  const segments = pathname.split('/').filter(Boolean);
  // Drop the admin prefix ("/mano/<resource>/...").
  const resource = segments[0] === 'mano' ? segments[1] : segments[0];

  return resource ? ROUTE_PREFIX_ICONS[resource] : undefined;
}

/** The best icon for a stored page: its URL path (reliable for admin pages), then its route, then a generic one. */
export function resolvePageIcon(routeName?: string, url?: string): Component {
  if (url) {
    const fromPath = iconFromPath(url);
    if (fromPath) {
      return fromPath;
    }
  }

  if (routeName) {
    const exact = EXACT_ROUTE_ICONS[routeName];
    if (exact) {
      return exact;
    }

    const fromPrefix = ROUTE_PREFIX_ICONS[routeName.split('.')[0] ?? ''];
    if (fromPrefix) {
      return fromPrefix;
    }
  }

  return PageIcon;
}
