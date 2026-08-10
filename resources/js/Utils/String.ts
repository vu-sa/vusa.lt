export const pluralizeModels = (word: string, forPermissions = true) => {
  if (word.endsWith('y')) {
    return `${word.slice(0, -1)}ies`;
  }

  // When the permissions where created, the Str::class was used to pluralize
  // As to stay consistent, by default these words will be pluralized but their
  // semantic meaning is not the same in this application as those words may
  // imply.

  if (['navigation', 'calendar'].includes(word)) {
    return forPermissions ? `${word}s` : word;
  }

  if (word.endsWith('s')) {
    return word;
  }

  return `${word}s`;
};

/**
 * Detect if a Lithuanian word is feminine based on its ending
 * Feminine endings: -a, -ė, -is (some), -ija, -tis
 * Masculine endings: -as, -is (most), -us, -ys
 */
export const isLithuanianFeminine = (word: string): boolean => {
  const lowercased = word.toLowerCase().trim();

  // Feminine patterns (order matters - check longer patterns first)
  if (lowercased.endsWith('ija')) return true; // komisija, kolegija
  if (lowercased.endsWith('tis')) return true; // atis patterns (but rare)
  if (lowercased.endsWith('yba')) return true; // taryba
  if (lowercased.endsWith('a') && !lowercased.endsWith('as')) return true; // komisija, taryba
  if (lowercased.endsWith('ė')) return true; // grupė

  // Masculine by default (as, is, us, ys, etc.)
  return false;
};

/**
 * Pluralize a Lithuanian word and return "Visi" or "Visos" accordingly
 * Returns { word: pluralized word, visi: "Visi" or "Visos" }
 */
export const pluralizeLithuanian = (word: string): { word: string; visi: string } => {
  const isFeminine = isLithuanianFeminine(word);
  const lowercased = word.toLowerCase().trim();

  let pluralized = word;

  // Pluralize based on ending
  if (lowercased.endsWith('ija')) {
    // komisija -> komisijos
    pluralized = `${word.slice(0, -1)}os`;
  }
  else if (lowercased.endsWith('yba')) {
    // taryba -> tarybos
    pluralized = `${word.slice(0, -1)}os`;
  }
  else if (lowercased.endsWith('a') && !lowercased.endsWith('as')) {
    // -a -> -os (feminine)
    pluralized = `${word.slice(0, -1)}os`;
  }
  else if (lowercased.endsWith('ė')) {
    // grupė -> grupės
    pluralized = `${word.slice(0, -1)}ės`;
  }
  else if (lowercased.endsWith('as')) {
    // dekanatas -> dekanatuose? No, just keep nominative plural: dekanatai
    pluralized = `${word.slice(0, -2)}ai`;
  }
  else if (lowercased.endsWith('is')) {
    // -is -> -iai (masculine)
    pluralized = `${word.slice(0, -2)}iai`;
  }
  else if (lowercased.endsWith('us')) {
    // -us -> -ūs or -ai
    pluralized = `${word.slice(0, -2)}ai`;
  }
  else if (lowercased.endsWith('ys')) {
    // -ys -> -iai
    pluralized = `${word.slice(0, -2)}iai`;
  }

  return {
    word: pluralized.toLowerCase(),
    visi: isFeminine ? 'Visos' : 'Visi',
  };
};

export const genitivize = (name: string | null) => {
  if (name === null) {
    return '';
  }

  return name
    .replace(/a$/, 'os')
    .replace(/as$/, 'o')
    .replace(/ė$/, 'ės')
    .replace(/is$/, 'io')
    .replace(/iai$/, 'ių')
    .replace(/ė$/, 'ės');
};

export const addressivize = (name: string | null | undefined) => {
  if (name === null || name === undefined) {
    return '';
  }

  return name
    .replace(/as$/, 'ai')
    .replace(/ė$/, 'e')
    .replace(/is$/, 'i')
    .replace(/us$/, 'au')
    .replace(/ys$/, 'y');
};

