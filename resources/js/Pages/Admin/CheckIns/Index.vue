<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import Avatar from '@/Components/ui/avatar/Avatar.vue'

const props = defineProps<{ filters: { institution_id?: string; state?: string; mode?: string }, checkIns: any }>()

const items = computed(() => props.checkIns?.data ?? [])

const stateOptions = [
  { label: 'All states', value: '' },
  { label: 'Active', value: 'App\\States\\InstitutionCheckIns\\Active' },
  { label: 'Expired', value: 'App\\States\\InstitutionCheckIns\\Expired' },
  { label: 'Invalidated', value: 'App\\States\\InstitutionCheckIns\\Invalidated' },
  { label: 'Withdrawn', value: 'App\\States\\InstitutionCheckIns\\Withdrawn' },
  { label: 'Disputed', value: 'App\\States\\InstitutionCheckIns\\Disputed' },
  { label: 'Admin Suppressed', value: 'App\\States\\InstitutionCheckIns\\AdminSuppressed' },
]

const modeOptions = [
  { label: 'All modes', value: '' },
  { label: 'blackout', value: 'blackout' },
  { label: 'heads_up', value: 'heads_up' },
]

const stateLabel = (state: string | null | undefined) => {
  const map: Record<string, { text: string; color: string }> = {
    'App\\States\\InstitutionCheckIns\\Active': { text: 'Active', color: 'bg-emerald-100 text-emerald-800' },
    'App\\States\\InstitutionCheckIns\\Expired': { text: 'Expired', color: 'bg-zinc-100 text-zinc-700' },
    'App\\States\\InstitutionCheckIns\\Invalidated': { text: 'Invalidated', color: 'bg-rose-100 text-rose-800' },
    'App\\States\\InstitutionCheckIns\\Withdrawn': { text: 'Withdrawn', color: 'bg-zinc-100 text-zinc-700' },
    'App\\States\\InstitutionCheckIns\\Disputed': { text: 'Disputed', color: 'bg-amber-100 text-amber-800' },
    'App\\States\\InstitutionCheckIns\\AdminSuppressed': { text: 'Suppressed', color: 'bg-orange-100 text-orange-800' },
  }
  return map[state ?? ''] ?? { text: (state?.split('\\').pop() ?? 'Unknown'), color: 'bg-gray-100 text-gray-800' }
}
</script>

<template>
  <Head title="Check-ins" />
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold">Check-ins</h1>
      <p class="text-sm text-gray-500">Read-only list for admins</p>
    </div>

    <!-- Filters (server-driven simple inputs) -->
    <form method="get" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
      <input type="text" name="institution_id" :value="filters.institution_id" placeholder="Institution ID" class="border rounded px-3 py-2" />
      <select name="state" :value="filters.state" class="border rounded px-3 py-2">
        <option v-for="opt in stateOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <select name="mode" :value="filters.mode" class="border rounded px-3 py-2">
        <option v-for="opt in modeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <button class="sm:col-span-4 bg-blue-600 text-white rounded px-4 py-2">Apply</button>
    </form>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="py-2 pr-4">Checked At</th>
            <th class="py-2 pr-4">Institution</th>
            <th class="py-2 pr-4">User</th>
            <th class="py-2 pr-4">Until</th>
            <th class="py-2 pr-4">Mode</th>
            <th class="py-2 pr-4">State</th>
            <th class="py-2 pr-4">Confidence</th>
            <th class="py-2 pr-4">Verifications</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in items" :key="row.id" class="border-b">
            <td class="py-2 pr-4">{{ row.checked_at }}</td>
            <td class="py-2 pr-4">{{ row.institution?.name ?? row.institution_id }}</td>
            <td class="py-2 pr-4">{{ row.user?.name ?? row.user_id }}</td>
            <td class="py-2 pr-4">{{ row.until_date }}</td>
            <td class="py-2 pr-4"><span class="px-2 py-0.5 rounded bg-gray-100">{{ row.mode }}</span></td>
            <td class="py-2 pr-4">
              <span class="px-2 py-0.5 rounded" :class="stateLabel(row.state).color">{{ stateLabel(row.state).text }}</span>
            </td>
            <td class="py-2 pr-4">{{ row.confidence }}</td>
            <td class="py-2 pr-4">
              <div v-if="Array.isArray(row.verifications) && row.verifications.length" class="flex -space-x-2">
                <Avatar v-for="v in row.verifications.slice(0,8)"
                        :key="v.id"
                        class="size-6 ring-2 ring-white">
                  <img :src="v.user?.profile_photo_path || '/images/default-avatar.png'" :alt="v.user?.name" />
                </Avatar>
                <span v-if="row.verifications.length > 8" class="text-xs ml-2">+{{ row.verifications.length - 8 }}</span>
              </div>
              <span v-else class="text-gray-400">0</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination (basic) -->
    <div class="flex items-center gap-2" v-if="checkIns?.links">
      <Link v-for="link in checkIns.links" :key="link.url ?? link.label" :href="link.url || '#'" :class="['px-3 py-1 rounded', { 'bg-blue-600 text-white': link.active, 'text-gray-500': !link.url } ]" preserve-state>
        <span v-html="link.label" />
      </Link>
    </div>
  </div>
</template>
