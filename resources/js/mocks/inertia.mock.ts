import { vi } from 'vitest';

import { createInertiaMock } from './createInertiaMock';
import { defaultMockPage } from './defaultPageProps';

const mock = createInertiaMock(implementation => vi.fn(implementation));

export const { useRemember, router, useForm, useHttp, Head, Link, Deferred, WhenVisible } = mock;

export const usePage = vi.fn(defaultMockPage);

export default { ...mock, usePage };
