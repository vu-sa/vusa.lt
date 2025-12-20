<template>
  <PageContent :title="relationship.name" :back-url="route('relationships.index')">
    <UpsertModelLayout>
      <RelationshipForm :relationship
        @submit:form="(form: any) => form.patch(route('relationships.update', relationship.id), { preserveScroll: true })"
        @delete="() => router.delete(route('relationship.destroy', relationship.id))" />
    </UpsertModelLayout>

    <!-- Relationship Connections Section -->
    <FormElement class="mt-6">
      <template #title>
        <div class="flex items-center justify-between">
          <span>{{ $t('relationships.model_connections') }}</span>
          <Button size="sm" @click="openCreateModal">
            <PlusIcon class="mr-2 h-4 w-4" />
            {{ $t('relationships.create_new') }}
          </Button>
        </div>
      </template>

      <!-- Empty state -->
      <div v-if="!relationship.relationshipables?.length" class="flex flex-col items-center justify-center py-12 text-center">
        <LinkIcon class="h-12 w-12 text-muted-foreground/50 mb-4" />
        <h3 class="text-lg font-medium text-foreground">{{ $t('relationships.no_connections') }}</h3>
        <p class="mt-1 text-sm text-muted-foreground max-w-sm">
          {{ $t('relationships.no_connections_description') }}
        </p>
        <Button class="mt-4" @click="openCreateModal">
          <PlusIcon class="mr-2 h-4 w-4" />
          {{ $t('relationships.create_first') }}
        </Button>
      </div>

      <!-- Connections table -->
      <div v-else class="space-y-3">
        <div
          v-for="item in relationship.relationshipables"
          :key="item.id"
          class="flex items-center justify-between p-4 rounded-lg border bg-card hover:bg-accent/5 transition-colors"
        >
          <div class="flex items-center gap-4 min-w-0 flex-1">
            <!-- Source -->
            <div class="flex items-center gap-2 min-w-0">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                <ArrowRightIcon class="h-4 w-4 text-green-600 dark:text-green-400" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ item.relationshipable?.name ?? item.relationshipable?.title }}</p>
                <p class="text-xs text-muted-foreground">{{ $t('relationships.source') }}</p>
              </div>
            </div>

            <!-- Arrow -->
            <ChevronRightIcon class="h-5 w-5 text-muted-foreground shrink-0" />

            <!-- Target -->
            <div class="flex items-center gap-2 min-w-0">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                <TargetIcon class="h-4 w-4 text-blue-600 dark:text-blue-400" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ item.related_model?.name ?? item.related_model?.title }}</p>
                <p class="text-xs text-muted-foreground">{{ $t('relationships.target') }}</p>
              </div>
            </div>
          </div>

          <!-- Metadata badges -->
          <div class="flex items-center gap-2 shrink-0 ml-4">
            <!-- Type badge -->
            <Badge variant="secondary">
              {{ item.relationshipable_type?.split('\\').pop() === 'Type' ? $t('relationships.type_based') : $t('relationships.direct') }}
            </Badge>

            <!-- Scope badge (only for type-based) -->
            <Badge v-if="item.relationshipable_type?.split('\\').pop() === 'Type'" :variant="item.scope === 'cross-tenant' ? 'default' : 'outline'">
              {{ item.scope === 'cross-tenant' ? $t('forms.options.scope_cross_tenant') : $t('forms.options.scope_within_tenant') }}
            </Badge>

            <!-- Bidirectional badge -->
            <Badge :variant="item.bidirectional ? 'default' : 'outline'" :class="item.bidirectional ? 'bg-green-600' : ''">
              <ArrowLeftRightIcon v-if="item.bidirectional" class="h-3 w-3 mr-1" />
              <ArrowRightIcon v-else class="h-3 w-3 mr-1" />
              {{ item.bidirectional ? $t('relationships.bidirectional_yes') : $t('relationships.bidirectional_no') }}
            </Badge>

            <!-- Actions -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="h-8 w-8">
                  <MoreHorizontalIcon class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem @click="openEditModal(item)">
                  <PencilIcon class="mr-2 h-4 w-4" />
                  {{ $t('forms.edit') }}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem class="text-destructive" @click="handleDeleteRelationship(item.id)">
                  <TrashIcon class="mr-2 h-4 w-4" />
                  {{ $t('forms.delete') }}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </div>
    </FormElement>

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
                <Button variant="outline" role="combobox" class="w-full justify-between h-10 font-normal">
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
                  <CommandList>
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
                <Button variant="outline" role="combobox" class="w-full justify-between h-10 font-normal">
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
                  <CommandList>
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

          <!-- Edit mode: Show source and target as read-only -->
          <div v-if="isEditing" class="space-y-4">
            <div class="flex items-center gap-4 p-4 rounded-lg bg-muted/50">
              <div class="flex-1">
                <p class="text-xs text-muted-foreground">{{ $t('relationships.source') }}</p>
                <p class="font-medium">{{ editingItem?.relationshipable?.name ?? editingItem?.relationshipable?.title }}</p>
              </div>
              <ArrowRightIcon class="h-5 w-5 text-muted-foreground" />
              <div class="flex-1">
                <p class="text-xs text-muted-foreground">{{ $t('relationships.target') }}</p>
                <p class="font-medium">{{ editingItem?.related_model?.name ?? editingItem?.related_model?.title }}</p>
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
            <p class="text-xs text-muted-foreground">{{ scopeExplanation }}</p>
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
            <p class="text-xs text-muted-foreground">{{ $t('relationships.bidirectional_explanation') }}</p>
          </div>

          <!-- Access explanation panel -->
          <div v-if="(relationForm.model_id && relationForm.related_model_id) || isEditing" class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
              <InfoIcon class="h-4 w-4" />
              {{ $t('relationships.access_explanation_title') }}
            </h4>
            <div class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
              <p v-html="accessExplanation"></p>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showModal = false">
            {{ $t('forms.cancel') }}
          </Button>
          <Button :disabled="!canSubmit" @click="submitRelationForm">
            {{ isEditing ? $t('forms.save') : $t('relationships.create') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </PageContent>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  ArrowLeftRightIcon,
  ArrowRightIcon,
  CheckIcon,
  ChevronRightIcon,
  ChevronsUpDownIcon,
  InfoIcon,
  LinkIcon,
  MoreHorizontalIcon,
  PencilIcon,
  PlusIcon,
  TargetIcon,
  TrashIcon,
} from "lucide-vue-next";

import { cn } from "@/Utils/Shadcn/utils";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/Components/ui/command";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { Label } from "@/Components/ui/label";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { modelTypes } from "@/Types/formOptions";

import FormElement from "@/Components/AdminForms/FormElement.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import RelationshipForm from "@/Components/AdminForms/RelationshipForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  relationship: App.Entities.Relationship;
  relatedModels?: Record<string, any>[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const editingItem = ref<any>(null);
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
  return relationForm.model_type === 'App\\Models\\Type';
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

// Computed explanation of what access this relationship grants
const accessExplanation = computed(() => {
  let sourceName: string;
  let targetName: string;
  
  if (isEditing.value && editingItem.value) {
    sourceName = `<strong>${editingItem.value.relationshipable?.name ?? editingItem.value.relationshipable?.title}</strong>`;
    targetName = `<strong>${editingItem.value.related_model?.name ?? editingItem.value.related_model?.title}</strong>`;
  } else {
    sourceName = `<strong>${getLabelForId(relationForm.model_id)}</strong>`;
    targetName = `<strong>${getLabelForId(relationForm.related_model_id)}</strong>`;
  }
  
  let baseExplanation = '';
  const isTypeBased = isEditing.value 
    ? editingItem.value?.relationshipable_type?.includes('Type')
    : isTypeBasedRelationship.value;
  
  if (isTypeBased) {
    if (relationForm.scope === SCOPE_CROSS_TENANT) {
      baseExplanation = $t('relationships.access_type_cross', { source: sourceName, target: targetName });
    } else {
      baseExplanation = $t('relationships.access_type_within', { source: sourceName, target: targetName });
    }
  } else {
    baseExplanation = $t('relationships.access_direct', { source: sourceName, target: targetName });
  }
  
  // Add bidirectional explanation
  if (relationForm.bidirectional) {
    baseExplanation += '<br/><span class="text-green-600 dark:text-green-400">' + $t('relationships.access_bidirectional_note') + '</span>';
  } else {
    baseExplanation += '<br/><span class="text-amber-600 dark:text-amber-400">' + $t('relationships.access_unidirectional_note') + '</span>';
  }
  
  return baseExplanation;
});

const options = computed(() => {
  if (!props.relatedModels) return [];
  return props.relatedModels.map((model) => ({
    label: model.name ?? model.title,
    value: model.id,
  }));
});

const canSubmit = computed(() => {
  if (isEditing.value) return true;
  return relationForm.model_type && relationForm.model_id && relationForm.related_model_id;
});

const modelTypeOptions = modelTypes.relationshipable.map((relationshipable) => ({
  label: relationshipable,
  value: "App\\Models\\" + relationshipable,
}));

function openCreateModal() {
  isEditing.value = false;
  editingItem.value = null;
  relationForm.reset();
  showModal.value = true;
}

function openEditModal(item: any) {
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
    only: ["relatedModels"],
  });
}

function submitRelationForm() {
  if (isEditing.value && editingItem.value) {
    // Update existing relationshipable
    router.patch(
      route("relationships.updateModelRelationship", editingItem.value.id),
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
      }
    );
  } else {
    // Create new relationshipable
    relationForm.post(
      route("relationships.storeModelRelationship", { relationship: props.relationship.id }),
      {
        onSuccess: () => {
          showModal.value = false;
          relationForm.reset();
        },
      }
    );
  }
}

function handleDeleteRelationship(id: number) {
  if (confirm($t('relationships.confirm_delete'))) {
    router.delete(route("relationships.deleteModelRelationship", id));
  }
}
</script>
