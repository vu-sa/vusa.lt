/**
 * adminPageCatalog - Curated catalog of admin destinations.
 *
 * Single source of truth for "all the admin pages a user can navigate to".
 * Consumed by:
 *  - useCommandActions (command palette navigation/create actions)
 *  - the global visit tracker (only catalog pages get recorded, so recent
 *    history always has clean labels + icons)
 *  - RecentlyVisitedSection / command palette "Neseniai" list (label + icon
 *    resolution for stored route names)
 *
 * Labels are i18n keys (the flat JSON convention uses the Lithuanian string
 * as the key) and are resolved with `$t()` at the consumer so they stay
 * reactive to locale changes.
 */

import { computed, type Component } from 'vue';
import { usePage } from '@inertiajs/vue3';

import {
  Home,
  Search,
  Settings,
  Plus,
  LayoutDashboard,
} from 'lucide-vue-next';

import {
  AgendaItemIcon,
  BannerIcon,
  CalendarIcon,
  CategoryIcon,
  DocumentIcon,
  DutyIcon,
  FileIcon,
  InstitutionIcon,
  MeetingIcon,
  MembershipIcon,
  NavigationIcon,
  NewsIcon,
  PageIcon,
  PermissionIcon,
  ProblemIcon,
  QuickLinkIcon,
  RelationshipIcon,
  ReservationIcon,
  ResourceIcon,
  RoleIcon,
  StudyProgramIcon,
  TagIcon,
  TaskIcon,
  TenantIcon,
  TrainingIcon,
  TypeIcon,
  UserIcon,
} from '@/Components/icons';

export type CatalogCategory = 'navigation' | 'create';

export interface AuthCan {
  create?: Record<string, boolean | undefined>;
  read?: Record<string, boolean | undefined>;
}

export interface AdminPageEntry {
  /** Stable id (used as command action id) */
  id: string;
  /** Ziggy route name */
  routeName: string;
  /** i18n key resolved with $t() at the consumer */
  labelKey: string;
  icon: Component;
  category: CatalogCategory;
  keywords: string[];
  /** Optional permission predicate; entry is hidden when it returns false */
  can?: (can: AuthCan) => boolean;
}

/**
 * The full catalog. Add new admin destinations here.
 */
export const ADMIN_PAGE_CATALOG: AdminPageEntry[] = [
  // ===== Navigation =====
  {
    id: 'nav-dashboard',
    routeName: 'dashboard',
    labelKey: 'Pradžia',
    icon: Home,
    category: 'navigation',
    keywords: ['home', 'dashboard', 'pradzia', 'pagrindinis'],
  },
  {
    id: 'nav-representation',
    routeName: 'dashboard.atstovavimas',
    labelKey: 'Atstovavimas',
    icon: LayoutDashboard,
    category: 'navigation',
    keywords: ['visak', 'representation', 'atstovavimas', 'posedziai'],
  },
  {
    id: 'nav-meetings',
    routeName: 'meetings.index',
    labelKey: 'Posėdžiai',
    icon: MeetingIcon,
    category: 'navigation',
    keywords: ['meetings', 'posedziai', 'susirinkimai'],
    can: c => !!c.read?.meeting,
  },
  {
    id: 'nav-search-meetings',
    routeName: 'meetings.search',
    labelKey: 'Ieškoti posėdžių',
    icon: Search,
    category: 'navigation',
    keywords: ['search', 'meetings', 'ieskoti', 'posedziai', 'paieska'],
    can: c => !!c.read?.meeting,
  },
  {
    id: 'nav-institutions',
    routeName: 'institutions.index',
    labelKey: 'Institucijos',
    icon: InstitutionIcon,
    category: 'navigation',
    keywords: ['institutions', 'institucijos', 'organizacijos'],
    can: c => !!c.read?.institution,
  },
  {
    id: 'nav-users',
    routeName: 'users.index',
    labelKey: 'Naudotojai',
    icon: UserIcon,
    category: 'navigation',
    keywords: ['users', 'naudotojai', 'vartotojai', 'zmones'],
    can: c => !!c.read?.user,
  },
  {
    id: 'nav-duties',
    routeName: 'duties.index',
    labelKey: 'Pareigybės',
    icon: DutyIcon,
    category: 'navigation',
    keywords: ['duties', 'pareigybes', 'pareigos'],
    can: c => !!c.read?.duty,
  },
  {
    id: 'nav-tasks',
    routeName: 'tasks.index',
    labelKey: 'Užduotys',
    icon: TaskIcon,
    category: 'navigation',
    keywords: ['tasks', 'uzduotys', 'darbai'],
    can: c => !!c.read?.task,
  },
  {
    id: 'nav-reservations',
    routeName: 'reservations.index',
    labelKey: 'Rezervacijos',
    icon: ReservationIcon,
    category: 'navigation',
    keywords: ['reservations', 'rezervacijos', 'uzsakymai'],
    can: c => !!c.read?.reservation,
  },
  {
    id: 'nav-calendar',
    routeName: 'calendar.index',
    labelKey: 'Kalendorius',
    icon: CalendarIcon,
    category: 'navigation',
    keywords: ['calendar', 'kalendorius', 'ivykiai'],
    can: c => !!c.read?.calendar,
  },
  {
    id: 'nav-news',
    routeName: 'news.index',
    labelKey: 'Naujienos',
    icon: NewsIcon,
    category: 'navigation',
    keywords: ['news', 'naujienos', 'straipsniai'],
    can: c => !!c.read?.news,
  },
  {
    id: 'nav-profile',
    routeName: 'profile',
    labelKey: 'Profilis',
    icon: Settings,
    category: 'navigation',
    keywords: ['profile', 'profilis', 'nustatymai', 'settings'],
  },

  // ===== Create =====
  {
    id: 'create-meeting',
    routeName: 'meetings.create',
    labelKey: 'Naujas posėdis',
    icon: Plus,
    category: 'create',
    keywords: ['create', 'new', 'meeting', 'naujas', 'posedis', 'sukurti'],
    can: c => !!c.create?.meeting,
  },
  {
    id: 'create-news',
    routeName: 'news.create',
    labelKey: 'Nauja naujiena',
    icon: Plus,
    category: 'create',
    keywords: ['create', 'new', 'news', 'nauja', 'naujiena', 'sukurti', 'straipsnis'],
    can: c => !!c.create?.news,
  },
  {
    id: 'create-reservation',
    routeName: 'reservations.create',
    labelKey: 'Nauja rezervacija',
    icon: Plus,
    category: 'create',
    keywords: ['create', 'new', 'reservation', 'nauja', 'rezervacija', 'sukurti'],
    can: c => !!c.create?.reservation,
  },
  {
    id: 'create-institution',
    routeName: 'institutions.create',
    labelKey: 'Nauja institucija',
    icon: Plus,
    category: 'create',
    keywords: ['create', 'new', 'institution', 'nauja', 'institucija', 'sukurti'],
    can: c => !!c.create?.institution,
  },
  {
    id: 'create-duty',
    routeName: 'duties.create',
    labelKey: 'Nauja pareigybė',
    icon: Plus,
    category: 'create',
    keywords: ['create', 'new', 'duty', 'nauja', 'pareigybe', 'sukurti'],
    can: c => !!c.create?.duty,
  },
];

