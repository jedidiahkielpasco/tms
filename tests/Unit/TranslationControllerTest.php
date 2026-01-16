<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\TranslationController;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    protected static $translationAliasMock = null;
    protected static $tagAliasMock = null;

    protected function setUp(): void
    {
        parent::setUp();
        // Create alias mocks once per test class in setUp
        if (self::$translationAliasMock === null) {
            try {
                self::$translationAliasMock = Mockery::mock('alias:' . Translation::class);
            } catch (\Mockery\Exception\RuntimeException $e) {
                // Alias already exists - this means Mockery wasn't cleaned up properly
                // Force cleanup and recreate
                Mockery::close();
                self::$translationAliasMock = Mockery::mock('alias:' . Translation::class);
            }
        }
        if (self::$tagAliasMock === null) {
            try {
                self::$tagAliasMock = Mockery::mock('alias:' . Tag::class);
            } catch (\Mockery\Exception\RuntimeException $e) {
                Mockery::close();
                self::$tagAliasMock = Mockery::mock('alias:' . Tag::class);
            }
        }
    }

    protected function tearDown(): void
    {
        // Don't close Mockery here - let it persist for the test class
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        Mockery::close();
        self::$translationAliasMock = null;
        self::$tagAliasMock = null;
        parent::tearDownAfterClass();
    }

    /**
     * Get or create Translation alias mock (singleton pattern)
     */
    protected function getTranslationAliasMock()
    {
        if (self::$translationAliasMock === null) {
            try {
                self::$translationAliasMock = Mockery::mock('alias:' . Translation::class);
            } catch (\Mockery\Exception\RuntimeException $e) {
                // Alias already exists from previous test - this shouldn't happen with proper cleanup
                // but if it does, we'll try to get the existing mock
                throw $e;
            }
        }
        return self::$translationAliasMock;
    }

    /**
     * Get or create Tag alias mock (singleton pattern)
     */
    protected function getTagAliasMock()
    {
        if (self::$tagAliasMock === null) {
            try {
                self::$tagAliasMock = Mockery::mock('alias:' . Tag::class);
            } catch (\Mockery\Exception\RuntimeException $e) {
                throw $e;
            }
        }
        return self::$tagAliasMock;
    }

    public function test_index_returns_paginated_translations()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')
            ->with('tag')
            ->andReturn(false);
        $request->shouldReceive('has')
            ->with('key')
            ->andReturn(false);
        $request->shouldReceive('has')
            ->with('content')
            ->andReturn(false);
        $request->shouldReceive('has')
            ->with('locale')
            ->andReturn(false);
        $request->shouldReceive('all')->andReturn([]);
        $request->shouldReceive('input')->andReturn([]);
        $request->shouldReceive('get')->andReturn([]);

        $translation = Mockery::mock(Translation::class)->makePartial();
        $translation->shouldAllowMockingProtectedMethods();
        $translation->id = 1;
        $translation->locale = 'en';
        $translation->key = 'app.title';
        $translation->content = 'My App';
        $translation->shouldReceive('setAttribute')->andReturnSelf();
        $translation->shouldReceive('getAttribute')->andReturnUsing(function($key) use ($translation) {
            return $translation->$key ?? null;
        });
        
        $paginator = new LengthAwarePaginator(
            collect([$translation]),
            1,
            50,
            1
        );

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('paginate')
            ->with(50)
            ->once()
            ->andReturn($paginator);

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new TranslationController();
        $response = $controller->index($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $response);
        $this->assertEquals(1, $response->total());
    }

    public function test_index_filters_by_tag()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')
            ->with('tag')
            ->andReturn(true);
        $request->shouldReceive('has')
            ->with('key')
            ->andReturn(false);
        $request->shouldReceive('has')
            ->with('content')
            ->andReturn(false);
        $request->shouldReceive('has')
            ->with('locale')
            ->andReturn(false);
        
        $request->tag = 'mobile';
        $request->shouldReceive('__get')->with('tag')->andReturn('mobile');
        $request->shouldReceive('input')->with('tag')->andReturn('mobile');
        $request->shouldReceive('get')->with('tag')->andReturn('mobile');
        $request->shouldReceive('all')->andReturn(['tag' => 'mobile']);

        $tagQuery = Mockery::mock(Builder::class);
        $tagQuery->shouldReceive('where')
            ->with('name', 'mobile')
            ->once()
            ->andReturnSelf();

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereHas')
            ->with('tags', Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($relation, $callback) use ($tagQuery, $query) {
                $callback($tagQuery);
                return $query;
            });
        
        $query->shouldReceive('paginate')
            ->with(50)
            ->once()
            ->andReturn(new LengthAwarePaginator(collect([]), 0, 50, 1));

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new TranslationController();
        $response = $controller->index($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $response);
    }

    public function test_index_filters_by_key()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')
            ->with('tag')
            ->andReturn(false);
        $request->shouldReceive('has')
            ->with('key')
            ->andReturn(true);
        $request->shouldReceive('has')
            ->with('content')
            ->andReturn(false);
        $request->shouldReceive('has')
            ->with('locale')
            ->andReturn(false);
        
        $request->key = 'app';
        $request->shouldReceive('__get')->with('key')->andReturn('app');
        $request->shouldReceive('input')->with('key')->andReturn('app');
        $request->shouldReceive('get')->with('key')->andReturn('app');
        $request->shouldReceive('all')->andReturn(['key' => 'app']);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')
            ->with('key', 'like', '%app%')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('paginate')
            ->with(50)
            ->once()
            ->andReturn(new LengthAwarePaginator(collect([]), 0, 50, 1));

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('query')
            ->once()
            ->andReturn($query);

        $controller = new TranslationController();
        $response = $controller->index($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $response);
    }

    public function test_store_creates_translation_with_tags()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'locale' => 'en',
                'key' => 'app.title',
                'content' => 'My App',
                'tags' => ['mobile', 'web']
            ]);

        $translation = Mockery::mock(Translation::class)->makePartial();
        $translation->shouldAllowMockingProtectedMethods();
        $translation->id = 1;
        $translation->locale = 'en';
        $translation->key = 'app.title';
        $translation->content = 'My App';
        $translation->shouldReceive('setAttribute')->andReturnSelf();
        $translation->shouldReceive('getAttribute')->andReturnUsing(function($key) use ($translation) {
            return $translation->$key ?? null;
        });

        $tag1 = Mockery::mock(Tag::class);
        $tag1->id = 1;
        $tag1->name = 'mobile';
        
        $tag2 = Mockery::mock(Tag::class);
        $tag2->id = 2;
        $tag2->name = 'web';
        
        $tags = collect([$tag1, $tag2]);

        $relation = Mockery::mock();
        $relation->shouldReceive('sync')
            ->with($tags)
            ->once()
            ->andReturn([]);

        $translation->shouldReceive('tags')
            ->once()
            ->andReturn($relation);

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('create')
            ->with([
                'locale' => 'en',
                'key' => 'app.title',
                'content' => 'My App',
                'tags' => ['mobile', 'web']
            ])
            ->once()
            ->andReturn($translation);

        $tagQuery = Mockery::mock(Builder::class);
        $tagQuery->shouldReceive('get')
            ->once()
            ->andReturn($tags);

        $tagMock = $this->getTagAliasMock();
        $tagMock->shouldReceive('whereIn')
            ->with('name', ['mobile', 'web'])
            ->once()
            ->andReturn($tagQuery);
        $tagMock->shouldReceive('whereIn')
            ->with('name', ['mobile', 'web'])
            ->once()
            ->andReturn($tagQuery);

        $controller = new TranslationController();
        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_store_creates_translation_without_tags()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'locale' => 'en',
                'key' => 'app.title',
                'content' => 'My App'
            ]);

        $translation = Mockery::mock(Translation::class)->makePartial();
        $translation->shouldAllowMockingProtectedMethods();
        $translation->id = 1;
        $translation->locale = 'en';
        $translation->key = 'app.title';
        $translation->content = 'My App';
        $translation->shouldReceive('setAttribute')->andReturnSelf();
        $translation->shouldReceive('getAttribute')->andReturnUsing(function($key) use ($translation) {
            return $translation->$key ?? null;
        });

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('create')
            ->with([
                'locale' => 'en',
                'key' => 'app.title',
                'content' => 'My App'
            ])
            ->once()
            ->andReturn($translation);

        $controller = new TranslationController();
        $response = $controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_show_returns_translation_with_tags()
    {
        $translation = Mockery::mock(Translation::class)->makePartial();
        $translation->shouldAllowMockingProtectedMethods();
        $translation->id = 1;
        $translation->locale = 'en';
        $translation->key = 'app.title';
        $translation->content = 'My App';
        $translation->shouldReceive('setAttribute')->andReturnSelf();
        $translation->shouldReceive('getAttribute')->andReturnUsing(function($key) use ($translation) {
            return $translation->$key ?? null;
        });

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('with')
            ->with('tags')
            ->once()
            ->andReturnSelf();
        
        $query->shouldReceive('findOrFail')
            ->with(1)
            ->once()
            ->andReturn($translation);

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('with')
            ->with('tags')
            ->once()
            ->andReturn($query);

        $controller = new TranslationController();
        $response = $controller->show(1);

        $this->assertEquals($translation, $response);
    }

    public function test_update_modifies_translation_and_syncs_tags()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'content' => 'Updated Content',
                'tags' => ['mobile']
            ]);

        $translation = Mockery::mock(Translation::class)->makePartial();
        $translation->shouldAllowMockingProtectedMethods();
        $translation->id = 1;
        $translation->locale = 'en';
        $translation->key = 'app.title';
        $translation->content = 'Updated Content';
        $translation->shouldReceive('setAttribute')->zeroOrMoreTimes()->andReturnSelf();
        $translation->shouldReceive('getAttribute')->zeroOrMoreTimes()->andReturnUsing(function($key) use ($translation) {
            return $translation->$key ?? null;
        });

        $translation->shouldReceive('update')
            ->with([
                'content' => 'Updated Content',
                'tags' => ['mobile']
            ])
            ->once()
            ->andReturn(true);

        $tag1 = Mockery::mock(Tag::class);
        $tag1->id = 1;
        $tag1->name = 'mobile';
        $tags = collect([$tag1]);

        $relation = Mockery::mock();
        $relation->shouldReceive('sync')
            ->with($tags)
            ->once()
            ->andReturn([]);

        $translation->shouldReceive('tags')
            ->once()
            ->andReturn($relation);

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('findOrFail')
            ->with(1)
            ->once()
            ->andReturn($translation);

        $tagQuery = Mockery::mock(Builder::class);
        $tagQuery->shouldReceive('get')
            ->once()
            ->andReturn($tags);

        $tagMock = $this->getTagAliasMock();
        $tagMock->shouldReceive('whereIn')
            ->with('name', ['mobile', 'web'])
            ->once()
            ->andReturn($tagQuery);
        $tagMock->shouldReceive('whereIn')
            ->with('name', ['mobile'])
            ->once()
            ->andReturn($tagQuery);

        $controller = new TranslationController();
        $response = $controller->update($request, 1);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_update_modifies_translation_without_tags()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')
            ->once()
            ->andReturn([
                'content' => 'Updated Content'
            ]);

        $translation = Mockery::mock(Translation::class)->makePartial();
        $translation->shouldAllowMockingProtectedMethods();
        $translation->id = 1;
        $translation->locale = 'en';
        $translation->key = 'app.title';
        $translation->content = 'Updated Content';
        $translation->shouldReceive('setAttribute')->zeroOrMoreTimes()->andReturnSelf();
        $translation->shouldReceive('getAttribute')->zeroOrMoreTimes()->andReturnUsing(function($key) use ($translation) {
            return $translation->$key ?? null;
        });

        $translation->shouldReceive('update')
            ->with([
                'content' => 'Updated Content'
            ])
            ->once()
            ->andReturn(true);

        // Use singleton pattern to avoid creating multiple alias mocks
        $translationMock = $this->getTranslationAliasMock();
        $translationMock->shouldReceive('findOrFail')
            ->with(1)
            ->once()
            ->andReturn($translation);

        $controller = new TranslationController();
        $response = $controller->update($request, 1);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