export const genitivizeEveryWord = (name: string | null) => {
  if (name === null) {
    return '';
  }

  // delimit by spaces
  const words = name.split(' ');

  // genitivize each word
  const genitivizedWords = words.map(word => genitivize(word));

  // join back together
  return genitivizedWords.join(' ');
};

export const splitFileNameAndExtension = (fileName: string) => {
  const parts = fileName.split('.');
  const extension = `.${parts.pop()}`;
  const name = parts.join('.');

  return { name, extension };
};

/**
 * Get faculty name from padalinys.fullname
 * @param padalinys
 * @returns facultyName
 * @example getFacultyName({fullname: "Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete"}) => "Matematikos ir informatikos fakultetas"
 */

export const getFacultyName = ({ fullname }: { fullname: string }) => {
  // split string into two parts, separated by string "Vilniaus universiteto Studentų atstovybė"
  let facultyName = fullname.split(
    'Vilniaus universiteto Studentų atstovybė',
  )[1];

  if (facultyName === undefined) {
    return '';
  }

  // change faculty name only at the string ending from "ete" to "etas"
  if (facultyName.endsWith('ete')) {
    facultyName = facultyName.replace('ete', 'etas');
  }
  // also apply this to "tre" to "tas"
  if (facultyName.endsWith('tre')) {
    facultyName = facultyName.replace('tre', 'tras');
  }

  // also if ends with "ykloje", change to "ykla"
  if (facultyName.endsWith('ykloje')) {
    facultyName = facultyName.replace('ykloje', 'ykla');
  }

  // change "ute" to "utas"
  if (facultyName.endsWith('ute')) {
    facultyName = facultyName.replace('ute', 'utas');
  }

  if (facultyName.endsWith('joje')) {
    facultyName = facultyName.replace('joje', 'ja');
  }

  return facultyName;
};

export const capitalize = (word: string) => {
  return word.charAt(0).toUpperCase() + word.slice(1);
};

/**
 * Nominative agent-noun stems that can head a Lithuanian duty title, each mapped to the
 * masculine singular ending it takes. A duty name is inflected on this word wherever it
 * sits in the title, so "Studentų atstovas VU FF Taryboje" turns on "atstovas" instead of
 * being left alone because the title ends in a locative.
 *
 * A curated vocabulary rather than a suffix rule, because from the outside the neighbouring
 * words are indistinguishable from the head noun: "Socialinis darbas", "Duomenų mokslas",
 * "Chemijos magistras" and "Kompiuterinis modeliavimas" all end in `-as` without ever
 * naming the person who holds the duty. Keyed by stem, so both genders and both numbers of
 * one noun collapse into a single entry.
 *
 * Add a stem here when a new kind of title appears; names with no entry still fall back to
 * the end-of-string rules in {@link inflectDutyNameEnding}, which is all any duty name had
 * before. Mirrored, ASCII-folded, by `App\Services\DutyNameNormalizer` on the server.
 */
const DUTY_AGENT_NOUN_STEMS: Record<string, 'ius' | 'as' | 'ys' | 'is'> = {
  administrator: 'ius',
  atstov: 'as',
  direktor: 'ius',
  instruktor: 'ius',
  iždinink: 'as',
  koordinator: 'ius',
  kurator: 'ius',
  mentor: 'ius',
  nar: 'ys',
  pavaduotoj: 'as',
  pirminink: 'as',
  prezident: 'as',
  redaktor: 'ius',
  sekretor: 'ius',
  seniūn: 'as',
  trener: 'is',
  vadov: 'as',
  vicepirminink: 'as',
  viceprezident: 'as',
};

const MASCULINE_PLURAL_ENDINGS = { ius: 'iai', as: 'ai', ys: 'iai', is: 'iai' } as const;

interface DutyAgentNoun {
  stem: string;
  masculineEnding: string;
  pluralEnding: string;
  /** Whether the spelling found in the name is already plural ("atstovai", "koordinatorės"). */
  isPlural: boolean;
}

