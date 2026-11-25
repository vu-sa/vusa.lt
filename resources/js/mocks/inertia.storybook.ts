import { fn } from 'storybook/test';

import { createInertiaMock } from './createInertiaMock';
import { defaultMockPage } from './defaultPageProps';

const mock = createInertiaMock(implementation => fn(implementation));

export const { useRemember, router, useForm, useHttp, Head, Link, Deferred, WhenVisible } = mock;

export const usePage = fn((): { props: Record<string, unknown>; url?: string; component?: string } => {
  const page = defaultMockPage();

  return {
    ...page,
    url: '/lt',
    props: {
      ...page.props,
      app: { ...page.props.app, path: 'lt' },
      otherLangURL: '/en',
      typesenseConfig: {
        apiKey: 'storybook-api-key',
        nodes: [{ protocol: 'http', host: 'localhost', port: 8108 }],
      },
    },
  };
});

export default { ...mock, usePage };
