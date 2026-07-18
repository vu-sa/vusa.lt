/**
 * Workspace flavor of the shared collaborative-document composable: wires one
 * workspace document to its presence channel and API endpoints. Created when
 * the document editor opens; disposal flushes and disconnects.
 */
import {
  useCollaborativeDocument,
} from '@/Composables/useCollaborativeDocument';
import type { CollaborativeDocUser } from '@/Composables/useCollaborativeDocument';

export function useWorkspaceDocument(workspaceId: string, documentId: string, currentUser: CollaborativeDocUser) {
  return useCollaborativeDocument({
    channelName: `workspace-documents.${documentId}`,
    showUrl: route('api.v1.admin.workspaces.documents.state.show', { workspace: workspaceId, document: documentId }),
    persistUrl: route('api.v1.admin.workspaces.documents.state.update', { workspace: workspaceId, document: documentId }),
    htmlField: 'content_html',
    currentUser,
  });
}
