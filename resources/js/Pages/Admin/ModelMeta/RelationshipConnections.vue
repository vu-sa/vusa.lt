<template>
  <div class="max-w-4xl space-y-4">
    <FormSection
      :title="$t('relationships.model_connections')"
      :badge="String(relationship.relationshipables?.length ?? 0)"
    >
      <div v-if="!relationship.relationshipables?.length" class="border-y border-border py-6">
        <p class="text-sm font-medium text-foreground">
          {{ $t('relationships.no_connections') }}
        </p>
        <p class="mt-1 max-w-prose text-sm text-muted-foreground">
          {{ $t('relationships.no_connections_description') }}
        </p>
      </div>

      <ul v-else class="divide-y divide-border border-y border-border">
        <li
          v-for="item in relationship.relationshipables"
          :key="item.id"
          class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"
        >
          <div class="grid min-w-0 flex-1 gap-2 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
            <div class="min-w-0">
              <p class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
                {{ $t('relationships.source') }}
              </p>
              <p class="truncate text-sm font-medium">
                {{ item.relationshipable?.name ?? item.relationshipable?.title }}
              </p>
            </div>
            <component
              :is="item.bidirectional ? ArrowLeftRightIcon : ArrowRightIcon"
              class="hidden size-4 text-muted-foreground sm:block"
              :aria-label="item.bidirectional ? $t('relationships.bidirectional_yes') : $t('relationships.bidirectional_no')"
            />
            <div class="min-w-0">
              <p class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
                {{ $t('relationships.target') }}
              </p>
              <p class="truncate text-sm font-medium">
                {{ item.related_model?.name ?? item.related_model?.title }}
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
            <Badge variant="outline">
              {{ isTypeBased(item) ? $t('relationships.type_based') : $t('relationships.direct') }}
            </Badge>
            <Badge v-if="isTypeBased(item)" variant="outline">
              {{ item.scope === SCOPE_CROSS_TENANT ? $t('forms.options.scope_cross_tenant') : $t('forms.options.scope_within_tenant') }}
            </Badge>
            <Badge variant="outline">
              {{ item.bidirectional ? $t('relationships.bidirectional_yes') : $t('relationships.bidirectional_no') }}
            </Badge>

            <DropdownMenu v-if="canUpdate">
              <DropdownMenuTrigger as-child>
                <Button type="button" variant="ghost" size="icon" class="pointer-coarse:size-11" :aria-label="$t('Veiksmai')">
                  <MoreHorizontalIcon class="size-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem @click="openEditModal(item)">
                  <PencilIcon class="mr-2 size-4" />
                  {{ $t('forms.edit') }}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem class="text-destructive" @click="connectionPendingDelete = item.id">
                  <TrashIcon class="mr-2 size-4" />
                  {{ $t('forms.delete') }}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </li>
      </ul>

      <SpotlightPopover
        v-if="canUpdate"
        :title="$t('relationships.model_connections')"
        :description="$t('Susietus įrašus dabar tvarkyk ryšio puslapyje.')"
        :is-dismissed="connectionsSpotlight.isDismissed.value"
        @dismiss="connectionsSpotlight.dismiss"
      >
        <Button type="button" variant="outline" class="pointer-coarse:min-h-11" @click="openCreateModal">
          <PlusIcon class="size-4" />
          {{ relationship.relationshipables?.length ? $t('relationships.create_new') : $t('relationships.create_first') }}
        </Button>
      </SpotlightPopover>
    </FormSection>
  </div>
  <!-- Create/Edit Dialog -->
  <Dialog v-model:open="showModal">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ isEditing ? $t('relationships.edit_connection') : $t('relationships.create_new') }}</DialogTitle>
        <DialogDescription>
          {{ isEditing ? $t('relationships.edit_connection_description') : $t('relationships.create_new_description') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6 py-4">
        <!-- Model Type (only for create) -->
        <div v-if="!isEditing" class="space-y-2">
          <Label>{{ $t('relationships.model_type') }}</Label>
          <Select v-model="relationForm.model_type" @update:model-value="handleUpdateModelType">
            <SelectTrigger>
              <SelectValue :placeholder="$t('relationships.select_model_type')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in modelTypeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Source -->
        <div v-if="!isEditing" class="space-y-2">
          <Label>
            {{ $t('relationships.source') }}
            <span class="text-xs text-muted-foreground font-normal ml-1">({{ $t('relationships.source_hint') }})</span>
          </Label>
          <Popover v-model:open="sourceOpen">
            <PopoverTrigger as-child>
              <Button type="button" variant="outline" role="combobox" aria-controls="relationship-source-options" :aria-expanded="sourceOpen" class="w-full justify-between font-normal pointer-coarse:min-h-11">
                <span :class="relationForm.model_id ? '' : 'text-muted-foreground'">
                  {{ selectedSourceLabel || $t('relationships.select_source') }}
                </span>
                <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-full p-0" align="start">
              <Command>
                <CommandInput :placeholder="$t('relationships.search_model')" />
                <CommandEmpty>{{ $t('relationships.no_results') }}</CommandEmpty>
                <CommandList id="relationship-source-options">
                  <CommandGroup>
                    <CommandItem
                      v-for="option in options"
                      :key="option.value"
                      :value="option.label"
                      @select="selectSource(option.value)"
                    >
                      <CheckIcon :class="cn('mr-2 h-4 w-4', relationForm.model_id === option.value ? 'opacity-100' : 'opacity-0')" />
                      {{ option.label }}
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
        </div>

        <!-- Target -->
        <div v-if="!isEditing" class="space-y-2">
          <Label>
            {{ $t('relationships.target') }}
            <span class="text-xs text-muted-foreground font-normal ml-1">({{ $t('relationships.target_hint') }})</span>
          </Label>
          <Popover v-model:open="targetOpen">
            <PopoverTrigger as-child>
              <Button type="button" variant="outline" role="combobox" aria-controls="relationship-target-options" :aria-expanded="targetOpen" class="w-full justify-between font-normal pointer-coarse:min-h-11">
                <span :class="relationForm.related_model_id ? '' : 'text-muted-foreground'">
                  {{ selectedTargetLabel || $t('relationships.select_target') }}
                </span>
                <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-full p-0" align="start">
              <Command>
                <CommandInput :placeholder="$t('relationships.search_model')" />
                <CommandEmpty>{{ $t('relationships.no_results') }}</CommandEmpty>
                <CommandList id="relationship-target-options">
                  <CommandGroup>
                    <CommandItem
                      v-for="option in options"
                      :key="option.value"
                      :value="option.label"
                      @select="selectTarget(option.value)"
                    >
                      <CheckIcon :class="cn('mr-2 h-4 w-4', relationForm.related_model_id === option.value ? 'opacity-100' : 'opacity-0')" />
                      {{ option.label }}
                    </CommandItem>
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
        </div>

        <!-- Same type error message -->
        <p v-if="sameTypeError" class="flex items-start gap-2 border-l-2 border-status-attention bg-status-attention-surface px-3 py-2 text-sm text-status-attention">
          <InfoIcon class="mt-0.5 size-4 shrink-0" />
          <span>{{ sameTypeError }}</span>
        </p>

        <!-- Edit mode: Show source and target as read-only -->
        <div v-if="isEditing" class="space-y-4">
          <div class="flex items-center gap-4 border border-border p-4">
            <div class="flex-1">
              <p class="text-xs text-muted-foreground">
                {{ $t('relationships.source') }}
              </p>
              <p class="font-medium">
                {{ editingItem?.relationshipable?.name ?? editingItem?.relationshipable?.title }}
              </p>
            </div>
            <ArrowRightIcon class="h-5 w-5 text-muted-foreground" />
            <div class="flex-1">
              <p class="text-xs text-muted-foreground">
                {{ $t('relationships.target') }}
              </p>
              <p class="font-medium">
                {{ editingItem?.related_model?.name ?? editingItem?.related_model?.title }}
              </p>
            </div>
          </div>
        </div>

        <!-- Scope (only for type-based) -->
        <div v-if="isTypeBasedRelationship || (isEditing && editingItem?.relationshipable_type?.includes('Type'))" class="space-y-2">
          <Label>{{ $t('forms.fields.relationship_scope') }}</Label>
          <Select v-model="relationForm.scope">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in scopeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
          <p class="text-xs text-muted-foreground">
            {{ scopeExplanation }}
          </p>
        </div>

        <!-- Bidirectional toggle -->
        <div class="space-y-3">
          <Label>{{ $t('relationships.bidirectional') }}</Label>
          <div class="flex items-center gap-3">
            <Switch
              v-model="relationForm.bidirectional"
            />
            <span class="text-sm text-muted-foreground">
              {{ relationForm.bidirectional ? $t('relationships.bidirectional_enabled') : $t('relationships.bidirectional_disabled') }}
            </span>
          </div>
          <p class="text-xs text-muted-foreground">
            {{ $t('relationships.bidirectional_explanation') }}
          </p>
        </div>

        <!-- Access explanation panel -->
        <div v-if="(relationForm.model_id && relationForm.related_model_id) || isEditing" class="border-l-2 border-status-info bg-status-info-surface px-4 py-3 text-status-info">
          <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold">
            <InfoIcon class="size-4" />
            {{ $t('relationships.access_explanation_title') }}
          </h4>
          <p class="text-sm" v-html="accessExplanation" />
        </div>
      </div>

      <DialogFooter>
        <Button type="button" variant="outline" @click="showModal = false">
          {{ $t('forms.cancel') }}
        </Button>
        <Button type="button" variant="brand" :disabled="!canSubmit" @click="submitRelationForm">
          {{ isEditing ? $t('forms.save') : $t('relationships.create') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <ConfirmDialog
    :open="connectionPendingDelete !== null"
    :title="$t('forms.delete')"
    :description="$t('relationships.confirm_delete')"
    :confirm-label="$t('forms.delete')"
    destructive
    @update:open="open => { if (!open) connectionPendingDelete = null; }"
    @confirm="deleteConnection"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
  ArrowLeftRightIcon,
  ArrowRightIcon,
  CheckIcon,
  ChevronsUpDownIcon,
  InfoIcon,
  MoreHorizontalIcon,
  PencilIcon,
  PlusIcon,
  TrashIcon,
} from 'lucide-vue-next';

import { ModelEnum } from '@/Types/enums';
import { cn } from '@/Utils/Shadcn/utils';
import { escapeHtml } from '@/Utils/String';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/Components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { modelTypeLabel, modelTypes } from '@/Types/formOptions';
import { ConfirmDialog } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = defineProps<{
  relationship: App.Entities.Relationship;
  relatedModels?: Array<{ id: string; name?: string; title?: string }>;
  canUpdate: boolean;
}>();

interface Connection {
  id: number;
  relationshipable_type: string;
  relationshipable?: { name?: string; title?: string };
  related_model?: { name?: string; title?: string };
  scope?: string;
  bidirectional: boolean;
}

const showModal = ref(false);
const connectionsSpotlight = useFeatureSpotlight('relationship-connections-v1');
const isEditing = ref(false);
const editingItem = ref<Connection | null>(null);
const sourceOpen = ref(false);
const targetOpen = ref(false);

// Scope constants matching backend
const SCOPE_WITHIN_TENANT = 'within-tenant';
const SCOPE_CROSS_TENANT = 'cross-tenant';

const relationTemplate = {
  model_type: null as string | null,
  model_id: null as string | null,
  related_model_id: null as string | null,
  relationship_id: props.relationship.id,
  scope: SCOPE_WITHIN_TENANT,
  bidirectional: false,
};

const relationForm = useForm(relationTemplate);

// Check if the current model type is Type (for showing scope selector)
const isTypeBasedRelationship = computed(() => {
  return relationForm.model_type === ModelEnum.TYPE;
});

// Scope options for the dropdown
const scopeOptions = computed(() => [
  { label: $t('forms.options.scope_within_tenant'), value: SCOPE_WITHIN_TENANT },
  { label: $t('forms.options.scope_cross_tenant'), value: SCOPE_CROSS_TENANT },
]);

// Explanation of what the current scope means
const scopeExplanation = computed(() => {
  if (relationForm.scope === SCOPE_CROSS_TENANT) {
    return $t('relationships.scope_cross_tenant_explanation');
  }
  return $t('relationships.scope_within_tenant_explanation');
});

// Get the label for a model ID
const getLabelForId = (id: string | null) => {
  if (!id || !props.relatedModels) return '?';
  const model = props.relatedModels.find(m => m.id === id);
  return model?.name ?? model?.title ?? '?';
};

// Selected labels for comboboxes
const selectedSourceLabel = computed(() => getLabelForId(relationForm.model_id));
const selectedTargetLabel = computed(() => getLabelForId(relationForm.related_model_id));

const isTypeBased = (item: { relationshipable_type?: string | null }): boolean =>
  item.relationshipable_type?.split('\\').pop() === 'Type';

// Computed explanation of what access this relationship grants
const accessExplanation = computed(() => {
  let sourceName: string;
  let targetName: string;

  if (isEditing.value && editingItem.value) {
    sourceName = `<strong>${escapeHtml(editingItem.value.relationshipable?.name ?? editingItem.value.relationshipable?.title)}</strong>`;
    targetName = `<strong>${escapeHtml(editingItem.value.related_model?.name ?? editingItem.value.related_model?.title)}</strong>`;
  }
  else {
    sourceName = `<strong>${escapeHtml(getLabelForId(relationForm.model_id))}</strong>`;
    targetName = `<strong>${escapeHtml(getLabelForId(relationForm.related_model_id))}</strong>`;
  }

  const isTypeBased = isEditing.value
    ? editingItem.value?.relationshipable_type?.includes('Type')
    : isTypeBasedRelationship.value;

  const baseExplanation = isTypeBased
    ? $t(relationForm.scope === SCOPE_CROSS_TENANT ? 'relationships.access_type_cross' : 'relationships.access_type_within', { source: sourceName, target: targetName })
    : $t('relationships.access_direct', { source: sourceName, target: targetName });

  return baseExplanation + `<br/><span class="font-medium">${relationForm.bidirectional
    ? $t('relationships.access_bidirectional_note')
    : $t('relationships.access_unidirectional_note')}</span>`;
});

const options = computed(() => {
  if (!props.relatedModels) return [];
  return props.relatedModels.map(model => ({
    label: model.name ?? model.title,
    value: model.id,
  }));
});

const canSubmit = computed(() => {
  if (isEditing.value) return true;
  if (!relationForm.model_type || !relationForm.model_id || !relationForm.related_model_id) return false;

  // For type-based relationships, source and target must be different
  // Same-type sibling relationships should be configured in the Type form instead
  if (isTypeBasedRelationship.value && relationForm.model_id === relationForm.related_model_id) {
    return false;
  }

  return true;
});

// Validation message for same-type selection
const sameTypeError = computed(() => {
  if (isTypeBasedRelationship.value
    && relationForm.model_id
    && relationForm.related_model_id
    && relationForm.model_id === relationForm.related_model_id) {
    return $t('relationships.same_type_error');
  }
  return null;
});

const modelTypeOptions = modelTypes.relationshipable.map(alias => ({
  label: modelTypeLabel(alias),
  value: alias,
}));

function openCreateModal() {
  connectionsSpotlight.dismiss();
  isEditing.value = false;
  editingItem.value = null;
  relationForm.reset();
  showModal.value = true;
}

function openEditModal(item: Connection) {
  isEditing.value = true;
  editingItem.value = item;
  relationForm.scope = item.scope ?? SCOPE_WITHIN_TENANT;
  relationForm.bidirectional = item.bidirectional ?? false;
  showModal.value = true;
}

function selectSource(value: string) {
  relationForm.model_id = value;
  sourceOpen.value = false;
}

function selectTarget(value: string) {
  relationForm.related_model_id = value;
  targetOpen.value = false;
}

function handleUpdateModelType(value: unknown) {
  if (typeof value !== 'string') return;
  relationForm.model_id = null;
  relationForm.related_model_id = null;
  router.reload({
    data: { modelType: value },
    only: ['relatedModels'],
  });
}

function submitRelationForm() {
  if (isEditing.value && editingItem.value) {
    // Update existing relationshipable
    router.patch(
      route('relationships.updateModelRelationship', editingItem.value.id),
      {
        scope: relationForm.scope,
        bidirectional: relationForm.bidirectional,
      },
      {
        onSuccess: () => {
          showModal.value = false;
          editingItem.value = null;
        },
        preserveScroll: true,
      },
    );
  }
  else {
    // Create new relationshipable
    relationForm.post(
      route('relationships.storeModelRelationship', { relationship: props.relationship.id }),
      {
        onSuccess: () => {
          showModal.value = false;
          relationForm.reset();
        },
      },
    );
  }
}

const connectionPendingDelete = ref<string | number | null>(null);

function deleteConnection(): void {
  if (connectionPendingDelete.value === null) {
    return;
  }

  router.delete(route('relationships.deleteModelRelationship', connectionPendingDelete.value), {
    preserveScroll: true,
    onFinish: () => {
      connectionPendingDelete.value = null;
    },
  });
}
</script>
