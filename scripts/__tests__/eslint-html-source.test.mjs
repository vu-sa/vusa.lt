// @vitest-environment node

import { Linter } from 'eslint';
import vueParser from 'vue-eslint-parser';
import { describe, expect, it } from 'vitest';

import { htmlSourcePlugin } from '../eslint-html-source.mjs';

function lint(template) {
  return new Linter().verify(`<template>\n${template}\n</template>\n`, {
    files: ['**/*.vue'],
    languageOptions: { parser: vueParser },
    plugins: { 'html-source': htmlSourcePlugin },
    rules: { 'html-source/require-comment': 'error' },
  }, 'Example.vue').map(message => message.messageId ?? message.message);
}

describe('html-source/require-comment', () => {
  it('rejects a v-html without a source comment', () => {
    expect(lint('<div v-html="body" />')).toEqual(['missing']);
  });

  it('accepts a v-html named by the comment right before it', () => {
    expect(lint('<!-- v-html: sanitized on write (Duty::sanitizedHtmlTranslations) -->\n<div v-html="body" />')).toEqual([]);
  });

  it('accepts the marker on a multi-line element with v-html on a later line', () => {
    expect(lint('<!-- v-html: sanitized on write -->\n<div\n  class="prose"\n  v-html="body"\n/>')).toEqual([]);
  });

  it('looks past lint directives between the marker and the element', () => {
    expect(lint('<!-- v-html: sanitized on write -->\n<!-- eslint-disable-next-line vue/max-len -->\n<div v-html="body" />')).toEqual([]);
  });

  it('rejects an unrelated comment, an empty marker, or a marker separated by markup', () => {
    expect(lint('<!-- trust me -->\n<div v-html="body" />')).toEqual(['missing']);
    expect(lint('<!-- v-html: -->\n<div v-html="body" />')).toEqual(['missing']);
    expect(lint('<!-- v-html: sanitized on write -->\n<p>Lead</p>\n<div v-html="body" />')).toEqual(['missing']);
  });

  it('treats an innerHTML or outerHTML binding as v-html', () => {
    expect(lint('<div :innerHTML="body" />')).toEqual(['missing']);
    expect(lint('<div v-bind:outerHTML="body" />')).toEqual(['missing']);
    expect(lint('<!-- v-html: sanitized on write -->\n<div :innerHTML="body" />')).toEqual([]);
    expect(lint('<div :title="body" />')).toEqual([]);
  });

  it('needs one marker per element', () => {
    expect(lint('<!-- v-html: sanitized on write -->\n<div v-html="a" />\n<div v-html="b" />')).toEqual(['missing']);
  });
});