/** Every surface form of every stem above, keyed by the lowercased word. */
const DUTY_AGENT_NOUN_FORMS = new Map<string, DutyAgentNoun>(
  Object.entries(DUTY_AGENT_NOUN_STEMS).flatMap(([stem, masculineEnding]) => {
    const pluralEnding = MASCULINE_PLURAL_ENDINGS[masculineEnding];
    const noun = { stem, masculineEnding, pluralEnding };

    return [
      [stem + masculineEnding, { ...noun, isPlural: false }],
      [`${stem}ė`, { ...noun, isPlural: false }],
      [stem + pluralEnding, { ...noun, isPlural: true }],
      [`${stem}ės`, { ...noun, isPlural: true }],
    ] as [string, DutyAgentNoun][];
  }),
);

/** Parenthesised qualifiers ("(Biochemija)", "(Valdybos narys)") name the duty's scope, not its holder. */
const PARENTHESISED = /\([^)]*\)/gu;

/** A hand-written both-gender marker: "(-ė)", "(-čių)", "( -ių )". */
const GENDER_MARKER_AT_START = /^\s*\(\s*-[^)]*\)/u;

interface DutyHeadNoun extends DutyAgentNoun {
  /** Index of the head noun's first character in the original name. */
  start: number;
  /** Index just past the head noun's last character. */
  end: number;
  /** Whether the admin already spelled both genders out, as in "atstovas (-ė)". */
  isGenderMarked: boolean;
}

/**
 * Locates the word a duty name should be inflected on: the last recognised agent noun
 * outside any parentheses. Last rather than first, because the qualifiers pile up on the
 * left ("Chemijos magistras studentų atstovė") while the head noun is the rightmost
 * person-word.
 */
const findDutyHeadNoun = (name: string): DutyHeadNoun | null => {
  // Blanked rather than removed so indices still line up with the original string.
  const searchable = name.replace(PARENTHESISED, match => ' '.repeat(match.length));

  let found: DutyHeadNoun | null = null;

  for (const match of searchable.matchAll(/\p{L}+/gu)) {
    const noun = DUTY_AGENT_NOUN_FORMS.get(match[0].toLowerCase());

    if (noun && match.index !== undefined) {
      const end = match.index + match[0].length;

      found = {
        ...noun,
        start: match.index,
        end,
        // Only a marker on the head noun itself means "either gender" — one sitting on a
        // modifier ("Studentų (-čių) iniciatyvų koordinatorius") leaves the title gendered.
        isGenderMarked: GENDER_MARKER_AT_START.test(name.slice(end)),
      };
    }
  }

  return found;
};

type DutyNameGender = 'masculine' | 'feminine' | 'plural';

/**
 * The original end-of-string rules, used for titles whose head noun isn't in
 * {@link DUTY_AGENT_NOUN_STEMS} — a misspelling ("Partnerysčių koodinatorius") or a role
 * nobody has catalogued yet.
 *
 * Going the masculine direction, `-ė` stands in for `-ius`, `-as` and `-ys` alike, so the
 * letters before it have to decide. `-orė` is the Latin-derived agent noun
 * ("koordinatorė" → "koordinatorius"); any other `-rė` takes `-ys` ("narė" → "narys",
 * never "narius").
 */
const inflectDutyNameEnding = (name: string, gender: DutyNameGender): string => {
  if (gender === 'feminine') {
    return name
      .replace(/ius$/, 'ė')
      .replace(/as$/, 'ė')
      .replace(/ys$/, 'ė');
  }

  if (gender === 'plural') {
    return name
      .replace(/ius$/, 'iai')
      .replace(/as$/, 'ai')
      .replace(/ys$/, 'iai');
  }

  return name
    .replace(/orė$/, 'orius')
    .replace(/rė$/, 'rys')
    .replace(/vė$/, 'vas')
    .replace(/kė$/, 'kas');
};

/**
 * Rewrites a duty name into the requested gender/number, leaving everything around the head
 * noun — qualifiers, locatives, parenthesised scopes — exactly as the admin typed it.
 */
