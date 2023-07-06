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

        if (in_array($response->getStatusCode(), [403])) {
            // check if inertia request
            if (!$response->headers->get('x-inertia')) {
                return $response;
            }
            return back()->with([
                'info' => $e->getMessage() ?? 'Neturite teisių atlikti šiam veiksmui.',
            ]);
        }

        return $response;
    }
}
