import { describe, it, expect } from 'vitest';

import { translitLithuanian, latinizeId, slugify, generateSlug, getDutyNameGenderVariants, changeDutyNameEndings } from '../String';

describe('translitLithuanian', () => {
  it('transliterates all Lithuanian lowercase letters', () => {
    expect(translitLithuanian('ą')).toBe('a');
    expect(translitLithuanian('č')).toBe('c');
    expect(translitLithuanian('ę')).toBe('e');
    expect(translitLithuanian('ė')).toBe('e');
    expect(translitLithuanian('į')).toBe('i');
    expect(translitLithuanian('š')).toBe('s');
    expect(translitLithuanian('ų')).toBe('u');
    expect(translitLithuanian('ū')).toBe('u');
    expect(translitLithuanian('ž')).toBe('z');
  });

  it('transliterates all Lithuanian uppercase letters', () => {
    expect(translitLithuanian('Ą')).toBe('A');
    expect(translitLithuanian('Č')).toBe('C');
    expect(translitLithuanian('Ę')).toBe('E');
    expect(translitLithuanian('Ė')).toBe('E');
    expect(translitLithuanian('Į')).toBe('I');
    expect(translitLithuanian('Š')).toBe('S');
    expect(translitLithuanian('Ų')).toBe('U');
    expect(translitLithuanian('Ū')).toBe('U');
    expect(translitLithuanian('Ž')).toBe('Z');
  });

  it('handles mixed Lithuanian and ASCII text', () => {
    expect(translitLithuanian('Vilniaus universitetas')).toBe('Vilniaus universitetas');
    expect(translitLithuanian('Žalioji ąžuolynas')).toBe('Zalioji azuolynas');
    expect(translitLithuanian('Būti čia')).toBe('Buti cia');
    expect(translitLithuanian('Įvadas į programavimą')).toBe('Ivadas i programavima');
  });

  it('preserves non-Lithuanian characters', () => {
    expect(translitLithuanian('Hello World!')).toBe('Hello World!');
    expect(translitLithuanian('123 abc')).toBe('123 abc');
    expect(translitLithuanian('')).toBe('');
  });

  it('handles full sentences with mixed content', () => {
    expect(translitLithuanian('Šiandien yra gera diena!')).toBe('Siandien yra gera diena!');
    expect(translitLithuanian('VU SA – Vilniaus universiteto Studentų atstovybė'))
      .toBe('VU SA – Vilniaus universiteto Studentu atstovybe');
  });

  it('handles consecutive Lithuanian characters', () => {
    expect(translitLithuanian('ąčęėįšųūž')).toBe('aceeisuuz');
    expect(translitLithuanian('ĄČĘĖĮŠŲŪŽ')).toBe('ACEEISUUZ');
  });
});

describe('latinizeId', () => {
  it('converts Lithuanian text to lowercase slug', () => {
    expect(latinizeId('Įvadas į programavimą')).toBe('ivadas-i-programavima');
    expect(latinizeId('Šiandien yra gera diena!')).toBe('siandien-yra-gera-diena');
  });

  it('removes special characters and replaces spaces with hyphens', () => {
    expect(latinizeId('Hello World!')).toBe('hello-world');
    expect(latinizeId('Test: Something & Other')).toBe('test-something-other');
  });

  it('removes leading and trailing hyphens', () => {
    expect(latinizeId('...Hello...')).toBe('hello');
    expect(latinizeId('---test---')).toBe('test');
  });

  it('handles empty strings', () => {
    expect(latinizeId('')).toBe('');
  });

  it('respects maxLength parameter', () => {
    const longText = 'Labai ilgas pavadinimas kuris yra per ilgas';
    expect(latinizeId(longText, 10)).toBe('labai-ilga');
    expect(latinizeId(longText, 20)).toBe('labai-ilgas-pavadini');
  });

  it('defaults to 100 character max length', () => {
    const veryLongText = 'a'.repeat(200);
    expect(latinizeId(veryLongText)).toHaveLength(100);
  });

  it('handles numbers in text', () => {
    expect(latinizeId('2024 metų įvykiai')).toBe('2024-metu-ivykiai');
    expect(latinizeId('123 ABC')).toBe('123-abc');
  });

  it('collapses multiple special characters into single hyphen', () => {
    expect(latinizeId('Hello   World')).toBe('hello-world');
    expect(latinizeId('Test!!!Something')).toBe('test-something');
    expect(latinizeId('A--B--C')).toBe('a-b-c');
  });

  it('handles real-world calendar event titles', () => {
    expect(latinizeId('VU SA susitikimas su rektoriumi'))
      .toBe('vu-sa-susitikimas-su-rektoriumi');
    expect(latinizeId('Studentų atstovybės šventė'))
      .toBe('studentu-atstovybes-svente');
  });
});

describe('slugify', () => {
  it('converts text to lowercase slug', () => {
    expect(slugify('Hello World')).toBe('hello-world');
    expect(slugify('Test String')).toBe('test-string');
  });

  it('removes non-alphanumeric characters', () => {
    expect(slugify('Hello! World?')).toBe('hello-world');
    expect(slugify('Test: Something')).toBe('test-something');
  });

  it('trims leading and trailing whitespace', () => {
    expect(slugify('  Hello World  ')).toBe('hello-world');
  });

  it('removes consecutive hyphens', () => {
    expect(slugify('Hello  World')).toBe('hello-world');
    expect(slugify('A - B - C')).toBe('a-b-c');
  });

  it('NOTE: does not transliterate Lithuanian characters (use translitLithuanian first)', () => {
    // This test documents current behavior - slugify removes non-ASCII
    expect(slugify('Žalioji')).toBe('alioji'); // ž is removed, not transliterated
    expect(slugify('ąčęėįšųūž')).toBe(''); // all removed
  });
});

