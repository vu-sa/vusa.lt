/**
 * Centralized icon → color-class mapping for search facet accordions.
 *
 * Keeps the colored icon chips consistent across every search surface
 * (public documents / meetings / institutions + admin search). Each icon name
 * resolves to the same Tailwind color pair everywhere it appears, so adding a
 * new facet anywhere automatically inherits the right palette.
 */

/** Tailwind classes for a facet icon chip, keyed by Lucide component name. */
export const FACET_ICON_COLORS: Record<string, string> = {
  // Organization / tenant — primary brand
  Building2: 'bg-primary/10 text-primary group-hover:bg-primary/15',
  Users: 'bg-primary/10 text-primary group-hover:bg-primary/15',

  // Content / document type — blue
  FileText: 'bg-blue-500/10 text-blue-600 group-hover:bg-blue-500/15',
  Briefcase: 'bg-blue-500/10 text-blue-600 group-hover:bg-blue-500/15',

  // Language & recurring time periods — emerald
  Globe: 'bg-emerald-500/10 text-emerald-600 group-hover:bg-emerald-500/15',
  CalendarDays: 'bg-emerald-500/10 text-emerald-600 group-hover:bg-emerald-500/15',

  // Dates & categories — amber
  Calendar: 'bg-amber-500/10 text-amber-600 group-hover:bg-amber-500/15',
  CalendarCheck: 'bg-amber-500/10 text-amber-600 group-hover:bg-amber-500/15',
  Tag: 'bg-amber-500/10 text-amber-600 group-hover:bg-amber-500/15',

  // Status / completion — rose
  CheckCircle: 'bg-rose-500/10 text-rose-600 group-hover:bg-rose-500/15',
  UserCheck: 'bg-rose-500/10 text-rose-600 group-hover:bg-rose-500/15',

  // Voting / decision analytics — violet
  Vote: 'bg-violet-500/10 text-violet-600 group-hover:bg-violet-500/15',
  TrendingUp: 'bg-violet-500/10 text-violet-600 group-hover:bg-violet-500/15',
  ThumbsUp: 'bg-violet-500/10 text-violet-600 group-hover:bg-violet-500/15',
  Gavel: 'bg-violet-500/10 text-violet-600 group-hover:bg-violet-500/15',

  // Categorization — teal
  Layers: 'bg-teal-500/10 text-teal-600 group-hover:bg-teal-500/15',
  Scale: 'bg-teal-500/10 text-teal-600 group-hover:bg-teal-500/15',
};

const DEFAULT_COLOR = 'bg-primary/10 text-primary group-hover:bg-primary/15';

/** Resolve the icon-chip color classes for a given Lucide icon name. */
export function getFacetIconColor(iconName?: string): string {
  if (!iconName) {
    return DEFAULT_COLOR;
  }
  return FACET_ICON_COLORS[iconName] ?? DEFAULT_COLOR;
}
