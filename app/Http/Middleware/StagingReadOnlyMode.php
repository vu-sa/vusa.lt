<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce read-only mode for shared resources in staging.
 *
 * This middleware blocks write operations (POST, PUT, PATCH, DELETE) on
 * specific routes when FILES_READ_ONLY or SHAREPOINT_READ_ONLY is enabled.
 *
 * This protects production data when staging shares file storage or SharePoint
 * with the production environment.
 */
class StagingReadOnlyMode
{
    /**
     * File-related routes that should be blocked when FILES_READ_ONLY=true
     * These are routes that modify files in storage/app/public
     */
    protected array $fileRoutes = [
        'files.store',           // Upload files
        'files.destroy',         // Delete single file
        'files.update',          // Update file
        'files.delete',          // Delete file
        'files.deleteDirectory', // Delete directory
        'files.bulkDelete',      // Bulk delete files
        'files.createDirectory', // Create directories
        'files.compress',        // Compress files
        'files.uploadImage',     // Upload image
    ];

    /**
     * SharePoint-related routes that should be blocked when SHAREPOINT_READ_ONLY=true
     * Only routes that WRITE to SharePoint are blocked
     * 
     * Note: documents.update, documents.destroy, documents.refresh, documents.bulk-sync
     * are ALLOWED because they only modify local database or READ from SharePoint
     */
    protected array $sharepointWriteRoutes = [
        'sharepointFiles.store',                  // Upload file TO SharePoint
        'sharepointFiles.destroy',                // Delete file FROM SharePoint
        'sharepoint.createDriveItemPublicLink',   // Create public link in SharePoint
    ];

    /**
     * SharePoint routes that are always allowed (read-only or database-only operations)
     */
    protected array $sharepointReadRoutes = [
        'documents.index',       // List documents (read DB)
        'documents.show',        // View document (read DB)
        'documents.store',       // Add document reference to DB from SharePoint picker
        'documents.update',      // Update document metadata in DB only
        'documents.destroy',     // Delete document reference from DB only (not SharePoint)
        'documents.refresh',     // Refresh document FROM SharePoint (read)
        'documents.bulk-sync',   // Bulk sync FROM SharePoint (read)
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply in staging environment
        if (config('app.env') !== 'staging') {
            return $next($request);
        }

        // Only check write methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return $next($request);
        }

        // Check file storage read-only mode
        if (config('app.files_read_only') && $this->isFileRoute($routeName)) {
            return $this->readOnlyResponse(
                'File modifications are disabled in staging environment. ' .
                'Files are shared with production.'
            );
        }

        // Check SharePoint read-only mode
        if (config('app.sharepoint_read_only') && $this->isSharepointWriteRoute($routeName)) {
            return $this->readOnlyResponse(
                'SharePoint modifications are disabled in staging environment. ' .
                'SharePoint is shared with production.'
            );
        }

        return $next($request);
    }

    /**
     * Check if route is a file-modifying route
     */
    protected function isFileRoute(string $routeName): bool
    {
        return in_array($routeName, $this->fileRoutes);
    }

    /**
     * Check if route is a SharePoint write route
     */
    protected function isSharepointWriteRoute(string $routeName): bool
    {
        return in_array($routeName, $this->sharepointWriteRoutes);
    }

    /**
     * Return appropriate read-only error response
     */
    protected function readOnlyResponse(string $message): Response
    {
        // For Inertia requests, redirect back with an error flash message
        // Use 303 status to force GET method on redirect (prevents DELETE/POST following)
        if (request()->header('X-Inertia')) {
            return redirect()->back(303)->with('error', $message);
        }

        // For API/JSON requests
        if (request()->wantsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'STAGING_READ_ONLY',
            ], 403);
        }

        abort(403, $message);
    }
}
