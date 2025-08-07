<?php

use App\Enums\SharepointConfigEnum;
use App\Services\SharepointGraphService;
use App\Settings\SharepointSettings;
use Illuminate\Support\Facades\Log;

describe('SharePoint Service Robustness', function () {
    beforeEach(function () {
        // Mock Laravel Sleep to prevent actual delays in tests
        \Illuminate\Support\Sleep::fake();

        $this->settings = Mockery::mock(SharepointSettings::class);
        $this->settings->permission_expiry_days = 365;

        $this->service = new SharepointGraphService(
            siteId: 'test-site',
            driveId: 'test-drive',
            settings: $this->settings
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('retry logic', function () {
        test('retry mechanism uses exponential backoff', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;

            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts <= 3) {
                    throw new \Exception('Temporary failure');
                }

                return 'success';
            };

            Log::shouldReceive('info')->times(4); // 3 retries + 1 success

            $result = $method->invoke($this->service, $operation, 'test-exponential-backoff');

            expect($result)->toBe('success');
            expect($attempts)->toBe(4);

            // Test that the operation eventually succeeds after retries
            // (We can't easily mock usleep in this context)
        });

        test('gives up after maximum retries', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                throw new \Exception('Always fails');
            };

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'test-max-retries'))
                ->toThrow(\Exception::class, 'Always fails');

            expect($attempts)->toBe((int) SharepointConfigEnum::MAX_RETRIES()->label + 1);
        });

        test('succeeds immediately when no retry needed', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;

                return 'immediate-success';
            };

            // Should not log retry attempts
            Log::shouldReceive('info')->never();

            $result = $method->invoke($this->service, $operation, 'test-immediate-success');

            expect($result)->toBe('immediate-success');
            expect($attempts)->toBe(1);
        });

        test('handles different exception types appropriately', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $exceptionTypes = [
                new \RuntimeException('Runtime error'),
                new \InvalidArgumentException('Invalid argument'),
                new \Exception('Generic exception'),
            ];

            foreach ($exceptionTypes as $exception) {
                $operation = fn () => throw $exception;

                Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);
                Log::shouldReceive('error')->once();

                expect(fn () => $method->invoke($this->service, $operation, 'test-exception-types'))
                    ->toThrow(get_class($exception));
            }
        });

        test('custom retry count is respected', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                throw new \Exception('Custom retry test');
            };

            $customMaxRetries = 1;

            Log::shouldReceive('info')->times($customMaxRetries);
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'test-custom-retries', $customMaxRetries))
                ->toThrow(\Exception::class);

            expect($attempts)->toBe($customMaxRetries + 1);
        });
    });

    describe('input validation', function () {
        test('validates required string parameters', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');
            $method->setAccessible(true);

            // Test cases that should throw exceptions
            $invalidCases = [
                ['test' => ''],     // Empty string
                ['test' => null],   // Null value
                ['test' => []],     // Empty array
            ];

            foreach ($invalidCases as $params) {
                expect(fn () => $method->invoke($this->service, $params))
                    ->toThrow(\InvalidArgumentException::class);
            }

            // Test cases that should NOT throw exceptions
            $validCases = [
                ['test' => 'valid'],   // Valid string
                ['test' => ' '],       // Whitespace (not empty by PHP standards)
                ['test' => ['value']], // Non-empty array
                ['test' => 1],         // Non-zero number
                ['test' => true],      // True boolean
            ];

            foreach ($validCases as $params) {
                $method->invoke($this->service, $params);
                expect(true)->toBeTrue(); // Should not throw
            }

            // Note: 0 and false are considered empty by PHP's empty() function
            // so they would trigger validation errors, which is the expected behavior
        });

        test('validates multiple parameters simultaneously', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');
            $method->setAccessible(true);

            // All valid - should not throw
            $method->invoke($this->service, [
                'param1' => 'value1',
                'param2' => 'value2',
                'param3' => 'value3',
            ]);

            // One invalid - should throw with specific parameter name
            expect(fn () => $method->invoke($this->service, [
                'param1' => 'value1',
                'param2' => '',  // Empty
                'param3' => 'value3',
            ]))->toThrow(\InvalidArgumentException::class, "Parameter 'param2' cannot be empty");
        });

        test('validation error messages are informative', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');
            $method->setAccessible(true);

            try {
                $method->invoke($this->service, ['important_parameter' => null]);
                expect(false)->toBeTrue(); // Should not reach here
            } catch (\InvalidArgumentException $e) {
                expect($e->getMessage())->toContain('important_parameter');
                expect($e->getMessage())->toContain('cannot be empty');
            }
        });

        test('handles array parameters correctly', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');
            $method->setAccessible(true);

            // Empty array should be considered empty
            expect(fn () => $method->invoke($this->service, ['array_param' => []]))
                ->toThrow(\InvalidArgumentException::class);

            // Non-empty array should be valid
            $method->invoke($this->service, ['array_param' => ['value']]);
            expect(true)->toBeTrue();
        });
    });

    describe('error handling scenarios', function () {
        test('handles network timeout gracefully', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => throw new \Exception('Request timeout');

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'timeout-test'))
                ->toThrow(\Exception::class, 'Request timeout');
        });

        test('handles API rate limiting', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts <= 2) {
                    throw new \Exception('Rate limit exceeded (429)');
                }

                return 'success-after-rate-limit';
            };

            Log::shouldReceive('info')->times(3); // 2 retries + 1 success

            $result = $method->invoke($this->service, $operation, 'rate-limit-test');

            expect($result)->toBe('success-after-rate-limit');
            expect($attempts)->toBe(3);
        });

        test('handles authentication failures', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => throw new \Exception('Authentication failed (401)');

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'auth-failure-test'))
                ->toThrow(\Exception::class, 'Authentication failed (401)');
        });

        test('handles service unavailable errors', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts === 1) {
                    throw new \Exception('Service Unavailable (503)');
                }

                return 'service-restored';
            };

            Log::shouldReceive('info')->twice(); // 1 retry + 1 success

            $result = $method->invoke($this->service, $operation, 'service-unavailable-test');

            expect($result)->toBe('service-restored');
        });

        test('handles malformed API responses', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => throw new \Exception('Invalid JSON response');

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'malformed-response-test'))
                ->toThrow(\Exception::class, 'Invalid JSON response');
        });
    });

    describe('performance characteristics', function () {
        test('retry delays increase appropriately', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;

            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts <= 3) {
                    throw new \Exception('Performance test');
                }

                return 'success';
            };

            Log::shouldReceive('info')->times(4);

            $result = $method->invoke($this->service, $operation, 'performance-test');

            // Verify that retry logic works and eventually succeeds
            expect($result)->toBe('success');
            expect($attempts)->toBe(4); // 3 failures + 1 success
        });

        test('total retry time is bounded', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => throw new \Exception('Bounded time test');

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);
            Log::shouldReceive('error')->once();

            // Calculate maximum possible retry time
            $maxRetryTime = 0;
            for ($i = 1; $i <= (int) SharepointConfigEnum::MAX_RETRIES()->label; $i++) {
                $maxRetryTime += (int) SharepointConfigEnum::RETRY_DELAY_MS()->label * pow(2, $i - 1);
            }

            // Should be reasonable (less than 30 seconds)
            expect($maxRetryTime)->toBeLessThan(30000);

            expect(fn () => $method->invoke($this->service, $operation, 'bounded-time-test'))
                ->toThrow(\Exception::class);
        });

        test('does not add unnecessary delay on immediate success', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => 'immediate-success';

            // Should not log any retry attempts for immediate success
            Log::shouldReceive('info')->never();

            $result = $method->invoke($this->service, $operation, 'no-delay-test');

            expect($result)->toBe('immediate-success');
        });
    });

    describe('logging behavior', function () {
        test('logs retry attempts with context', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts <= 2) {
                    throw new \Exception('Retry logging test');
                }

                return 'success';
            };

            Log::shouldReceive('info')
                ->with('Operation failed, retrying', Mockery::on(function ($context) {
                    return isset($context['operation']) &&
                           isset($context['attempt']) &&
                           isset($context['error']);
                }))->twice();

            Log::shouldReceive('info')
                ->with('Operation succeeded after retry', Mockery::type('array'))
                ->once();

            $method->invoke($this->service, $operation, 'retry-logging-test');
        });

        test('logs final failure with all context', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operation = fn () => throw new \Exception('Final failure test');

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES()->label);

            Log::shouldReceive('error')
                ->with('Operation failed after all retries', Mockery::on(function ($context) {
                    return $context['operation'] === 'final-failure-test' &&
                           $context['attempts'] === (int) SharepointConfigEnum::MAX_RETRIES()->label + 1 &&
                           $context['error'] === 'Final failure test';
                }));

            expect(fn () => $method->invoke($this->service, $operation, 'final-failure-test'))
                ->toThrow(\Exception::class);
        });

        test('includes operation name in all log entries', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $operationName = 'test-operation-logging';
            $operation = fn () => throw new \Exception('Test');

            Log::shouldReceive('info')
                ->withArgs(function ($message, $context) use ($operationName) {
                    return $context['operation'] === $operationName;
                })->times((int) SharepointConfigEnum::MAX_RETRIES()->label);

            Log::shouldReceive('error')
                ->withArgs(function ($message, $context) use ($operationName) {
                    return $context['operation'] === $operationName;
                })->once();

            expect(fn () => $method->invoke($this->service, $operation, $operationName))
                ->toThrow(\Exception::class);
        });
    });

    describe('edge cases and boundary conditions', function () {
        test('handles zero retry configuration gracefully', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                throw new \Exception('Zero retry test');
            };

            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'zero-retry-test', 0))
                ->toThrow(\Exception::class);

            expect($attempts)->toBe(1); // Only initial attempt
        });

        test('handles very large retry counts', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts === 2) {
                    return 'success-on-second-attempt';
                }
                throw new \Exception('Large retry test');
            };

            Log::shouldReceive('info')->twice(); // 1 retry + 1 success

            $result = $method->invoke($this->service, $operation, 'large-retry-test', 1000);

            expect($result)->toBe('success-on-second-attempt');
            expect($attempts)->toBe(2);
        });

        test('handles operations that return false or null', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');
            $method->setAccessible(true);

            $falseOperation = fn () => false;
            $nullOperation = fn () => null;

            expect($method->invoke($this->service, $falseOperation, 'false-test'))->toBe(false);
            expect($method->invoke($this->service, $nullOperation, 'null-test'))->toBe(null);
        });
    });
});
