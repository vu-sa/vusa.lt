type LogoLocale = 'lt' | 'en';

const supportedTenantAliases = new Set([
  'chgf',
  'evaf',
  'ff',
  'filf',
  'fsf',
  'gmc',
  'if',
  'kf',
  'knf',
  'mf',
  'mif',
  'sa',
  'tf',
  'tspmi',
  'vm',
  'vusa',
]);

export const getAppLogoSrc = (tenantAlias: string | undefined, locale: string): string => {
  const language: LogoLocale = locale === 'en' ? 'en' : 'lt';
  const alias = supportedTenantAliases.has(tenantAlias ?? '') ? tenantAlias! : 'vusa';

  if (alias === 'chgf' && language === 'lt') {
    return '/logos/hor/lt/vusachgf.lin.hor.balt.svg';
  }

  if (alias === 'sa') {
    return `/logos/hor/${language}/vusasa.lin.hor.tams.${language}.svg`;
  }

  return `/logos/hor/${language}/${alias === 'vusa' ? 'vusa' : `vusa${alias}`}.lin.hor.tams.svg`;
};
