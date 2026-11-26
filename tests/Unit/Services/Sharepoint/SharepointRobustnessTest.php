<?php

use App\Enums\SharepointConfigEnum;
use App\Services\Sharepoint\SharepointGraphService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

describe('SharePoint Service Robustness', function (): void {
    beforeEach(function (): void {
        // Mock Laravel Sleep to prevent actual delays in tests
        Sleep::fake();

        $this->service = new SharepointGraphService(
            siteId: 'test-site',
            driveId: 'test-drive',
            listId: null
        );
    });

    afterEach(function (): void {
        Mockery::close();
    });

    describe('retry logic', function (): void {
        test('retry mechanism uses exponential backoff', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $attempts = 0;

            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts <= 3) {
                    throw new Exception('Temporary failure');
                }

                return 'success';
            };

            Log::shouldReceive('info')->times(4); // 3 retries + 1 success

            $result = $method->invoke($this->service, $operation, 'test-exponential-backoff');

            expect($result)->toBe('success')
                ->and($attempts)->toBe(4);

            Sleep::assertSequence([
                Sleep::for(1000)->milliseconds(),
                Sleep::for(2000)->milliseconds(),
                Sleep::for(4000)->milliseconds(),
            ]);
        });

        test('gives up after maximum retries', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $attempts = 0;
            $operation = function () use (&$attempts): void {
                $attempts++;
                throw new Exception('Always fails');
            };

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES->label());
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'test-max-retries'))
                ->toThrow(Exception::class, 'Always fails')
                ->and($attempts)->toBe((int) SharepointConfigEnum::MAX_RETRIES->label() + 1);
        });

        test('succeeds immediately when no retry needed', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;

                return 'immediate-success';
            };

            // Should not log retry attempts
            Log::shouldReceive('info')->never();

            $result = $method->invoke($this->service, $operation, 'test-immediate-success');

            expect($result)->toBe('immediate-success')
                ->and($attempts)->toBe(1);

            Sleep::assertNeverSlept();
        });

        test('handles different exception types appropriately', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $exceptionTypes = [
                new RuntimeException('Runtime error'),
                new InvalidArgumentException('Invalid argument'),
                new Exception('Generic exception'),
            ];

            foreach ($exceptionTypes as $exception) {
                $operation = fn () => throw $exception;

                Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES->label());
                Log::shouldReceive('error')->once();

                expect(fn () => $method->invoke($this->service, $operation, 'test-exception-types'))
                    ->toThrow($exception::class);
            }
        });

        test('custom retry count is respected', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $attempts = 0;
            $operation = function () use (&$attempts): void {
                $attempts++;
                throw new Exception('Custom retry test');
            };

            $customMaxRetries = 1;

            Log::shouldReceive('info')->times($customMaxRetries);
            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'test-custom-retries', $customMaxRetries))
                ->toThrow(Exception::class)
                ->and($attempts)->toBe($customMaxRetries + 1);
        });
    });

    describe('input validation', function (): void {
        test('validates required string parameters', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');

            // Test cases that should throw exceptions
            $invalidCases = [
                ['test' => ''],     // Empty string
                ['test' => null],   // Null value
                ['test' => []],     // Empty array
            ];

            foreach ($invalidCases as $params) {
                expect(fn () => $method->invoke($this->service, $params))
                    ->toThrow(InvalidArgumentException::class);
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

        test('validates multiple parameters simultaneously', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('validateNotEmpty');

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
            ]))->toThrow(InvalidArgumentException::class, "Parameter 'param2' cannot be empty");
        });
    });

    describe('logging behavior', function (): void {
        test('logs retry attempts with context', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts <= 2) {
                    throw new Exception('Retry logging test');
                }

                return 'success';
            };

            Log::shouldReceive('info')
                ->with('Operation failed, retrying', Mockery::on(fn ($context) => isset($context['operation']) &&
                       isset($context['attempt']) &&
                       isset($context['error'])))->twice();

            Log::shouldReceive('info')
                ->with('Operation succeeded after retry', Mockery::type('array'))
                ->once();

            $method->invoke($this->service, $operation, 'retry-logging-test');
        });

        test('logs final failure with all context', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $operation = fn () => throw new Exception('Final failure test');

            Log::shouldReceive('info')->times((int) SharepointConfigEnum::MAX_RETRIES->label());

            Log::shouldReceive('error')
                ->with('Operation failed after all retries', Mockery::on(fn ($context) => $context['operation'] === 'final-failure-test' &&
                       $context['attempts'] === (int) SharepointConfigEnum::MAX_RETRIES->label() + 1 &&
                       $context['error'] === 'Final failure test'));

            expect(fn () => $method->invoke($this->service, $operation, 'final-failure-test'))
                ->toThrow(Exception::class);
        });

        test('includes operation name in all log entries', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $operationName = 'test-operation-logging';
            $operation = fn () => throw new Exception('Test');

            Log::shouldReceive('info')
                ->withArgs(fn ($message, $context) => $context['operation'] === $operationName)->times((int) SharepointConfigEnum::MAX_RETRIES->label());

            Log::shouldReceive('error')
                ->withArgs(fn ($message, $context) => $context['operation'] === $operationName)->once();

            expect(fn () => $method->invoke($this->service, $operation, $operationName))
                ->toThrow(Exception::class);
        });
    });

    describe('edge cases and boundary conditions', function (): void {
        test('handles zero retry configuration gracefully', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $attempts = 0;
            $operation = function () use (&$attempts): void {
                $attempts++;
                throw new Exception('Zero retry test');
            };

            Log::shouldReceive('error')->once();

            expect(fn () => $method->invoke($this->service, $operation, 'zero-retry-test', 0))
                ->toThrow(Exception::class)
                ->and($attempts)->toBe(1); // Only initial attempt
        });

        test('handles very large retry counts', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $attempts = 0;
            $operation = function () use (&$attempts) {
                $attempts++;
                if ($attempts === 2) {
                    return 'success-on-second-attempt';
                }
                throw new Exception('Large retry test');
            };

            Log::shouldReceive('info')->twice(); // 1 retry + 1 success

            $result = $method->invoke($this->service, $operation, 'large-retry-test', 1000);

            expect($result)->toBe('success-on-second-attempt')
                ->and($attempts)->toBe(2);
        });

        test('handles operations that return false or null', function (): void {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeWithRetry');

            $falseOperation = fn () => false;
            $nullOperation = fn () => null;

            expect($method->invoke($this->service, $falseOperation, 'false-test'))->toBeFalse()
                ->and($method->invoke($this->service, $nullOperation, 'null-test'))->toBeNull();
        });
    });

    describe('service method coverage', function (): void {
        test('createPublicPermission validates required parameters', function (): void {
            // Test parameter validation for createPublicPermission
            expect(fn () => $this->service->createPublicPermission(null, ''))
                ->toThrow(InvalidArgumentException::class, "Parameter 'driveItemId' cannot be empty");

            expect(fn () => $this->service->createPublicPermission('test-site', null))
                ->toThrow(TypeError::class);
        });

        test('logging methods work correctly', function (): void {
            $reflection = new ReflectionClass($this->service);

            $logInfoMethod = $reflection->getMethod('logInfo');

            $logErrorMethod = $reflection->getMethod('logError');

            Log::shouldReceive('info')->with('Test info message', [])->once();
            Log::shouldReceive('error')->with('Test error message', ['error' => 'context'])->once();

            $logInfoMethod->invoke($this->service, 'Test info message');
            $logErrorMethod->invoke($this->service, 'Test error message', ['error' => 'context']);
        });
    });
});
