<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
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
     * @return Response
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof HttpException && $this->isMaintenanceModeException($e) && ! $request->expectsJson()) {
            return response()->view('errors.maintenance', ['exception' => $e], 503, $e->getHeaders());
        }

        $response = parent::render($request, $e);

        // Handle 403 errors with redirect and flash message for Inertia requests
        // Direct visits will get the full 403 error page
        if ($response->getStatusCode() === 403) {
            if ($request->header('X-Inertia')) {
                return back()->with([
                    'error' => __($e->getMessage() ?: 'This action is unauthorized.'),
                ]);
            }
        }

        return $response;
    }

    /**
     * Determine whether the exception is the 503 raised by PreventRequestsDuringMaintenance.
     *
     * That middleware throws a plain HttpException with no maintenance-specific type, so the
     * only way to tell it apart from a genuine 503 (which should keep the errors::503 view)
     * is to ask the application whether it is currently down.
     */
    private function isMaintenanceModeException(HttpException $e): bool
    {
        return $e->getStatusCode() === 503 && app()->isDownForMaintenance();
    }
}
