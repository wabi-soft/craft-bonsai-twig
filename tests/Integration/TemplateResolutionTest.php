<?php

namespace wabisoft\bonsaitwig\tests\Integration;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\services\CacheService;
use wabisoft\bonsaitwig\services\PerformanceMonitor;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use wabisoft\bonsaitwig\enums\TemplateType;
use craft\web\View;
use Mockery;

/**
 * Integration tests for template resolution workflows.
 *
 * Tests the complete template resolution process from loader services
 * through the hierarchy template loader to final output.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Integration
 * @since 6.4.0
 */
class TemplateResolutionTest extends TestCase
{
    private $mockView;
    private $mockCacheService;
    private $mockPerformanceMonitor;
    private HierarchyTemplateLoader $hierarchyLoader;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockView = Mockery::mock(View::class);
        $this->mockCacheService = Mockery::mock(CacheService::class);
        $this->mockPerformanceMonitor = Mockery::mock(PerformanceMonitor::class);
        
        $this->hierarchyLoader = new HierarchyTemplateLoader(
            $this->mockView,
            $this->mockCacheService,
            $this->mockPerformanceMonitor
        );
    }

    public function testCompleteEntryTemplateResolution(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article',
            'slug' => 'my-awesome-post'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $expectedOutput = '<article><h1>My Awesome Post</h1></article>';
        
        // Mock the complete resolution workflow
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_1');
        
        // Mock template resolution - first few don't exist, then one does
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('entry/blog/article/my-awesome-post')
            ->andReturn(false);
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('entry/blog/article/_entry')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('entry/blog/article/_entry', Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_1')
            ->andReturn(0.003);
        
        $result = $entryLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testCompleteCategoryTemplateResolution(): void
    {
        $categoryLoader = new CategoryLoader($this->hierarchyLoader);
        
        $category = $this->createMockCategory([
            'groupHandle' => 'topics',
            'slug' => 'technology'
        ]);
        
        $context = new TemplateContext(
            element: $category,
            path: 'category'
        );
        
        $expectedOutput = '<div class="category"><h2>Technology</h2></div>';
        
        // Mock the complete resolution workflow
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_2');
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('category/topics/technology')
            ->andReturn(false);
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('category/topics/_category')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('category/topics/_category', Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_2')
            ->andReturn(0.002);
        
        $result = $categoryLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testCompleteMatrixTemplateResolutionWithStyle(): void
    {
        $matrixLoader = new MatrixLoader($this->hierarchyLoader);
        
        $ownerEntry = $this->createMockEntry([
            'sectionHandle' => 'pages',
            'typeHandle' => 'page'
        ]);
        
        $matrixBlock = $this->createMockMatrixBlock([
            'typeHandle' => 'textBlock',
            'owner' => $ownerEntry
        ]);
        
        $context = new TemplateContext(
            element: $matrixBlock,
            path: 'matrix',
            style: 'highlighted'
        );
        
        $expectedOutput = '<div class="text-block highlighted">Content</div>';
        
        // Mock the complete resolution workflow
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_3');
        
        // Mock style-specific template resolution
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('matrix/pages/page/textBlock/highlighted')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('matrix/pages/page/textBlock/highlighted', Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_3')
            ->andReturn(0.001);
        
        $result = $matrixLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testTemplateResolutionWithDebugMode(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            showDebug: true
        );
        
        $templateOutput = '<article>Content</article>';
        $debugOutput = '<div class="debug">Debug info</div>';
        $expectedOutput = $templateOutput . $debugOutput;
        
        // Mock the complete resolution workflow with debug
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_4');
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('entry/blog/article/_entry')
            ->andReturn(true);
        
        // First render for main template
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('entry/blog/article/_entry', Mockery::type('array'))
            ->andReturn($templateOutput);
        
        // Second render for debug template
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('_bonsai-twig/_partials/infobar', Mockery::type('array'))
            ->andReturn($debugOutput);
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_4')
            ->andReturn(0.005);
        
        $result = $entryLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testTemplateResolutionWithCaching(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $cachedOutput = '<article>Cached content</article>';
        
        // Mock cache hit scenario
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn($cachedOutput);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_5');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_5')
            ->andReturn(0.0001); // Very fast due to cache hit
        
        // Should not call template rendering when cached
        $this->mockView
            ->shouldNotReceive('doesTemplateExist');
        
        $this->mockView
            ->shouldNotReceive('renderTemplate');
        
        $result = $entryLoader->load($context);
        
        $this->assertEquals($cachedOutput, $result);
    }

    public function testMultiSiteTemplateResolution(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article',
            'siteHandle' => 'fr'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            baseSite: 'fr'
        );
        
        $expectedOutput = '<article lang="fr">Contenu français</article>';
        
        // Mock the complete resolution workflow
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_6');
        
        // Should try site-specific template first
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('entry/fr/blog/article/_entry')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('entry/fr/blog/article/_entry', Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_6')
            ->andReturn(0.002);
        
        $result = $entryLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testItemLoaderWithBothEntryAndCategory(): void
    {
        $itemLoader = new ItemLoader($this->hierarchyLoader);
        
        // Test with Entry
        $entry = $this->createMockEntry([
            'sectionHandle' => 'products',
            'typeHandle' => 'product'
        ]);
        
        $entryContext = new TemplateContext(
            element: $entry,
            path: 'item'
        );
        
        $entryOutput = '<div class="product-item">Product</div>';
        
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_7');
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('item/products/product/_item')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('item/products/product/_item', Mockery::type('array'))
            ->andReturn($entryOutput);
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_7')
            ->andReturn(0.002);
        
        $result = $itemLoader->load($entryContext);
        $this->assertEquals($entryOutput, $result);
        
        // Test with Category
        $category = $this->createMockCategory([
            'groupHandle' => 'productCategories'
        ]);
        
        $categoryContext = new TemplateContext(
            element: $category,
            path: 'item'
        );
        
        $categoryOutput = '<div class="category-item">Category</div>';
        
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_8');
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with('item/productCategories/_item')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with('item/productCategories/_item', Mockery::type('array'))
            ->andReturn($categoryOutput);
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_8')
            ->andReturn(0.001);
        
        $result = $itemLoader->load($categoryContext);
        $this->assertEquals($categoryOutput, $result);
    }
}