describe('integration: translitLithuanian + slugify', () => {
  it('produces proper slugs when used together', () => {
    expect(slugify(translitLithuanian('Žalioji ąžuolynas'))).toBe('zalioji-azuolynas');
    expect(slugify(translitLithuanian('VU SA – Studentų atstovybė')))
      .toBe('vu-sa-studentu-atstovybe');
  });
});

describe('generateSlug', () => {
  it('transliterates Lithuanian diacritics', () => {
    expect(generateSlug('Narių registracija')).toBe('nariu-registracija');
    expect(generateSlug('Studentų atstovų registracija')).toBe('studentu-atstovu-registracija');
    expect(generateSlug('Žalioji ąžuolynas')).toBe('zalioji-azuolynas');
  });

  it('is unbounded when no maxLength is given', () => {
    const long = 'Labai ilgas pavadinimas kuris tikrai virsija sesiasdesimt simboliu riba be jokiu abejoniu';

    expect(generateSlug(long).length).toBeGreaterThan(60);
  });

  it('truncates to maxLength when given', () => {
    expect(generateSlug('Labai ilgas pavadinimas', 11)).toBe('labai-ilgas');
  });

  it('does not leave a trailing hyphen after truncation', () => {
    // Cutting at 12 lands right on the separator after "labai-ilgas".
    expect(generateSlug('Labai ilgas pavadinimas', 12)).toBe('labai-ilgas');
  });

  it('handles empty and punctuation-only input', () => {
    expect(generateSlug('')).toBe('');
    expect(generateSlug('!!!')).toBe('');
  });
});

describe('changeDutyNameEndings', () => {
  const masculinize = (name: string) => changeDutyNameEndings(null, name, 'lt', 'jis/jo', false);

  it('turns a feminine -rė duty into -rys, not -rius', () => {
    // "Narė" is stored feminine on plenty of board/committee duties; "Narius" is not a word.
    expect(masculinize('Narė')).toBe('Narys');
    expect(masculinize('Valdybos narė')).toBe('Valdybos narys');
  });

  it('keeps -orė agent nouns on -orius', () => {
    expect(masculinize('Koordinatorė')).toBe('Koordinatorius');
    expect(masculinize('Studentų atstovų koordinatorė')).toBe('Studentų atstovų koordinatorius');
    expect(masculinize('Kuratorė')).toBe('Kuratorius');
  });

  it('keeps the other feminine endings unchanged in behaviour', () => {
    expect(masculinize('Studentų atstovė')).toBe('Studentų atstovas');
    expect(masculinize('Pirmininkė')).toBe('Pirmininkas');
  });

  it('round-trips the -ys/-ė pair back to the feminine form', () => {
    expect(changeDutyNameEndings(null, 'Narys', 'lt', 'ji/jos', false)).toBe('Narė');
    expect(changeDutyNameEndings(null, 'Narys', 'lt', 'jie/jų', false)).toBe('Nariai');
  });
});

describe('getDutyNameGenderVariants', () => {
  it('splits an -ius/-ė pair stored in the masculine form', () => {
    expect(getDutyNameGenderVariants('Koordinatorius')).toEqual({
      stem: 'Koordinator',
      masculineEnding: 'ius',
      feminineEnding: 'ė',
    });
  });

  it('splits an -as/-ė pair stored in the masculine form', () => {
    expect(getDutyNameGenderVariants('Pirmininkas')).toEqual({
      stem: 'Pirminink',
      masculineEnding: 'as',
      feminineEnding: 'ė',
    });
  });

  it('detects the same pair when the duty is instead stored in the feminine form', () => {
    // Forgetting the masculine counterpart of an already-feminine duty is exactly as
    // easy as the reverse, and the whole point is to surface it either way.
    expect(getDutyNameGenderVariants('Koordinatorė')).toEqual({
      stem: 'Koordinator',
      masculineEnding: 'ius',
      feminineEnding: 'ė',
    });
    expect(getDutyNameGenderVariants('Sekretorė')).toEqual({
      stem: 'Sekretor',
      masculineEnding: 'ius',
      feminineEnding: 'ė',
    });
    expect(getDutyNameGenderVariants('Valdybos narė')).toEqual({
      stem: 'Valdybos nar',
      masculineEnding: 'ys',
      feminineEnding: 'ė',
    });
  });

  it('keeps a multi-word stem intact', () => {
    expect(getDutyNameGenderVariants('Studentų atstovas')).toEqual({
      stem: 'Studentų atstov',
      masculineEnding: 'as',
      feminineEnding: 'ė',
    });
  });

  it('returns null for names with no gendered ending', () => {
    expect(getDutyNameGenderVariants('Grupė')).toBeNull();
    expect(getDutyNameGenderVariants('Taryba')).toBeNull();
    expect(getDutyNameGenderVariants('Atstovybė')).toBeNull();
  });

  it('returns null when the admin already wrote the gender marker by hand', () => {
    expect(getDutyNameGenderVariants('Koordinatorius (-ė)')).toBeNull();
  });

  it('returns null for empty, whitespace-only, or nullish input', () => {
    expect(getDutyNameGenderVariants('')).toBeNull();
    expect(getDutyNameGenderVariants('   ')).toBeNull();
    expect(getDutyNameGenderVariants(null)).toBeNull();
    expect(getDutyNameGenderVariants(undefined)).toBeNull();
  });
});
