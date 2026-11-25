import type { IconifyIcon } from '@iconify/vue';
import type { ErrorBag, Errors } from '@inertiajs/core';
import type { Config as ZiggyConfig } from 'ziggy-js';

import type { NavFooterColumn, NavItem } from '@/Components/Public/Nav/types';
import type { Notification, NotificationData } from '@/Composables/useNotificationFormatting';
import type { PublicEditLink } from '@/Composables/usePublicEditLinkToast';
import type { TypesenseConfig } from '@/Search/core/types';
import type { DutyPronouns } from '@/Utils/String';
import type { ZiggyContext } from '@/Utils/ziggyConfig';

import type { LocaleEnum, ModelEnum } from './enums';

type UserTenant = Pick<App.Entities.Tenant, 'id' | 'shortname' | 'alias'>;

interface SharedTenant extends Pick<App.Entities.Tenant, 'id' | 'alias' | 'shortname' | 'fullname' | 'type' | 'primary_institution_id'> {
  primary_institution: {
    id: string;
    short_name: string | null;
    image_url: string | null;
    image_focal_point: string | null;
  } | null;
}

interface StoredPage {
  route: string;
  params?: Record<string, unknown> | [];
  title?: string | null;
  url?: string | null;
}

interface NotificationPreview extends Omit<Notification, 'data'> {
  data: Pick<NotificationData, 'category' | 'modelClass' | 'title' | 'body' | 'excerpt' | 'url' | 'color' | 'text' | 'message' | 'primaryAction'> & {
    subject?: Pick<NonNullable<NotificationData['subject']>, 'name' | 'image'>;
    object?: Pick<NonNullable<NotificationData['object']>, 'modelClass' | 'name' | 'url'>;
  };
}

interface User extends Pick<App.Entities.User,
  'id' | 'name' | 'email' | 'phone' | 'facebook_url' | 'profile_photo_path' | 'profile_photo_focal_point'
  | 'show_pronouns' | 'is_active' | 'created_at' | 'updated_at' | 'deleted_at'
> {
  pronouns: DutyPronouns;
  /** Units of the user's current and upcoming terms. */
  tenants: UserTenant[];
  isSuperAdmin: boolean;
  unreadNotifications: NotificationPreview[];
  unreadNotificationsCount: number;
  /** Uncompleted tasks assigned to the user, and the subset already past its due date. */
  tasks_count?: number;
  overdue_tasks_count?: number;
  tutorial_progress: Record<string, string> | [];
  ui_preferences: {
    pinned_pages: StoredPage[];
    recent_pages: Array<StoredPage & { visited_at: string }>;
  };
}

