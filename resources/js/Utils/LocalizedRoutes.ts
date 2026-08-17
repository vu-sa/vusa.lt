/**
 * Localized URL segments — "/lt/naujiena/x" and "/en/news/x" are the same route.
 *
 * For the language the page is currently rendered in, nothing is needed: the segments are
 * route parameters and Laravel's `URL::defaults()` (set per request by `SetLocale`) is
 * serialized into Ziggy, so a plain `route('news', { news })` already produces the right slug.
 *
 * This module is for the other case — building a URL in a language that is *not* the current
 * one, e.g. an admin previewing a Lithuanian article while using the English interface.
 *
 * Both tables below mirror `App\Support\LocalizedRouteSlugs`;
 * `tests/Feature/System/LocalizedRouteSlugsTest.php` fails if they drift from the routes.
 */
export const LOCALIZED_ROUTE_SLUGS: Record<string, Record<string, string>> = {
  newsArchiveString: { lt: 'naujienos', en: 'news' },
  newsString: { lt: 'naujiena', en: 'news' },
  registrationString: { lt: 'registracija', en: 'registration' },
  curatorRegistrationString: {
    lt: 'registracija-i-kuratoriu-programa',
    en: 'registration-to-mentor-program',
  },
  documentsString: { lt: 'dokumentai', en: 'documents' },
  searchString: { lt: 'paieska', en: 'search' },
  meetingsString: { lt: 'posedziai', en: 'meetings' },
  contactsString: { lt: 'kontaktai', en: 'contacts' },
  studentRepsString: { lt: 'studentu-atstovai', en: 'student-representatives' },
  contactCategoryString: { lt: 'kategorija', en: 'category' },
};

/** Which localized parameters each route declares, so no unrelated slug lands in the query string. */
export const ROUTE_SLUG_PARAMETERS: Record<string, string[]> = {
  'news': ['newsString'],
  'newsArchive': ['newsArchiveString'],
  'registrationPage': ['registrationString'],
  'curatorRegistrations': ['curatorRegistrationString'],
  'documents': ['documentsString'],
  'search': ['searchString'],
  'publicMeetings.index': ['meetingsString'],
  'publicMeetings.show': ['meetingsString'],
  'contacts': ['contactsString'],
  'contacts.institution': ['contactsString'],
  'contacts.alias': ['contactsString'],
  'contacts.dutyType': ['contactsString'],
  'contacts.studentRepresentatives': ['contactsString', 'studentRepsString'],
  'contacts.category': ['contactsString', 'contactCategoryString'],
};

/** The slug a parameter takes in a locale, falling back to Lithuanian. */
export function localizedSlug(parameter: string, locale: string): string {
  const slugs = LOCALIZED_ROUTE_SLUGS[parameter] ?? {};

  return slugs[locale] ?? slugs.lt ?? '';
}

/**
 * Build a route URL in an explicit language.
 *
 * @param locale the language the URL should be in — not necessarily the page's
 */
export function localizedRoute(
  name: string,
  parameters: Record<string, unknown> = {},
  locale = 'lt',
): string {
  const slugs = Object.fromEntries(
    (ROUTE_SLUG_PARAMETERS[name] ?? []).map(parameter => [parameter, localizedSlug(parameter, locale)]),
  );

  return route(name, { ...slugs, lang: locale, ...parameters });
}
