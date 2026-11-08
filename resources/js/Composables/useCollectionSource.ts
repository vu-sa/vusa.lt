/** One contract, four adapters: see collectionSource/ for each. */
export type * from './collectionSource/types';
export type { DatabaseFacetDefinition } from './collectionSource/database';
export { useTypesenseCollectionSource } from './collectionSource/typesense';
export { useDatabaseCollectionSource } from './collectionSource/database';
export { useLocalCollectionSource } from './collectionSource/local';
export { isTrashView, useTrashAwareSource, useTrashCollectionSource } from './collectionSource/trash';
