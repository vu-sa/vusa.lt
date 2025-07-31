<template>
  <AdminContentPage :title="$t('Sistemos būsena')">
    <!-- Status overview cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 items-start">
      <Card class="h-fit hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium">Redis</CardTitle>
            <component 
              :is="getStatusIcon(status.redis?.status)" 
              :class="getStatusColor(status.redis?.status)"
              class="h-4 w-4"
            />
          </div>
        </CardHeader>
        <CardContent size="compact">
          <div class="text-2xl font-bold">
            {{ status.redis?.connected ? 'Prijungtas' : 'Neprijungtas' }}
          </div>
          <p class="text-xs text-muted-foreground">
            {{ status.redis?.memory_used || 'N/A' }}
          </p>
        </CardContent>
      </Card>

      <Card class="h-fit hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium">{{ $t('Duomenų bazė') }}</CardTitle>
            <component 
              :is="getStatusIcon(status.database?.status)" 
              :class="getStatusColor(status.database?.status)"
              class="h-4 w-4"
            />
          </div>
        </CardHeader>
        <CardContent size="compact">
          <div class="text-2xl font-bold">
            {{ status.database?.connected ? 'Prijungta' : 'Neprijungta' }}
          </div>
          <p class="text-xs text-muted-foreground">
            {{ status.database?.connection_time || 'N/A' }}
          </p>
        </CardContent>
      </Card>

      <Card class="h-fit hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium">{{ $t('Talpykla') }}</CardTitle>
            <component 
              :is="getStatusIcon(status.cache?.status)" 
              :class="getStatusColor(status.cache?.status)"
              class="h-4 w-4"
            />
          </div>
        </CardHeader>
        <CardContent size="compact">
          <div class="text-2xl font-bold">
            {{ status.cache?.working ? 'Veikia' : 'Neveikia' }}
          </div>
          <p class="text-xs text-muted-foreground">
            {{ status.cache?.driver || 'N/A' }}
          </p>
        </CardContent>
      </Card>

      <Card class="h-fit hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium">Typesense</CardTitle>
            <component 
              :is="getStatusIcon(status.typesense?.status)" 
              :class="getStatusColor(status.typesense?.status)"
              class="h-4 w-4"
            />
          </div>
        </CardHeader>
        <CardContent size="compact">
          <div class="text-2xl font-bold">
            {{ status.typesense?.connected ? 'Prijungta' : (status.typesense?.enabled ? 'Neprijungta' : 'Išjungta') }}
          </div>
          <p class="text-xs text-muted-foreground">
            {{ status.typesense?.collections?.total_documents || '0' }} {{ $t('dokumentų') }}
            <span v-if="status.typesense?.collections?.memory?.active_memory_mb || status.typesense?.collections?.memory?.resident_memory_mb || status.typesense?.collections?.memory?.estimated_collections_mb" class="block">
              {{ status.typesense.collections.memory.active_memory_mb || status.typesense.collections.memory.resident_memory_mb || status.typesense.collections.memory.estimated_collections_mb }} MB RAM
            </span>
          </p>
        </CardContent>
      </Card>

      <Card class="h-fit hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium">{{ $t('Sistema') }}</CardTitle>
            <CheckCircleIcon class="h-4 w-4 text-green-500" />
          </div>
        </CardHeader>
        <CardContent size="compact">
          <div class="text-2xl font-bold">{{ status.system?.environment || 'N/A' }}</div>
          <p class="text-xs text-muted-foreground">
            PHP {{ status.system?.php_version || 'N/A' }}
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Auto-refresh status -->
    <div class="mb-6 flex items-center gap-2">
      <Badge variant="outline" class="text-xs flex items-center gap-2">
        <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
        {{ $t('Atnaujinimas po') }} 
        <span class="font-mono font-bold tabular-nums transition-all duration-300">{{ countdown }}s</span>
      </Badge>
      <Badge 
        v-if="isPolling" 
        variant="outline" 
        class="text-xs flex items-center gap-2 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800"
      >
        <div class="h-2 w-2 rounded-full bg-blue-500 animate-spin"></div>
        {{ $t('Atnaujinama...') }}
      </Badge>
      <Badge variant="outline" class="text-xs">
        {{ $t('Paskutinis atnaujinimas') }}: {{ formatLastUpdated }}
      </Badge>
    </div>

    <!-- Detailed status sections -->
    <div class="grid gap-6 lg:grid-cols-2">
      <!-- Redis Details -->
      <Card v-if="status.redis" class="hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <CardTitle class="flex items-center gap-2">
            <DatabaseIcon class="h-5 w-5" />
            Redis {{ $t('detalės') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="status.redis.error" class="rounded-md bg-red-50 p-3 dark:bg-red-900/20">
            <p class="text-sm text-red-700 dark:text-red-400">
              {{ status.redis.error }}
            </p>
          </div>
          <div v-else class="space-y-3">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium">{{ $t('Versija') }}:</span>
                <span class="ml-2">{{ status.redis.version }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Veikimo laikas') }}:</span>
                <span class="ml-2">{{ status.redis.uptime }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Prisijungę klientai') }}:</span>
                <span data-redis-clients class="ml-2 font-mono tabular-nums">{{ status.redis.connected_clients }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Pataikymai') }}:</span>
                <span class="ml-2">{{ status.redis.hit_ratio }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Naudojama atmintis') }}:</span>
                <span class="ml-2">{{ status.redis.memory_used }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('RSS atmintis') }}:</span>
                <span class="ml-2">{{ status.redis.memory_rss }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Database Details -->
      <Card v-if="status.database" class="hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <CardTitle class="flex items-center gap-2">
            <HardDriveIcon class="h-5 w-5" />
            {{ $t('Duomenų bazės detalės') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="status.database.error" class="rounded-md bg-red-50 p-3 dark:bg-red-900/20">
            <p class="text-sm text-red-700 dark:text-red-400">
              {{ status.database.error }}
            </p>
          </div>
          <div v-else class="space-y-3">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium">{{ $t('Tvarkyklė') }}:</span>
                <span class="ml-2">{{ status.database.driver }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Versija') }}:</span>
                <span class="ml-2">{{ status.database.version }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Prisijungimo laikas') }}:</span>
                <span class="ml-2">{{ status.database.connection_time }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Dydis') }}:</span>
                <span class="ml-2">{{ status.database.database_size }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Typesense Details -->
      <Card v-if="status.typesense" class="hover:shadow-lg transition-shadow duration-300">
        <CardHeader size="compact">
          <CardTitle class="flex items-center gap-2">
            <SearchIcon class="h-5 w-5" />
            Typesense {{ $t('detalės') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="status.typesense.error" class="rounded-md bg-red-50 p-3 dark:bg-red-900/20">
            <p class="text-sm text-red-700 dark:text-red-400">
              {{ status.typesense.error }}
            </p>
            <p v-if="status.typesense.error_type" class="text-xs text-red-600 dark:text-red-500 mt-1">
              {{ status.typesense.error_type }}
            </p>
          </div>
          <div v-else-if="!status.typesense.enabled" class="rounded-md bg-gray-50 p-3 dark:bg-gray-900/20">
            <p class="text-sm text-gray-700 dark:text-gray-400">
              {{ $t('Typesense paieška išjungta') }}. {{ $t('Dabartinė tvarkyklė') }}: {{ status.typesense.configuration?.driver || 'N/A' }}
            </p>
          </div>
          <div v-else-if="!status.typesense.configured" class="rounded-md bg-yellow-50 p-3 dark:bg-yellow-900/20">
            <p class="text-sm text-yellow-700 dark:text-yellow-400">
              {{ $t('Typesense nėra tinkamai sukonfigūruotas. Naudojamas numatytasis API raktas.') }}
            </p>
          </div>
          <div v-else class="space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="font-medium">{{ $t('Serveris') }}:</span>
                <span class="ml-2">{{ status.typesense.configuration?.host }}:{{ status.typesense.configuration?.port }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Protokolas') }}:</span>
                <span class="ml-2">{{ status.typesense.configuration?.protocol }}</span>
              </div>
              <div>
                <span class="font-medium">{{ $t('Prisijungimo laikas') }}:</span>
                <span class="ml-2">{{ status.typesense.connection_time || 'N/A' }}</span>
              </div>
            </div>
            
            <!-- Configuration badges with better spacing -->
            <div class="grid grid-cols-1 gap-3 text-sm">
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ $t('API raktas') }}:</span>
                <Badge :variant="status.typesense.configuration?.api_key_configured ? 'default' : 'destructive'">
                  {{ status.typesense.configuration?.api_key_configured ? $t('Sukonfigūruotas') : $t('Numatytasis') }}
                </Badge>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ $t('Paieškos raktas') }}:</span>
                <Badge :variant="status.typesense.configuration?.search_only_key_configured ? 'default' : 'secondary'">
                  {{ status.typesense.configuration?.search_only_key_configured ? $t('Sukonfigūruotas') : $t('Nesukonfigūruotas') }}
                </Badge>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ $t('Eilė') }}:</span>
                <Badge :variant="status.typesense.configuration?.queue_enabled ? 'default' : 'secondary'">
                  {{ status.typesense.configuration?.queue_enabled ? $t('Įjungta') : $t('Išjungta') }}
                </Badge>
              </div>
            </div>

            <!-- Collections Statistics -->
            <div v-if="status.typesense.collections" class="mt-6">
              <h4 class="font-semibold text-sm mb-3">{{ $t('Kolekcijos') }}</h4>
              
              <!-- Memory Usage Summary -->
              <div v-if="status.typesense.collections.memory" class="mb-4 p-3 rounded-md bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <h5 class="font-medium text-sm mb-2">{{ $t('Atminties naudojimas') }}</h5>
                <div class="grid grid-cols-2 gap-3 text-sm">
                  <div v-if="status.typesense.collections.memory.active_memory_mb && status.typesense.collections.memory.active_memory_mb > 0" class="flex justify-between">
                    <span>{{ $t('Aktyviai naudojama') }}:</span>
                    <span class="font-mono">{{ status.typesense.collections.memory.active_memory_mb }} MB</span>
                  </div>
                  <div v-if="status.typesense.collections.memory.resident_memory_mb && status.typesense.collections.memory.resident_memory_mb > 0" class="flex justify-between">
                    <span>{{ $t('RAM (resident)') }}:</span>
                    <span class="font-mono">{{ status.typesense.collections.memory.resident_memory_mb }} MB</span>
                  </div>
                  <div v-if="status.typesense.collections.memory.allocated_memory_mb && status.typesense.collections.memory.allocated_memory_mb > 0" class="flex justify-between">
                    <span>{{ $t('Išskirta atminties') }}:</span>
                    <span class="font-mono">{{ status.typesense.collections.memory.allocated_memory_mb }} MB</span>
                  </div>
                  <div v-if="status.typesense.collections.memory.mapped_memory_mb && status.typesense.collections.memory.mapped_memory_mb > 0" class="flex justify-between">
                    <span>{{ $t('Susietos bylos') }}:</span>
                    <span class="font-mono">{{ status.typesense.collections.memory.mapped_memory_mb }} MB</span>
                  </div>
                  <div v-if="status.typesense.collections.memory.metadata_memory_mb && status.typesense.collections.memory.metadata_memory_mb > 0" class="flex justify-between">
                    <span>{{ $t('Metaduomenys') }}:</span>
                    <span class="font-mono">{{ status.typesense.collections.memory.metadata_memory_mb }} MB</span>
                  </div>
                  <div v-if="status.typesense.collections.memory.fragmentation_ratio !== undefined && status.typesense.collections.memory.fragmentation_ratio !== null" class="flex justify-between col-span-2 pt-2 border-t border-blue-300 dark:border-blue-700">
                    <span>{{ $t('Fragmentacijos santykis') }}:</span>
                    <span class="font-mono">{{ (status.typesense.collections.memory.fragmentation_ratio! * 100).toFixed(1) }}%</span>
                  </div>
                  <div v-if="status.typesense.collections.memory.estimated_collections_mb" class="flex justify-between col-span-2 pt-2 border-t border-blue-300 dark:border-blue-700">
                    <span>{{ $t('Apskaičiuota kolekcijų') }}:</span>
                    <span class="font-mono">{{ status.typesense.collections.memory.estimated_collections_mb }} MB</span>
                  </div>
                  <div v-if="status.typesense.collections.memory.note" class="col-span-2 pt-2 text-xs text-muted-foreground border-t border-blue-300 dark:border-blue-700">
                    {{ status.typesense.collections.memory.note }}
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-3">
                <div v-for="collection in status.typesense.collections.details" :key="collection.name" 
                     class="flex items-center justify-between p-3 rounded-md border bg-muted/10">
                  <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-primary"></div>
                    <div>
                      <div class="font-medium text-sm">{{ collection.name }}</div>
                      <div class="text-xs text-muted-foreground">
                        {{ collection.fields }} {{ $t('laukai') }} • {{ $t('rikiuoti pagal') }} {{ collection.default_sorting_field }}
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-sm">{{ collection.documents }}</div>
                    <div class="text-xs text-muted-foreground">{{ $t('dokumentų') }}</div>
                  </div>
                </div>
              </div>
              
              <!-- Total Summary -->
              <div class="mt-4 p-3 rounded-md bg-primary/5 border border-primary/20">
                <div class="flex justify-between items-center">
                  <span class="font-medium">{{ $t('Iš viso') }}:</span>
                  <div class="text-right">
                    <div class="font-bold">{{ status.typesense.collections.total_documents }}</div>
                    <div class="text-xs text-muted-foreground">
                      {{ status.typesense.collections.count }} {{ $t('kolekcijose') }}
                      <span v-if="status.typesense.collections.memory?.active_memory_mb || status.typesense.collections.memory?.resident_memory_mb || status.typesense.collections.memory?.estimated_collections_mb">
                        • {{ status.typesense.collections.memory.active_memory_mb || status.typesense.collections.memory.resident_memory_mb || status.typesense.collections.memory.estimated_collections_mb }} MB
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Schema Validation Issues -->
            <div v-if="status.typesense.schema_validation?.has_issues" class="mt-6">
              <div class="rounded-md bg-yellow-50 p-4 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-start gap-3">
                  <AlertTriangleIcon class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" />
                  <div class="flex-1">
                    <h5 class="font-medium text-sm text-yellow-800 dark:text-yellow-200 mb-2">
                      {{ $t('Schemos neatitikimai rasti') }}
                    </h5>
                    <div class="space-y-3">
                      <div v-for="mismatch in status.typesense.schema_validation.mismatches" :key="mismatch.model" 
                           class="text-sm">
                        <div class="font-medium text-yellow-800 dark:text-yellow-200">
                          {{ mismatch.model }}: {{ mismatch.message }}
                        </div>
                        <div v-if="mismatch.missing_in_typesense?.length" class="mt-1 text-yellow-700 dark:text-yellow-300">
                          {{ $t('Trūkstami laukai') }}: {{ mismatch.missing_in_typesense.join(', ') }}
                        </div>
                        <div v-if="mismatch.extra_in_typesense?.length" class="mt-1 text-yellow-700 dark:text-yellow-300">
                          {{ $t('Papildomi laukai') }}: {{ mismatch.extra_in_typesense.join(', ') }}
                        </div>
                        <div v-if="mismatch.action" class="mt-2">
                          <code class="px-2 py-1 bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded text-xs">
                            {{ mismatch.action }}
                          </code>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Integrations -->
      <Card v-if="status.integrations" class="hover:shadow-lg transition-shadow duration-300">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <LinkIcon class="h-5 w-5" />
            {{ $t('Integracijos') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Microsoft Integration -->
          <div class="rounded-md border p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <component 
                  :is="getStatusIcon(status.integrations.microsoft?.status === 'configured' ? 'healthy' : 'error')" 
                  :class="getStatusColor(status.integrations.microsoft?.status === 'configured' ? 'healthy' : 'error')"
                  class="h-4 w-4"
                />
                <span class="font-medium">Microsoft API</span>
              </div>
              <Badge :variant="status.integrations.microsoft?.configured ? 'default' : 'secondary'">
                {{ status.integrations.microsoft?.configured ? $t('Sukonfigūruota') : $t('Nesukonfigūruota') }}
              </Badge>
            </div>
          </div>

          <!-- SharePoint Integration -->
          <div class="rounded-md border p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <component 
                  :is="getStatusIcon(status.integrations.sharepoint?.status === 'configured' ? 'healthy' : 'error')" 
                  :class="getStatusColor(status.integrations.sharepoint?.status === 'configured' ? 'healthy' : 'error')"
                  class="h-4 w-4"
                />
                <span class="font-medium">SharePoint API</span>
              </div>
              <Badge :variant="status.integrations.sharepoint?.configured ? 'default' : 'secondary'">
                {{ status.integrations.sharepoint?.configured ? $t('Sukonfigūruota') : $t('Nesukonfigūruota') }}
              </Badge>
            </div>
            <div v-if="status.integrations.sharepoint?.configured" class="mt-2 text-sm text-muted-foreground">
              {{ $t('Tenant ID') }}: {{ status.integrations.sharepoint.tenant_id }}
            </div>
          </div>

          <!-- Mail Integration -->
          <div class="rounded-md border p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <component 
                  :is="getStatusIcon(status.integrations.mail?.status === 'configured' ? 'healthy' : 'error')" 
                  :class="getStatusColor(status.integrations.mail?.status === 'configured' ? 'healthy' : 'error')"
                  class="h-4 w-4"
                />
                <span class="font-medium">{{ $t('El. paštas') }}</span>
              </div>
              <Badge :variant="status.integrations.mail?.configured ? 'default' : 'secondary'">
                {{ status.integrations.mail?.configured ? $t('Sukonfigūruota') : $t('Nesukonfigūruota') }}
              </Badge>
            </div>
            <div v-if="status.integrations.mail?.configured" class="mt-2 text-sm text-muted-foreground">
              {{ status.integrations.mail.driver }} - {{ status.integrations.mail.host }}:{{ status.integrations.mail.port }}
            </div>
          </div>

          <!-- Scout Integration -->
          <div class="rounded-md border p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <component 
                  :is="getStatusIcon(status.integrations.scout?.status === 'configured' ? 'healthy' : 'warning')" 
                  :class="getStatusColor(status.integrations.scout?.status === 'configured' ? 'healthy' : 'warning')"
                  class="h-4 w-4"
                />
                <span class="font-medium">{{ $t('Paieška') }} (Scout)</span>
              </div>
              <Badge :variant="status.integrations.scout?.configured ? 'default' : 'secondary'">
                {{ status.integrations.scout?.status === 'configured' ? $t('Įjungta') : $t('Išjungta') }}
              </Badge>
            </div>
            <div v-if="status.integrations.scout?.configured" class="mt-2 space-y-1 text-sm text-muted-foreground">
              <div>{{ $t('Tvarkyklė') }}: {{ status.integrations.scout.driver }}</div>
              <div v-if="status.integrations.scout.queue_enabled">
                {{ $t('Eilė') }}: {{ status.integrations.scout.queue_enabled ? $t('Įjungta') : $t('Išjungta') }}
                • {{ $t('Gabalo dydis') }}: {{ status.integrations.scout.chunk_size }}
              </div>
              <div v-if="status.integrations.scout.driver === 'typesense'">
                Typesense: 
                <Badge :variant="status.integrations.scout.typesense_configured ? 'default' : 'destructive'" size="sm">
                  {{ status.integrations.scout.typesense_configured ? $t('Sukonfigūruotas') : $t('Nesukonfigūruotas') }}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- System Information -->
      <Card v-if="status.system" class="hover:shadow-lg transition-shadow duration-300">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <MonitorIcon class="h-5 w-5" />
            {{ $t('Sistemos informacija') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 gap-3 text-sm">
            <div class="flex justify-between">
              <span class="font-medium">{{ $t('Laravel versija') }}:</span>
              <span>{{ status.system.laravel_version }}</span>
            </div>
            <div class="flex justify-between">
              <span class="font-medium">{{ $t('PHP versija') }}:</span>
              <span>{{ status.system.php_version }}</span>
            </div>
            <div class="flex justify-between">
              <span class="font-medium">{{ $t('Aplinka') }}:</span>
              <Badge :variant="status.system.environment === 'production' ? 'default' : 'secondary'">
                {{ status.system.environment }}
              </Badge>
            </div>
            <div class="flex justify-between">
              <span class="font-medium">{{ $t('Derinimo režimas') }}:</span>
              <Badge :variant="status.system.debug_mode ? 'destructive' : 'default'">
                {{ status.system.debug_mode ? $t('Įjungtas') : $t('Išjungtas') }}
              </Badge>
            </div>
            <div class="flex justify-between">
              <span class="font-medium">{{ $t('Atmintis') }}:</span>
              <span>{{ status.system.memory_limit }}</span>
            </div>
            
            <!-- Disk Space -->
            <div v-if="status.system.disk_space && !status.system.disk_space.error">
              <div class="flex justify-between">
                <span class="font-medium">{{ $t('Disko vieta') }}:</span>
                <span>{{ status.system.disk_space.used }} / {{ status.system.disk_space.total }}</span>
              </div>
              <div class="mt-1">
                <div class="h-2 w-full rounded-full bg-muted">
                  <div 
                    class="h-2 rounded-full bg-primary" 
                    :style="{ width: status.system.disk_space.percentage }"
                  ></div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                  {{ $t('Naudojama') }} {{ status.system.disk_space.percentage }}
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Cache Performance -->
    <Card v-if="status.redis && !status.redis.error" class="mt-6 h-fit hover:shadow-lg transition-shadow duration-300">
      <CardHeader size="compact">
        <CardTitle class="flex items-center gap-2">
          <TrendingUpIcon class="h-5 w-5" />
          {{ $t('Talpyklos statistika') }}
        </CardTitle>
      </CardHeader>
      <CardContent size="compact" class="pb-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="text-center">
            <div data-redis-hits class="text-2xl font-bold text-green-600 font-mono tabular-nums">{{ status.redis.keyspace_hits }}</div>
            <p class="text-sm text-muted-foreground">{{ $t('Pataikymai') }}</p>
          </div>
          <div class="text-center">
            <div data-redis-misses class="text-2xl font-bold text-red-600 font-mono tabular-nums">{{ status.redis.keyspace_misses }}</div>
            <p class="text-sm text-muted-foreground">{{ $t('Nepataikimai') }}</p>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ status.redis.hit_ratio }}</div>
            <p class="text-sm text-muted-foreground">{{ $t('Pataikymo koeficientas') }}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </AdminContentPage>
</template>

<script setup lang="ts">
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router, usePoll } from '@inertiajs/vue3';
import { gsap } from 'gsap';

// UI components
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';

// Icons
import {
  CheckCircleIcon,
  XCircleIcon,
  AlertTriangleIcon,
  DatabaseIcon,
  HardDriveIcon,
  LinkIcon,
  MonitorIcon,
  TrendingUpIcon,
  SearchIcon,
} from 'lucide-vue-next';

import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";

// Props
const props = defineProps<{
  status: {
    redis?: {
      status: string;
      connected: boolean;
      memory_used?: string;
      memory_rss?: string;
      memory_peak?: string;
      connected_clients?: number;
      commands_processed?: string;
      keyspace_hits?: string;
      keyspace_misses?: string;
      hit_ratio?: string;
      uptime?: string;
      version?: string;
      error?: string;
    };
    database?: {
      status: string;
      connected: boolean;
      connection_time?: string;
      database_size?: string;
      driver?: string;
      version?: string;
      error?: string;
    };
    cache?: {
      status: string;
      driver?: string;
      working: boolean;
      test_result?: string;
      error?: string;
    };
    typesense?: {
      status: string;
      configured: boolean;
      enabled: boolean;
      connected: boolean;
      connection_time?: string;
      health?: {
        ok: boolean;
      };
      collections?: {
        count: number;
        total_documents: string;
        details: Array<{
          name: string;
          documents: string;
          fields: number;
          default_sorting_field: string;
        }>;
        memory?: {
          active_memory_mb?: number;
          resident_memory_mb?: number;
          allocated_memory_mb?: number;
          mapped_memory_mb?: number;
          metadata_memory_mb?: number;
          fragmentation_ratio?: number | null;
          estimated_collections_mb?: number;
          note?: string;
        };
      };
      configuration?: {
        driver: string;
        host: string;
        port: string;
        protocol: string;
        api_key_configured: boolean;
        search_only_key_configured: boolean;
        queue_enabled: boolean;
        configured_models: string[];
      };
      schema_validation?: {
        has_issues: boolean;
        mismatches: Array<{
          model: string;
          collection?: string;
          issue: string;
          missing_in_typesense?: string[];
          extra_in_typesense?: string[];
          message: string;
          action?: string;
        }>;
        checked_at: string;
      };
      error?: string;
      error_type?: string;
    };
    integrations?: {
      microsoft?: {
        configured: boolean;
        client_id_set: boolean;
        client_secret_set: boolean;
        status: string;
      };
      sharepoint?: {
        configured: boolean;
        client_id_set: boolean;
        client_secret_set: boolean;
        tenant_id?: string;
        status: string;
      };
      mail?: {
        driver?: string;
        host?: string;
        port?: number;
        encryption?: string;
        configured: boolean;
        status: string;
      };
      scout?: {
        driver?: string;
        configured: boolean;
        status: string;
        queue_enabled?: boolean;
        after_commit?: boolean;
        chunk_size?: number;
        typesense_configured?: boolean;
      };
    };
    system?: {
      php_version?: string;
      laravel_version?: string;
      environment?: string;
      debug_mode?: boolean;
      timezone?: string;
      locale?: string;
      url?: string;
      memory_limit?: string;
      max_execution_time?: string;
      upload_max_filesize?: string;
      disk_space?: {
        total?: string;
        used?: string;
        free?: string;
        percentage?: string;
        error?: string;
      };
    };
  };
  lastUpdated: string;
}>();

// Animation state
const countdown = ref(30);
const isPolling = ref(false);
const previousData = ref<Record<string, any>>({});

// Auto-polling setup - refresh status every 30 seconds
const { start, stop } = usePoll(30000, { 
  only: ['status', 'lastUpdated'],
  onStart: () => {
    isPolling.value = true;
    animatePollingStart();
  },
  onFinish: () => {
    isPolling.value = false;
    animateDataUpdate();
    countdown.value = 30;
  }
});

// Countdown timer
const countdownInterval = ref<NodeJS.Timeout>();

onMounted(() => {
  // Store initial data for comparison
  previousData.value = JSON.parse(JSON.stringify(props.status));
  
  // Start countdown
  startCountdown();
  
  // Initial animations
  nextTick(() => {
    animateInitialLoad();
  });
});

onUnmounted(() => {
  // Clean up countdown interval
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value);
  }
});

const startCountdown = () => {
  countdownInterval.value = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--;
    } else {
      countdown.value = 30;
    }
  }, 1000);
};

// Status icon and color helpers
const getStatusIcon = (status?: string) => {
  switch (status) {
    case 'healthy':
      return CheckCircleIcon;
    case 'warning':
      return AlertTriangleIcon;
    case 'error':
    default:
      return XCircleIcon;
  }
};

const getStatusColor = (status?: string) => {
  switch (status) {
    case 'healthy':
      return 'text-green-500';
    case 'warning':
      return 'text-yellow-500';
    case 'error':
    default:
      return 'text-red-500';
  }
};

// Format last updated time
const formatLastUpdated = computed(() => {
  if (!props.lastUpdated) return $t('Niekada');
  
  const date = new Date(props.lastUpdated);
  return date.toLocaleString('lt-LT', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
});


// Animation functions
const animateInitialLoad = () => {
  // Just animate numbers, no card entrance effects
  animateNumbers();
};

const animatePollingStart = () => {
  // Simple pulse effect
  isPolling.value = true;
};

const animateDataUpdate = () => {
  // Just animate numbers on update
  animateNumbers();
  
  // Update previous data
  previousData.value = JSON.parse(JSON.stringify(props.status));
};

const animateNumbers = () => {
  // Animate Redis stats with simple GSAP counters
  animateCounter('[data-redis-hits]', parseFloat(props.status.redis?.keyspace_hits) || 0);
  animateCounter('[data-redis-misses]', parseFloat(props.status.redis?.keyspace_misses) || 0);
  animateCounter('[data-redis-clients]', props.status.redis?.connected_clients || 0);
};

const animateCounter = (selector: string, endValue: number) => {
  const elements = document.querySelectorAll(selector);
  elements.forEach(el => {
    const startValue = parseFloat(el.textContent || '0') || 0;
    
    gsap.fromTo({ value: startValue }, {
      value: endValue,
      duration: 1.0,
      ease: 'power2.out',
      onUpdate: function() {
        el.textContent = Math.round(this.targets()[0].value).toString();
      }
    });
  });
};

const hasDataChanged = () => {
  return JSON.stringify(previousData.value) !== JSON.stringify(props.status);
};

// Watch for prop changes to trigger animations
watch(() => props.status, () => {
  nextTick(() => {
    animateDataUpdate();
  });
}, { deep: true });

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs([
  { label: $t('Sistemos būsena') }
]);
</script>

<style scoped>
/* Minimal styles for GSAP number counter animation */
[data-redis-hits], 
[data-redis-misses], 
[data-redis-clients] {
  display: inline-block;
}
</style>
