/**
 * Custom Vite plugin for split i18n translation compilation
 * 
 * Compiles PHP translation files from separate directories (admin, public, shared)
 * into separate JSON files that can be loaded independently by each entry point.
 * 
 * This allows the public bundle to only include shared+public translations,
 * while the admin bundle gets shared+admin translations.
 * 
 * Uses php-parser for robust PHP parsing (same as laravel-vue-i18n).
 */

import { existsSync, readdirSync, readFileSync, writeFileSync } from 'fs';
import { basename, extname, join, resolve } from 'path';
import type { Plugin } from 'vite';
import { Engine } from 'php-parser';

interface I18nSplitOptions {
  /** Base language directory path */
  langPath?: string;
  /** Languages to compile */
  languages?: string[];
}

interface ParsedTranslations {
  [key: string]: string;
}

// PHP parser engine
const phpParser = new Engine({});

/**
 * Parse a PHP array item recursively
 */
function parsePhpItem(expr: any): any {
  if (!expr) return null;
  
  if (expr.kind === 'string') {
    return expr.value;
  }
  
  if (expr.kind === 'nullkeyword') {
    return null;
  }
  
  if (expr.kind === 'array') {
    const items = expr.items.map((item: any) => parsePhpItem(item));
    if (expr.items.every((item: any) => item.key !== null)) {
      return items.reduce((acc: any, val: any) => Object.assign({}, acc, val), {});
    }
    return items;
  }
  
  if (expr.kind === 'bin') {
    return parsePhpItem(expr.left) + parsePhpItem(expr.right);
  }
  
  if (expr.key) {
    const key = expr.key.value || expr.key.name;
    return { [key]: parsePhpItem(expr.value) };
  }
  
  if (expr.value) {
    return parsePhpItem(expr.value);
  }
  
  return null;
}

/**
 * Convert nested object to dot notation
 */
function convertToDotNotation(obj: any, prefix = ''): ParsedTranslations {
  const result: ParsedTranslations = {};
  
  if (obj === null || obj === undefined) {
    return result;
  }
  
  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;
    
    if (typeof value === 'string') {
      result[fullKey] = value;
    } else if (typeof value === 'object' && value !== null) {
      Object.assign(result, convertToDotNotation(value, fullKey));
    }
  }
  
  return result;
}

/**
 * Parse PHP translation file content
 */
function parsePhpContent(content: string): ParsedTranslations {
  try {
    const ast = phpParser.parseCode(content, 'translation.php');
    const returnStatement = ast.children.find((child: any) => child.kind === 'return');
    
    if (!returnStatement || returnStatement.expr?.kind !== 'array') {
      return {};
    }
    
    const parsed = parsePhpItem(returnStatement.expr);
    return convertToDotNotation(parsed);
  } catch (error) {
    console.warn('[i18n-split] Failed to parse PHP:', error);
    return {};
  }
}

/**
 * Read and parse all PHP translation files from a directory
 * Returns translations in dot notation format
 */
function parseTranslationsFromDir(dirPath: string, locale: string): ParsedTranslations {
  const result: ParsedTranslations = {};
  const localePath = join(dirPath, locale);
  
  if (!existsSync(localePath)) {
    return result;
  }

  try {
    const files = readdirSync(localePath).filter(file => extname(file) === '.php');
    
    for (const file of files) {
      const filePath = join(localePath, file);
      const namespace = basename(file, '.php');
      
      try {
        const content = readFileSync(filePath, 'utf-8');
        const parsed = parsePhpContent(content);
        
        // Add namespace prefix to all keys
        for (const [key, value] of Object.entries(parsed)) {
          result[`${namespace}.${key}`] = value;
        }
      } catch (parseError) {
        console.warn(`[i18n-split] Warning: Failed to parse ${filePath}:`, parseError);
      }
    }
  } catch (readError) {
    console.warn(`[i18n-split] Warning: Failed to read directory ${localePath}:`, readError);
  }

  return result;
}

/**
 * Merge multiple translation objects
 */
function mergeTranslations(...sources: ParsedTranslations[]): ParsedTranslations {
  const result: ParsedTranslations = {};
  
  for (const source of sources) {
    Object.assign(result, source);
  }

  return result;
}

export default function i18nSplit(options: I18nSplitOptions = {}): Plugin {
  const langPath = options.langPath || 'lang';
  const languages = options.languages || ['lt', 'en'];
  
  let rootDir: string;

  const generateTranslationFiles = () => {
    const langDir = resolve(rootDir, langPath);

    for (const lang of languages) {
      // Parse translations from each category
      const sharedTranslations = parseTranslationsFromDir(join(langDir, 'shared'), lang);
      const adminTranslations = parseTranslationsFromDir(join(langDir, 'admin'), lang);
      const publicTranslations = parseTranslationsFromDir(join(langDir, 'public'), lang);

      // Generate combined files for each bundle type
      // Admin bundle: shared + admin
      const adminCombined = mergeTranslations(sharedTranslations, adminTranslations);
      writeFileSync(
        join(langDir, `php_admin_${lang}.json`),
        JSON.stringify(adminCombined, null, 2),
        'utf-8'
      );

      // Public bundle: shared + public  
      const publicCombined = mergeTranslations(sharedTranslations, publicTranslations);
      writeFileSync(
        join(langDir, `php_public_${lang}.json`),
        JSON.stringify(publicCombined, null, 2),
        'utf-8'
      );

      console.log(`[i18n-split] Generated translations for ${lang}:`);
      console.log(`  - php_admin_${lang}.json (${Object.keys(adminCombined).length} keys)`);
      console.log(`  - php_public_${lang}.json (${Object.keys(publicCombined).length} keys)`);
    }
  };

  return {
    name: 'vite-plugin-i18n-split',
    
    configResolved(config) {
      rootDir = config.root;
    },

    buildStart() {
      generateTranslationFiles();
    },

    configureServer(server) {
      const langDir = resolve(rootDir, langPath);
      
      // Watch for changes in translation files
      const watchDirs = [
        join(langDir, 'admin'),
        join(langDir, 'public'),
        join(langDir, 'shared'),
      ];

      for (const dir of watchDirs) {
        if (existsSync(dir)) {
          server.watcher.add(join(dir, '**/*.php'));
        }
      }

      server.watcher.on('change', (file) => {
        if (file.endsWith('.php') && watchDirs.some(dir => file.startsWith(dir))) {
          console.log(`[i18n-split] Translation file changed: ${file}`);
          generateTranslationFiles();
        }
      });

      // Generate on server start
      generateTranslationFiles();
    },
  };
}