/**
 * Resolve a live route name to its catalog entry (undefined if not catalogued).
 * Used by the visit tracker to record only known pages.
 *
 * Falls back to the parent index route for show/edit pages
 * (e.g. `users.edit` → `users.index`).
 */
export function resolveCatalogEntryByRoute(routeName: string | undefined): AdminPageEntry | undefined {
  if (!routeName) {
    return undefined;
  }

  // Exact match first
  const exact = ADMIN_PAGE_CATALOG.find(entry => entry.routeName === routeName);
  if (exact) {
    return exact;
  }

  // Fallback: strip the last segment and try the parent index route
  // e.g. "users.edit" → "users.index", "meetings.show" → "meetings.index"
  const segments = routeName.split('.');
  if (segments.length > 1) {
    const parentRoute = `${segments.slice(0, -1).join('.')}.index`;
    const parent = ADMIN_PAGE_CATALOG.find(entry => entry.routeName === parentRoute);
    if (parent) {
      return parent;
    }
  }

  return undefined;
}

/**
 * Returns the permission-filtered catalog for the current user.
 */
export function useAdminPageCatalog() {
  const page = usePage();

  const can = computed<AuthCan>(() => {
    return (page.props.auth as { can?: AuthCan })?.can || {};
  });

  const catalog = computed<AdminPageEntry[]>(() => {
    return ADMIN_PAGE_CATALOG.filter(entry => !entry.can || entry.can(can.value));
  });

  return { catalog };
}

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
  sharepointFiles: FileIcon,
  tasks: TaskIcon,
  reservations: ReservationIcon,
  resources: ResourceIcon,
  duties: DutyIcon,
  news: NewsIcon,
  pages: PageIcon,
  banners: BannerIcon,
  types: TypeIcon,
  categories: CategoryIcon,
  tags: TagIcon,
  calendar: CalendarIcon,
  trainings: TrainingIcon,
  studyPrograms: StudyProgramIcon,
  roles: RoleIcon,
  permissions: PermissionIcon,
  problems: ProblemIcon,
  quickLinks: QuickLinkIcon,
  navigation: NavigationIcon,
  tenants: TenantIcon,
  memberships: MembershipIcon,
  relationships: RelationshipIcon,
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

/**
 * Resolve the best icon for a page. Prefers the URL path (reliable for admin
 * pages), then the curated catalog icon / route prefix, and finally a generic
 * page icon.
 */
export function resolvePageIcon(routeName?: string, url?: string): Component {
  if (url) {
    const fromPath = iconFromPath(url);
    if (fromPath) {
      return fromPath;
    }
  }

  if (routeName) {
    const catalogEntry = resolveCatalogEntryByRoute(routeName);
    if (catalogEntry) {
      return catalogEntry.icon;
    }

    const prefix = routeName.split('.')[0];
    if (ROUTE_PREFIX_ICONS[prefix]) {
      return ROUTE_PREFIX_ICONS[prefix];
    }
  }

  return PageIcon;
}
