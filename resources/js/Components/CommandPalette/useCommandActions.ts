/**
 * useCommandActions - Registry of static command palette actions
 *
 * Provides permission-aware quick actions for navigation and creation.
 * Actions are automatically filtered based on user permissions.
 */

import { computed, type Component } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

// Import icons directly for tree-shaking
import {
  Home,
  Search,
  Settings,
  Plus,
  LayoutDashboard,
} from 'lucide-vue-next';

import {
  MeetingIcon,
  NewsIcon,
  ReservationIcon,
  InstitutionIcon,
  UserIcon,
  DutyIcon,
  TaskIcon,
  CalendarIcon,
  AgendaItemIcon,
} from '@/Components/icons';

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

interface AuthCan {
  create?: {
    meeting?: boolean;
    news?: boolean;
    reservation?: boolean;
    institution?: boolean;
    duty?: boolean;
    user?: boolean;
  };
  read?: {
    meeting?: boolean;
    user?: boolean;
    institution?: boolean;
    duty?: boolean;
    task?: boolean;
    reservation?: boolean;
    calendar?: boolean;
    news?: boolean;
  };
}

/**
 * Returns computed list of available command actions based on permissions
 */
export function useCommandActions() {
  const page = usePage();

  const can = computed<AuthCan>(() => {
    return (page.props.auth as { can?: AuthCan })?.can || {};
  });

  const actions = computed<CommandAction[]>(() => {
    const result: CommandAction[] = [];

    // ==================
    // Navigation Actions
    // ==================

    // Dashboard - always available
    result.push({
      id: 'nav-dashboard',
      label: $t('Pradžia'),
      keywords: ['home', 'dashboard', 'pradzia', 'pagrindinis'],
      icon: Home,
      category: 'navigation',
      action: () => router.visit(route('dashboard')),
    });

    // Representation dashboard
    result.push({
      id: 'nav-representation',
      label: $t('Atstovavimas'),
      keywords: ['visak', 'representation', 'atstovavimas', 'posedziai'],
      icon: LayoutDashboard,
      category: 'navigation',
      action: () => router.visit(route('dashboard.atstovavimas')),
    });

    // Meetings
    if (can.value.read?.meeting) {
      result.push({
        id: 'nav-meetings',
        label: $t('Posėdžiai'),
        keywords: ['meetings', 'posedziai', 'susirinkimai'],
        icon: MeetingIcon,
        category: 'navigation',
        action: () => router.visit(route('meetings.index')),
      });
    }

    // Search meetings
    if (can.value.read?.meeting) {
      result.push({
        id: 'nav-search-meetings',
        label: $t('Ieškoti posėdžių'),
        keywords: ['search', 'meetings', 'ieskoti', 'posedziai', 'paieska'],
        icon: Search,
        category: 'navigation',
        action: () => router.visit(route('meetings.search')),
      });
    }

    // Institutions
    if (can.value.read?.institution) {
      result.push({
        id: 'nav-institutions',
        label: $t('Institucijos'),
        keywords: ['institutions', 'institucijos', 'organizacijos'],
        icon: InstitutionIcon,
        category: 'navigation',
        action: () => router.visit(route('institutions.index')),
      });
    }

    // Users
    if (can.value.read?.user) {
      result.push({
        id: 'nav-users',
        label: $t('Naudotojai'),
        keywords: ['users', 'naudotojai', 'vartotojai', 'zmones'],
        icon: UserIcon,
        category: 'navigation',
        action: () => router.visit(route('users.index')),
      });
    }

    // Duties
    if (can.value.read?.duty) {
      result.push({
        id: 'nav-duties',
        label: $t('Pareigybės'),
        keywords: ['duties', 'pareigybes', 'pareigos'],
        icon: DutyIcon,
        category: 'navigation',
        action: () => router.visit(route('duties.index')),
      });
    }

    // Tasks
    if (can.value.read?.task) {
      result.push({
        id: 'nav-tasks',
        label: $t('Užduotys'),
        keywords: ['tasks', 'uzduotys', 'darbai'],
        icon: TaskIcon,
        category: 'navigation',
        action: () => router.visit(route('tasks.index')),
      });
    }

    // Reservations
    if (can.value.read?.reservation) {
      result.push({
        id: 'nav-reservations',
        label: $t('Rezervacijos'),
        keywords: ['reservations', 'rezervacijos', 'uzsakymai'],
        icon: ReservationIcon,
        category: 'navigation',
        action: () => router.visit(route('reservations.index')),
      });
    }

    // Calendar
    if (can.value.read?.calendar) {
      result.push({
        id: 'nav-calendar',
        label: $t('Kalendorius'),
        keywords: ['calendar', 'kalendorius', 'ivykiai'],
        icon: CalendarIcon,
        category: 'navigation',
        action: () => router.visit(route('calendar.index')),
      });
    }

    // News
    if (can.value.read?.news) {
      result.push({
        id: 'nav-news',
        label: $t('Naujienos'),
        keywords: ['news', 'naujienos', 'straipsniai'],
        icon: NewsIcon,
        category: 'navigation',
        action: () => router.visit(route('news.index')),
      });
    }

    // Profile - always available
    result.push({
      id: 'nav-profile',
      label: $t('Profilis'),
      keywords: ['profile', 'profilis', 'nustatymai', 'settings'],
      icon: Settings,
      category: 'navigation',
      action: () => router.visit(route('profile')),
    });

    // ==================
    // Create Actions
    // ==================

    // Create meeting
    if (can.value.create?.meeting) {
      result.push({
        id: 'create-meeting',
        label: $t('Naujas posėdis'),
        keywords: ['create', 'new', 'meeting', 'naujas', 'posedis', 'sukurti'],
        icon: Plus,
        category: 'create',
        action: () => router.visit(route('meetings.create')),
      });
    }

    // Create news
    if (can.value.create?.news) {
      result.push({
        id: 'create-news',
        label: $t('Nauja naujiena'),
        keywords: ['create', 'new', 'news', 'nauja', 'naujiena', 'sukurti', 'straipsnis'],
        icon: Plus,
        category: 'create',
        action: () => router.visit(route('news.create')),
      });
    }

    // Create reservation
    if (can.value.create?.reservation) {
      result.push({
        id: 'create-reservation',
        label: $t('Nauja rezervacija'),
        keywords: ['create', 'new', 'reservation', 'nauja', 'rezervacija', 'sukurti'],
        icon: Plus,
        category: 'create',
        action: () => router.visit(route('reservations.create')),
      });
    }

    // Create institution
    if (can.value.create?.institution) {
      result.push({
        id: 'create-institution',
        label: $t('Nauja institucija'),
        keywords: ['create', 'new', 'institution', 'nauja', 'institucija', 'sukurti'],
        icon: Plus,
        category: 'create',
        action: () => router.visit(route('institutions.create')),
      });
    }

    // Create duty
    if (can.value.create?.duty) {
      result.push({
        id: 'create-duty',
        label: $t('Nauja pareigybė'),
        keywords: ['create', 'new', 'duty', 'nauja', 'pareigybe', 'sukurti'],
        icon: Plus,
        category: 'create',
        action: () => router.visit(route('duties.create')),
      });
    }

    return result;
  });

  /**
   * Filter actions by search query
   */
  const filterActions = (query: string): CommandAction[] => {
    if (!query.trim()) {
      return actions.value;
    }

    const lowerQuery = query.toLowerCase().trim();

    return actions.value.filter((action) => {
      // Check label
      if (action.label.toLowerCase().includes(lowerQuery)) {
        return true;
      }

      // Check keywords
      return action.keywords.some(keyword =>
        keyword.toLowerCase().includes(lowerQuery),
      );
    });
  };

  return {
    actions,
    filterActions,
  };
}
