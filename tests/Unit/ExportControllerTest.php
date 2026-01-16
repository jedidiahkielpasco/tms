<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ExportController;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_export_returns_translations_for_locale()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn(['locale' => 'en']);
        
        $request->shouldReceive('has')
            ->with('tags')
            ->andReturn(false);
        
        $request->shouldReceive('fullUrl')
            ->andReturn('http://example.com/api/export?locale=en');
        
        $request->shouldReceive('header')
            ->with('If-None-Match')
            ->andReturn(null);
        
        $request->shouldReceive('header')
            ->with('If-Modified-Since')
            ->andReturn(null);
        
        // Mock property access for locale and other Request methods
        $request->shouldReceive('__get')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('input')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('get')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('all')
            ->andReturn(['locale' => 'en']);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->with('locale', 'en')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('select')
            ->with('key', 'content')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('max')
            ->with('updated_at')
            ->once()
            ->andReturn('2024-01-01 12:00:00');
        
        $query->shouldReceive('pluck')
            ->with('content', 'key')
            ->once()
            ->andReturn(collect(['app.title' => 'My App', 'button.save' => 'Save']));

        // Mock Translation::query() using alias
        // Note: alias creates a class alias that is cleaned up with Mockery::close()
        // We create it fresh for each test to avoid conflicts
        $translationMock = Mockery::mock('alias:' . Translation::class);
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new ExportController();
        $response = $controller($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function test_export_filters_by_tags_when_provided()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn(['locale' => 'en']);
        
        $request->shouldReceive('has')
            ->with('tags')
            ->andReturn(true);
        
        $request->shouldReceive('fullUrl')
            ->andReturn('http://example.com/api/export?locale=en&tags=mobile,web');
        
        $request->shouldReceive('header')
            ->with('If-None-Match')
            ->andReturn(null);
        
        $request->shouldReceive('header')
            ->with('If-Modified-Since')
            ->andReturn(null);
        
        // Mock property access and methods
        $request->shouldReceive('__get')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('__get')
            ->with('tags')
            ->andReturn('mobile,web');
        $request->shouldReceive('input')
            ->andReturn(['locale' => 'en', 'tags' => 'mobile,web']);
        $request->shouldReceive('get')
            ->andReturn(['locale' => 'en', 'tags' => 'mobile,web']);
        $request->shouldReceive('all')
            ->andReturn(['locale' => 'en', 'tags' => 'mobile,web']);
        
        $request->tags = 'mobile,web';

        $tagQuery = Mockery::mock(Builder::class);
        $tagQuery->shouldReceive('whereIn')
            ->with('name', ['mobile', 'web'])
            ->once()
            ->andReturnSelf();

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->with('locale', 'en')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('select')
            ->with('key', 'content')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('whereHas')
            ->with('tags', Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($relation, $callback) use ($tagQuery, $query) {
                $callback($tagQuery);
                return $query;
            });
        
        $query->shouldReceive('max')
            ->with('updated_at')
            ->once()
            ->andReturn('2024-01-01 12:00:00');
        
        $query->shouldReceive('pluck')
            ->with('content', 'key')
            ->once()
            ->andReturn(collect(['app.title' => 'My App']));

        // Mock Translation::query() using alias
        // Note: alias creates a class alias that is cleaned up with Mockery::close()
        // We create it fresh for each test to avoid conflicts
        $translationMock = Mockery::mock('alias:' . Translation::class);
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new ExportController();
        $response = $controller($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_export_returns_304_when_etag_matches()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn(['locale' => 'en']);
        
        $request->shouldReceive('has')
            ->with('tags')
            ->andReturn(false);
        
        $request->shouldReceive('fullUrl')
            ->andReturn('http://example.com/api/export?locale=en');
        
        // Mock property access and methods
        $request->shouldReceive('__get')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('input')
            ->andReturn(['locale' => 'en']);
        $request->shouldReceive('get')
            ->andReturn(['locale' => 'en']);
        $request->shouldReceive('all')
            ->andReturn(['locale' => 'en']);
        
        $lastModified = '2024-01-01 12:00:00';
        $timestamp = \Carbon\Carbon::parse($lastModified)->timestamp;
        $expectedEtag = md5('http://example.com/api/export?locale=en' . $timestamp);
        
        $request->shouldReceive('header')
            ->with('If-None-Match')
            ->andReturn('"' . $expectedEtag . '"');
        
        $request->shouldReceive('header')
            ->with('If-Modified-Since')
            ->andReturn(null);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->with('locale', 'en')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('select')
            ->with('key', 'content')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('max')
            ->with('updated_at')
            ->once()
            ->andReturn($lastModified);

        // Mock Translation::query() using alias
        // Note: alias creates a class alias that is cleaned up with Mockery::close()
        // We create it fresh for each test to avoid conflicts
        $translationMock = Mockery::mock('alias:' . Translation::class);
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new ExportController();
        $response = $controller($request);

        $this->assertEquals(304, $response->getStatusCode());
    }

    public function test_export_handles_empty_results()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn(['locale' => 'fr']);
        
        $request->shouldReceive('has')
            ->with('tags')
            ->andReturn(false);
        
        $request->shouldReceive('fullUrl')
            ->andReturn('http://example.com/api/export?locale=fr');
        
        $request->shouldReceive('header')
            ->with('If-None-Match')
            ->andReturn(null);
        
        $request->shouldReceive('header')
            ->with('If-Modified-Since')
            ->andReturn(null);
        
        // Mock property access and methods
        $request->shouldReceive('__get')
            ->with('locale')
            ->andReturn('fr');
        $request->shouldReceive('input')
            ->andReturn(['locale' => 'fr']);
        $request->shouldReceive('get')
            ->andReturn(['locale' => 'fr']);
        $request->shouldReceive('all')
            ->andReturn(['locale' => 'fr']);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->with('locale', 'fr')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('select')
            ->with('key', 'content')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('max')
            ->with('updated_at')
            ->once()
            ->andReturn(null);
        
        $query->shouldReceive('pluck')
            ->with('content', 'key')
            ->once()
            ->andReturn(collect([]));

        // Mock Translation::query() using alias
        // Note: alias creates a class alias that is cleaned up with Mockery::close()
        // We create it fresh for each test to avoid conflicts
        $translationMock = Mockery::mock('alias:' . Translation::class);
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new ExportController();
        $response = $controller($request);

        $this->assertEquals(200, $response->getStatusCode());
        // Empty collection returns [] in JSON, not {}
        $this->assertEquals('[]', $response->getContent());
    }

    public function test_export_sets_cache_headers()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn(['locale' => 'en']);
        
        $request->shouldReceive('has')
            ->with('tags')
            ->andReturn(false);
        
        $request->shouldReceive('fullUrl')
            ->andReturn('http://example.com/api/export?locale=en');
        
        $request->shouldReceive('header')
            ->with('If-None-Match')
            ->andReturn(null);
        
        $request->shouldReceive('header')
            ->with('If-Modified-Since')
            ->andReturn(null);
        
        // Mock property access for locale and other Request methods
        $request->shouldReceive('__get')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('input')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('get')
            ->with('locale')
            ->andReturn('en');
        $request->shouldReceive('all')
            ->andReturn(['locale' => 'en']);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->with('locale', 'en')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('select')
            ->with('key', 'content')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('max')
            ->with('updated_at')
            ->once()
            ->andReturn('2024-01-01 12:00:00');
        
        $query->shouldReceive('pluck')
            ->with('content', 'key')
            ->once()
            ->andReturn(collect(['app.title' => 'My App']));

        // Mock Translation::query() using alias
        // Note: alias creates a class alias that is cleaned up with Mockery::close()
        // We create it fresh for each test to avoid conflicts
        $translationMock = Mockery::mock('alias:' . Translation::class);
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new ExportController();
        $response = $controller($request);

        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('max-age=60', $response->headers->get('Cache-Control'));
    }
}
