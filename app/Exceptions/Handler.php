<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Prepare exception for rendering.
     *
     * @return \Throwable
     */
    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        // TODO: Maybe make errors work something like this: https://inertiajs.com/error-handling

        // Resolve 403 errors with a flash message
        // But only if the request is an Inertia request, otherwise it results in a redirect loop
        if (! in_array($response->getStatusCode(), [403])
            || ! $response->headers->get('x-inertia') === 'true') {
            return $response;
        }

        return back()->with([
            'info' => $e->getMessage() ?? 'Neturite teisių atlikti šiam veiksmui.',
            'statusCode' => $response->getStatusCode(),
        ]);
    }
}
