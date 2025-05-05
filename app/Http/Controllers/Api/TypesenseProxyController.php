<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Typesense\Client;

class TypesenseProxyController extends Controller
{
    protected $client;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Initialize Typesense client using the complete configuration from scout.php
        $this->client = new Client(config('scout.typesense.client-settings'));
    }

    /**
     * Pass through InstantSearch multi-index search requests to Typesense
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function multiSearch(Request $request)
    {
        try {
            // Validate the payload
            $payload = $this->validateSearchPayload($request);
            
            // Direct pass-through to Typesense's multi-search API
            $response = $this->client->multiSearch->perform(
                ['searches' => $payload->searches]
            );
            
            return response()->json(['results' => $response['results']]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Invalid request format', 'details' => $e->errors()], 400);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Typesense proxy error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return an error response
            return response()->json(
                ['error' => 'Search service unavailable', 'message' => config('app.debug') ? $e->getMessage() : 'Internal search error'],
                503
            );
        }
    }

    /**
     * Validate the search payload
     *
     * @param Request $request
     * @return object
     * @throws ValidationException
     */
    protected function validateSearchPayload(Request $request)
    {
        $content = $request->getContent();
        $payload = json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'payload' => ['Invalid JSON format']
            ]);
        }

        $validator = Validator::make((array)$payload, [
            'searches' => 'required|array',
            'searches.*.collection' => 'required|string|in:documents,pages,news'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional security checks
        foreach ($payload->searches as $search) {
            // Prevent potential injection by ensuring only allowed parameters are used
            $allowedParams = ['q', 'query_by', 'filter_by', 'sort_by', 'page', 'per_page', 'facet_by', 'max_facet_values'];
            foreach ((array)$search as $key => $value) {
                if (!in_array($key, $allowedParams) && $key !== 'collection') {
                    throw ValidationException::withMessages([
                        'searches' => ["Unsupported parameter: {$key}"]
                    ]);
                }
            }
        }

        return $payload;
    }
}