const inflectDutyName = (name: string, gender: DutyNameGender): string => {
  const head = findDutyHeadNoun(name);

  if (!head) {
    return inflectDutyNameEnding(name, gender);
  }

  if (head.isGenderMarked) {
    return name;
  }

  const ending = gender === 'feminine'
    ? 'ė'
    : gender === 'plural' ? head.pluralEnding : head.masculineEnding;

  // Slicing the stem out of the original name keeps whatever casing was typed ("Kuratorė").
  return name.slice(0, head.start + head.stem.length) + ending + name.slice(head.end);
};

export const changeDutyNameEndings = (
  contact: App.Entities.User | null | undefined,
  dutyName: App.Entities.Duty['name'],
  locale: string,
  pronouns: string,
  useOriginalDutyName: boolean,
) => {
  if (locale === 'en') {
    return dutyName;
  }

  // check if duty name should not be explicitly changed
  if (useOriginalDutyName) return dutyName;

  const splitPronouns = pronouns?.split('/');

  const womanizedTitle = inflectDutyName(dutyName, 'feminine');
  const pluralizedTitle = inflectDutyName(dutyName, 'plural');
  const masculinedTitle = inflectDutyName(dutyName, 'masculine');

  if (Array.isArray(splitPronouns) && splitPronouns.length > 1) {
    if (splitPronouns[0] === 'ji' || splitPronouns[0] === 'she') {
      return womanizedTitle;
    }
    else if (splitPronouns[0] === 'jie' || splitPronouns[0] === 'they') {
      return pluralizedTitle;
    }
    else if (splitPronouns[0] === 'jis' || splitPronouns[0] === 'he') {
      return masculinedTitle;
    }
  }

  // If no pronouns are set, try to guess based on the name
  if (!contact) {
    return dutyName;
  }

  const firstName = contact.name.split(' ')[0];

  const namesToWomanize = ['Katrin'];
  if (namesToWomanize.includes(firstName)) {
    return womanizedTitle;
  }

  const namesNotToWomanize = ['German'];
  if (namesNotToWomanize.includes(firstName)) {
    return dutyName;
  }

  if (contact.name.endsWith('ė') || firstName.endsWith('ė')) {
    return womanizedTitle;
  }

  // check for first name ending with 's'
  if (contact.name.endsWith('a') && !firstName.endsWith('s')) {
    return womanizedTitle;
  }

  return dutyName ?? '';
};

export interface DutyNameGenderVariants {
  /** Leading text identical in both genders. */
  stem: string;
  /** Ending as it would read for a masculine holder. */
  masculineEnding: string;
  /** Ending as it would read for a feminine holder. */
  feminineEnding: string;
  /** Trailing text identical in both genders — everything after the head noun. */
  suffix: string;
}

/**
 * Detects whether a Lithuanian duty name carries a gendered ending at all, and if so,
 * where it splits into an invariant stem and a varying ending — for UI that needs to show
 * a duty name is not tied to one gender (e.g. before a holder is assigned).
 *
 * Reuses the same head-noun detection and suffix rules as {@link changeDutyNameEndings},
 * so whatever this reports matches what a real holder's pronouns would produce. Checks both
 * directions because a duty may be stored in either gender ("Koordinatorius" or
 * "Koordinatorė") — forgetting the other form is exactly the duplicate-duty mistake this is
 * meant to prevent.
 *
 * Returns null for names that carry a hand-written "(-ė)" on the head noun (they already
 * say they cover both genders) and for names stored in the plural ("Studentų atstovai MIF
 * Taryboje"), where showing a singular pair would misreport what is actually stored.
 */
