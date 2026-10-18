import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

/** Mirrors App\Services\FileUsageScanner::scanFileUsage(). */
export interface FileUsageDetail {
  model_type: string;
  model_class: string;
  id: number | string;
  title: string;
  url: string | null;
  matched_parts_count?: number;
}

export interface FileUsageResult {
  is_safe_to_delete: boolean;
  total_usages: number;
  usage_details: FileUsageDetail[];
  scanned_models: string[];
}

/** Where a file is referenced, so deleting, renaming or moving it does not break a page unseen. */
export function useFileUsageScan() {
  const scanning = ref(false);
  const result = ref<FileUsageResult | null>(null);
  const scannedPath = ref<string | null>(null);

  function scan(path: string) {
    if (scanning.value) return;

    scanning.value = true;
    result.value = null;
    scannedPath.value = path;

    router.post(route('files.scanUsage'), { path }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        // A later scan replaced this one while it was in flight.
        if (scannedPath.value !== path) return;
        result.value = (page.props.flash?.data as FileUsageResult | undefined) ?? null;
      },
      onFinish: () => {
        scanning.value = false;
      },
    });
  }

  function reset() {
    result.value = null;
    scannedPath.value = null;
  }

  return { scanning, result, scan, reset };
}

export type ScannedFileUsage = FileUsageResult & { path: string };

/** The usage check that has to run before files can be deleted; `failed` keeps deleting blocked. */
export function useFilesUsageScan() {
  const scanning = ref(false);
  const failed = ref(false);
  const results = ref<ScannedFileUsage[] | null>(null);

  function scan(paths: string[]) {
    if (scanning.value) return;

    scanning.value = true;
    failed.value = false;
    results.value = null;

    router.post(route('files.scanUsageBulk'), { paths }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        const data = page.props.flash?.data as { files?: ScannedFileUsage[] } | undefined;
        results.value = data?.files ?? null;
        failed.value = results.value === null;
      },
      onError: () => {
        failed.value = true;
      },
      onFinish: () => {
        scanning.value = false;
      },
    });
  }

  return { scanning, failed, results, scan };
}
