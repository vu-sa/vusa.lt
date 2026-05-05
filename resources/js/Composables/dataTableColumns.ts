import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';

import { capitalize } from '@/Utils/String';

export const tenantColumn = (filters, tenants) => {
  return {
    key: 'tenant.id',
    title() {
      return capitalize($tChoice('entities.tenant.model', 1));
    },
    width: 120,
    filter: true,
    filterOptionValues: filters.value['tenant.id'],
    filterOptions: tenants.map((tenant) => {
      return {
        label: $t(tenant.shortname),
        value: tenant.id,
      };
    }),
  };
};

export const langColumn = (filters) => {
  return {
    key: 'lang',
    title() {
      return $t('Kalba');
    },
    width: 100,
    filter: true,
    filterOptionValues: filters.value['lang'],
    filterOptions: [
      {
        label: 'Lietuvių',
        value: 'lt',
      },
      {
        label: 'Anglų',
        value: 'en',
      },
    ],
  };
};

export const createTimestampColumn = (key, title, sorters) => {
  return {
    title,
    key,
    sorter: true,
    sortOrder: sorters.value[key],
    render(row) {
      return new Date(row[key]).toLocaleString('lt-LT');
    },
  };
};

export const createTextColumn = (key, title, sorters) => {
  return {
    title,
    key,
    sorter: !!sorters,
    sortOrder: sorters?.value[key],
  };
};

export const createRelationshipColumn = (key, title, renderFn) => {
  return {
    title,
    key,
    render: renderFn,
  };
};
