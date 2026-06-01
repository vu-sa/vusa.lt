/**
 * Block options offered by the "/" slash menu in the agenda-item notes editor.
 * Each item deletes the typed "/query" range, then applies its block command.
 */
import type { Editor, Range } from '@tiptap/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Heading2, List, ListChecks, ListOrdered } from 'lucide-vue-next';
import type { Component } from 'vue';

export interface SlashCommandItem {
  title: string;
  icon: Component;
  command: (props: { editor: Editor; range: Range }) => void;
}

export function buildSlashCommandItems(): SlashCommandItem[] {
  return [
    {
      title: $t('Skyriaus antraštė'),
      icon: Heading2,
      command: ({ editor, range }) => editor.chain().focus().deleteRange(range).toggleHeading({ level: 2 }).run(),
    },
    {
      title: $t('Sąrašas'),
      icon: List,
      command: ({ editor, range }) => editor.chain().focus().deleteRange(range).toggleBulletList().run(),
    },
    {
      title: $t('Numeruotas sąrašas'),
      icon: ListOrdered,
      command: ({ editor, range }) => editor.chain().focus().deleteRange(range).toggleOrderedList().run(),
    },
    {
      title: $t('Užduočių sąrašas'),
      icon: ListChecks,
      command: ({ editor, range }) => editor.chain().focus().deleteRange(range).toggleTaskList().run(),
    },
  ];
}
