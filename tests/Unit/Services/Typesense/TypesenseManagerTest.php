<?php

namespace Tests\Unit\Services\Typesense;

use App\Services\Typesense\TypesenseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;
use Typesense\Client;
use Typesense\Keys;
use PHPUnit\Framework\Attributes\Test;

class TypesenseManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any cached API keys before tests
        Cache::forget('typesense_search_only_key');
        
        // Configure test environment
        Config::set('scout.typesense.client-settings', [
            'api_key' => 'test_admin_key',
            'nodes' => [
                [
                    'host' => 'test.example.com',
                    'port' => 8108,
                    'protocol' => 'https',
                    'path' => '/api',
                ]
            ],
        ]);
    }

    #[Test]
    public function it_returns_frontend_config_with_search_only_key()
    {
        // Set an env key to use directly
        $this->app['config']->set('app.env', 'testing');
        putenv('TYPESENSE_SEARCH_ONLY_KEY=test_search_only_key');
        
        $config = TypesenseManager::getFrontendConfig();
        
        $this->assertArrayHasKey('apiKey', $config);
        $this->assertEquals('test_search_only_key', $config['apiKey']);
        $this->assertArrayHasKey('nodes', $config);
        $this->assertArrayHasKey('searchParams', $config);
        $this->assertEquals('test.example.com', $config['nodes'][0]['host']);
        
        // Reset environment
        putenv('TYPESENSE_SEARCH_ONLY_KEY');
    }
    
    #[Test]
    public function it_creates_search_only_key_when_env_key_not_provided()
    {
        // Remove any env keys
        putenv('TYPESENSE_SEARCH_ONLY_KEY');
        
        // Mock the Typesense client and keys property
        $keysMock = Mockery::mock(Keys::class);
        $keysMock->shouldReceive('create')
            ->once()
            ->andReturn(['value' => 'generated_search_only_key']);
            
        $clientMock = $this->createPartialMock(Client::class, []);
        $clientMock->keys = $keysMock;
        
        $this->app->instance(Client::class, $clientMock);
        
        $config = TypesenseManager::getFrontendConfig();
        
        $this->assertEquals('generated_search_only_key', $config['apiKey']);
        $this->assertTrue(Cache::has('typesense_search_only_key'));
    }
    
    #[Test]
    public function it_uses_cached_key_when_available()
    {
        // Set up a cached key
        Cache::put('typesense_search_only_key', 'cached_search_key', now()->addHours(1));
        
        // No need to mock the client as the cached key should be used
        $config = TypesenseManager::getFrontendConfig();
        
        $this->assertEquals('cached_search_key', $config['apiKey']);
    }
    
    #[Test]
    public function it_handles_key_creation_exceptions()
    {
        // Remove any env keys and cached keys
        putenv('TYPESENSE_SEARCH_ONLY_KEY');
        Cache::forget('typesense_search_only_key');
        
        // Set a fallback key
        putenv('TYPESENSE_SEARCH_ONLY_KEY_FALLBACK=fallback_search_key');
        
        // Mock the client to throw an exception
        $keysMock = Mockery::mock(Keys::class);
        $keysMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Connection refused'));
        
        $clientMock = $this->createPartialMock(Client::class, []);
        $clientMock->keys = $keysMock;
        
        $this->app->instance(Client::class, $clientMock);
        
        $config = TypesenseManager::getFrontendConfig();
        
        $this->assertEquals('fallback_search_key', $config['apiKey']);
        
        // Reset environment
        putenv('TYPESENSE_SEARCH_ONLY_KEY_FALLBACK');
    }
}