// PermissionMapBuilder keys each map by ModelEnum::labels(), for example eventType.
type PermissionMap = Partial<Record<string, boolean>>;

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  alias?: string;
  publicAssets?: {
    logoSrc: string;
    icons?: Record<string, IconifyIcon>;
  };
  app: {
    env: string;
    locale: LocaleEnum;
    path: string;
    url: string;
  };
  errors: Errors & ErrorBag;
  ziggyContext: ZiggyContext;
  /** Only sent on initial requests eligible for public SSR. */
  ziggy?: ZiggyConfig & { location: string };
  head?: string[];
  staging?: {
    isStaging: boolean;
    filesReadOnly: boolean;
    sharepointReadOnly: boolean;
    mailToRequester: boolean;
  };
  publicEditLink?: PublicEditLink | null;
  /** Organisation-level facts, from config/vusa.php. */
  organization: {
    contacts: { it: string; accounting: string; phone: string };
    social: { facebook: string; instagram: string; linkedin: string };
    legal: {
      company_code: string;
      vat_code: string;
      address: { street: string; city: string };
    };
    /** Public URL of the configured privacy policy page, or null when unset. */
    privacyPageUrl: string | null;
  };
  auth: {
    can: {
      index: PermissionMap;
      create: PermissionMap;
      /**
       * Coarse, class-level hint for whether the user may permanently delete a model.
       * False for every model that is not soft-deletable. The per-record decision is
       * still made server-side by the model's policy.
       */
      forceDelete: PermissionMap;
    };
    impersonating: { impersonator_name: string } | null;
    user: User;
  } | null;
  csrf_token: string;
  flash: {
    data: any;
    info: string | null;
    success: string | null;
    warning: string | null;
    error: string | null;
    toast_duration?: number | null;
    toast_description?: string | null;
    access_change_warning?: string | null;
  };
  pwa: {
    vapidPublicKey: string | null;
    hasPushSubscription: boolean;
    subscriptionEndpoints: string[];
  };
  /** The middleware shares the public search key even on admin pages. */
  typesenseConfig: Required<TypesenseConfig> | [];
  // The generated `App.Entities.Navigation` model type lacks `links`/`cols` — this
  // prop's actual shape comes from `NavigationService::getNavigationForPublic()`, not
  // the raw Eloquent model. See Components/Public/Nav/types.ts for the authoritative shape.
  mainNavigation?: NavItem[];
  /** Up to `NavigationService::FOOTER_MAX_COLUMNS` columns — see Components/Public/Nav/types.ts. */
  footerNavigation?: NavFooterColumn[];
  /** CARTO now requires an API key on basemap tile requests; null when unconfigured. */
  map: {
    cartoApiKey: string | null;
  };
  /**
   * The navigation catalog (O19): every workspace, section and create action this user may
   * see, gated and cached server-side by `App\Services\AdminNavigation\AdminNavigationCatalog`.
   * Null outside `/mano`. `label`/`description` are i18n keys — resolve with `$t()`, not printed
   * directly.
   */
  adminNavigation: {
    workspaces: Array<{
      key: string;
      label: string;
      description: string;
      experimental: boolean;
      sections: Array<{
        key: string;
        label: string;
        routeName: string;
        routeParams: Record<string, unknown>;
        entityType: ModelEnum | null;
        /** One-line description on section tiles; null for overviews. */
        description: string | null;
        collectionActions: Array<{
          key: string;
          label: string;
          target: string;
        }>;
        /** Route-name patterns (`meetings.*`) living inside this section — see `resolveActive()`. */
        matches: string[];
        /** Draw a separator before this section. */
        startsGroup: boolean;
        children?: Array<{ key: string; label: string; routeName: string }>;
      }>;
      createActions: Array<{
        key: string;
        label: string;
        description: string | null;
        entityType: ModelEnum | null;
        target: { kind: 'route'; routeName: string } | { kind: 'screen'; screen: string };
      }>;
    }>;
  } | null;
  otherLangURL?: string | null;
  /** Destination used by the tenant selector: the selected tenant's home or this route. */
  tenantSwitchTarget?: 'home' | 'same-page';
  seo: { title: string | null };
  tenants: SharedTenant[];
  institutionTypes: Array<{ id: number; title: string | null; slug: string | null; parent_id: number | null }>;
  /**
   * Every event type, global (not tenant-scoped). `name` is already the current-locale
   * string here (Spatie translatable resolves it server-side before serializing) — not
   * the raw translation array `App.Entities.EventType['name']` would suggest.
   */
  eventTypes: Array<{ id: number; slug: string; name: string }>;
  /** Configured in Settings → Sistemos paskirtys; empty outside the admin. */
  curatorDutyTypeIds: number[];
  /** Global tag lookup for rich-content news/link-list filters. `is_topic` tags drive
   *  the topic picker; the rest stay descriptive-only. */
  tags: Array<{ id: number; alias: string | null; name: string; is_topic: boolean }>;
  tenant?: Pick<App.Entities.Tenant, 'id' | 'alias' | 'shortname' | 'type'> & {
    subdomain: string;
    homeUrl: string;
    links: Array<App.Entities.QuickLink | null>;
    banners: Array<App.Entities.Banner> | [];
  };
  search: {
    calendar: Array<{
      date: string;
      id: number;
      title: string;
    } | null>;
    news: Array<{
      id: number;
      lang: 'lt' | 'en';
      permalink: string;
      publish_time: string;
      title: string;
    } | null>;
    pages: Array<{
      id: number;
      lang: 'lt' | 'en';
      permalink: string;
      title: string;
    } | null>;
    documents: Array<{
      id: number;
      title: string;
      language: 'Lietuvių' | 'English';
      anonymous_url: string;
      document_date: string;
    } | null>;
  } | null;
};