export const getDutyNameGenderVariants = (
  dutyName: string | null | undefined,
): DutyNameGenderVariants | null => {
  const name = dutyName?.trim();
  if (!name) {
    return null;
  }

  const head = findDutyHeadNoun(name);

  if (head) {
    if (head.isGenderMarked || head.isPlural) {
      return null;
    }

    return {
      stem: name.slice(0, head.start + head.stem.length),
      masculineEnding: head.masculineEnding,
      feminineEnding: 'ė',
      suffix: name.slice(head.end),
    };
  }

  const feminized = changeDutyNameEndings(null, name, 'lt', 'ji/jos', false);
  const masculinized = changeDutyNameEndings(null, name, 'lt', 'jis/jo', false);

  let masculine: string;
  let feminine: string;

  if (feminized !== name) {
    // `name` matched a masculine ending (-ius/-as/-ys).
    masculine = name;
    feminine = feminized;
  }
  else if (masculinized !== name) {
    // `name` matched a feminine ending (-vė/-rė/-kė).
    masculine = masculinized;
    feminine = name;
  }
  else {
    // Neither direction changed anything — not a gendered noun (e.g. "Grupė", "Taryba").
    return null;
  }

  let stemLength = 0;
  const maxLength = Math.min(masculine.length, feminine.length);
  while (stemLength < maxLength && masculine[stemLength] === feminine[stemLength]) {
    stemLength += 1;
  }

  return {
    stem: masculine.slice(0, stemLength),
    masculineEnding: masculine.slice(stemLength),
    feminineEnding: feminine.slice(stemLength),
    suffix: '',
  };
};

export function slugify(str: string) {
  str = str.replace(/^\s+|\s+$/g, ''); // trim leading/trailing white space
  str = str.toLowerCase(); // convert string to lowercase
  str = str.replace(/[^a-z0-9 -]/g, '') // remove any non-alphanumeric characters
    .replace(/\s+/g, '-') // replace spaces with hyphens
    .replace(/-+/g, '-'); // remove consecutive hyphens
  return str;
}

/**
 * Character map for transliterating Lithuanian diacritics to ASCII equivalents.
 * Matches Laravel's Str::slug() behavior with 'lt' locale.
 */
const LITHUANIAN_CHAR_MAP: Record<string, string> = {
  Ą: 'A', ą: 'a',
  Č: 'C', č: 'c',
  Ę: 'E', ę: 'e',
  Ė: 'E', ė: 'e',
  Į: 'I', į: 'i',
  Š: 'S', š: 's',
  Ų: 'U', ų: 'u',
  Ū: 'U', ū: 'u',
  Ž: 'Z', ž: 'z',
};

/**
 * Transliterate Lithuanian diacritical characters to their ASCII equivalents.
 *
 * @param text - The string containing Lithuanian characters
 * @returns The transliterated string with Lithuanian diacritics replaced
 *
 * @example
 * translitLithuanian('Žalioji ąžuolynas') // 'Zalioji azuolynas'
 * translitLithuanian('Būti čia') // 'Buti cia'
 */
export function translitLithuanian(text: string): string {
  return text.replace(/[ĄąČčĘęĖėĮįŠšŲųŪūŽž]/g, char => LITHUANIAN_CHAR_MAP[char] || char);
}

/**
 * Generate a URL-safe ID from text by transliterating Lithuanian characters
 * and converting to a lowercase slug format.
 *
 * Used for generating anchor IDs for headings in TipTap editor.
 *
 * @param text - The text to convert to an ID
 * @param maxLength - Maximum length of the resulting ID (default: 100)
 * @returns A URL-safe lowercase ID with hyphens
 *
 * @example
 * latinizeId('Įvadas į programavimą') // 'ivadas-i-programavima'
 * latinizeId('Šiandien yra gera diena!') // 'siandien-yra-gera-diena'
 */
export function latinizeId(text: string, maxLength = 100): string {
  return translitLithuanian(text)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
    .substring(0, maxLength);
}

/**
 * Generate a URL slug from a human-readable title.
 *
 * Same transformation as {@link latinizeId}, but unbounded by default and trailing
 * hyphens are trimmed *after* truncating, so a slug cut mid-word never ends in a
 * dangling separator.
 *
 * @param text - The title to convert
 * @param maxLength - Optional cap on the resulting slug's length
 *
 * @example
 * generateSlug('Narių registracija') // 'nariu-registracija'
 * generateSlug('Šiandien yra gera diena!', 12) // 'siandien-yra'
 */
export function generateSlug(text: string, maxLength?: number): string {
  const slug = latinizeId(text);

  return maxLength === undefined ? slug : slug.substring(0, maxLength).replace(/-+$/, '');
}
