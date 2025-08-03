import { fn } from 'storybook/test';

/**
 * Centralized Laravel Vue i18n mock for Storybook
 * Provides realistic translations for common keys used across the application
 */

// Enhanced translation dictionary
const translations: Record<string, string> = {
  // Common form fields
  'forms.fields.name': 'Pavadinimas',
  'forms.fields.email': 'El. paštas',
  'forms.fields.type': 'Tipas',
  'forms.fields.title': 'Pavadinimas',
  'forms.fields.description': 'Aprašymas',
  'forms.fields.content': 'Turinys',
  'forms.fields.date': 'Data',
  'forms.fields.status': 'Būsena',
  
  // Actions
  'actions.edit': 'Redaguoti',
  'actions.delete': 'Ištrinti',
  'actions.view': 'Peržiūrėti',
  'actions.create': 'Sukurti',
  'actions.save': 'Išsaugoti',
  'actions.cancel': 'Atšaukti',
  'actions.search': 'Ieškoti',
  'actions.clear': 'Išvalyti',
  'actions.filter': 'Filtruoti',
  
  // Common words
  'Created': 'Sukurta',
  'Updated': 'Atnaujinta',
  'Loading': 'Kraunama',
  'Error': 'Klaida',
  'Success': 'Sėkmingai',
  'Warning': 'Įspėjimas',
  'Info': 'Informacija',
  
  // Document search specific
  'Dokumentai': 'Dokumentai',
  'Ieškokite VU SA dokumentų archyve': 'Ieškokite VU SA dokumentų archyve',
  'Ieškokite dokumentų...': 'Ieškokite dokumentų...',
  'Protokolai': 'Protokolai',
  'Ataskaitos': 'Ataskaitos',
  'Sprendimai': 'Sprendimai',
  'Pranešimai': 'Pranešimai',
  
  // Alert and notification specific
  'Įsidėmėk': 'Remember',
  
  // Button text
  'Toliau': 'Continue',
  'Išsaugoti': 'Save',
  'Pridėti': 'Add'
}

const pluralTranslations: Record<string, Record<number, string>> = {
  'forms.fields.type': {
    1: 'Tipas',
    2: 'Tipai'
  },
  'document': {
    1: 'dokumentas',
    2: 'dokumentai'
  }
}

// Mock the trans function with realistic translations
export const trans = fn((key: string, replace: any = {}) => {
  let translation = translations[key] || key
  
  // Handle replacements
  if (replace && typeof replace === 'object') {
    Object.keys(replace).forEach(replaceKey => {
      translation = translation.replace(`:${replaceKey}`, replace[replaceKey])
    })
  }
  
  return translation
});

export const wTrans = fn((key: string, replace: any = {}) => trans(key, replace));

export const transChoice = fn((key: string, choice: number, replace: any = {}) => {
  const plurals = pluralTranslations[key]
  if (plurals) {
    const translation = plurals[choice] || plurals[2] || key
    return trans(translation, replace)
  }
  return trans(key, replace)
});

export const wTransChoice = fn((key: string, choice: number, replace: any = {}) => 
  transChoice(key, choice, replace));

// Export as $t for components that use it that way
export const $t = trans;

// Export default object
export default { trans, wTrans, transChoice, wTransChoice };