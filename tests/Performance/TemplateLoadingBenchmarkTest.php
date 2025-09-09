<?php

namespace wabisoft\bonsaitwig\tests\Performance;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\services\CacheService;
use wabisoft\bonsaitwig\services\PerformanceMonitor;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use craft\web\View;
use Mockery;

/**
 * Performance benchmark tests for template loading optimization validation.
 *
 * These tests measure performance characteristics and ensure that
 * optimizations maintain acceptable performance levels.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Performance
 * @since 6.4.0
 */
class TemplateLoadingBenchmarkTest extends TestCase
{
    private const MAX_TEMPLATE_RESOLUTION_TIME = 0.010; // 10ms
    private const MAX_CACHED_RESOLUTION_TIME = 0.001;   // 1ms
    private const MAX_BATCH_RESOLUTION_TIME = 0.050;    // 50ms for 10 templates
    
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

    public function testSingleEntryTemplateResolutionPerformance(): void
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
        
        // Mock performance monitoring to return actual timing
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_1');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_1')
            ->andReturn(0.005); // 5ms - within acceptable range
        
        // Mock cache miss and template resolution
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->andReturn('<div>Content</div>');
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $startTime = microtime(true);
        $result = $entryLoader->load($context);
        $endTime = microtime(true);
        
        $actualTime = $endTime - $startTime;
        
        $this->assertPerformanceWithinBounds($actualTime, self::MAX_TEMPLATE_RESOLUTION_TIME);
        $this->assertNotEmpty($result);
    }

    public function testCachedTemplateResolutionPerformance(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        $entry = $this->createMockEntry();
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $cachedOutput = '<div>Cached content</div>';
        
        // Mock performance monitoring for cache hit
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_2');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_2')
            ->andReturn(0.0005); // 0.5ms - very fast cache hit
        
        // Mock cache hit
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn($cachedOutput);
        
        $startTime = microtime(true);
        $result = $entryLoader->load($context);
        $endTime = microtime(true);
        
        $actualTime = $endTime - $startTime;
        
        $this->assertPerformanceWithinBounds($actualTime, self::MAX_CACHED_RESOLUTION_TIME);
        $this->assertEquals($cachedOutput, $result);
    }

    public function testBatchTemplateResolutionPerformance(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        // Create multiple entries for batch testing
        $entries = [];
        $contexts = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $entries[] = $this->createMockEntry([
                'id' => $i,
                'sectionHandle' => 'blog',
                'typeHandle' => 'article',
                'slug' => "post-{$i}"
            ]);
            
            $contexts[] = new TemplateContext(
                element: $entries[$i - 1],
                path: 'entry'
            );
        }
        
        // Mock performance monitoring for each template
        for ($i = 1; $i <= 10; $i++) {
            $this->mockPerformanceMonitor
                ->shouldReceive('startTiming')
                ->once()
                ->andReturn("timer_{$i}");
            
            $this->mockPerformanceMonitor
                ->shouldReceive('endTiming')
                ->with("timer_{$i}")
                ->andReturn(0.003); // 3ms per template
        }
        
        // Mock cache misses and template resolution for all
        $this->mockCacheService
            ->shouldReceive('get')
            ->times(10)
            ->andReturn(null);
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->times(10)
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->times(10)
            ->andReturn('<div>Content</div>');
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->times(10);
        
        $startTime = microtime(true);
        
        $results = [];
        foreach ($contexts as $context) {
            $results[] = $entryLoader->load($context);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        $this->assertPerformanceWithinBounds($totalTime, self::MAX_BATCH_RESOLUTION_TIME);
        $this->assertCount(10, $results);
        
        foreach ($results as $result) {
            $this->assertNotEmpty($result);
        }
    }

    public function testMatrixTemplateResolutionPerformance(): void
    {
        $matrixLoader = new MatrixLoader($this->hierarchyLoader);
        
        $ownerEntry = $this->createMockEntry([
            'sectionHandle' => 'pages',
            'typeHandle' => 'page'
        ]);
        
        $matrixBlock = $this->createMockMatrixBlock([
            'typeHandle' => 'complexBlock',
            'owner' => $ownerEntry
        ]);
        
        $context = new TemplateContext(
            element: $matrixBlock,
            path: 'matrix',
            style: 'featured'
        );
        
        // Mock performance monitoring
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_matrix');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_matrix')
            ->andReturn(0.007); // 7ms - acceptable for complex matrix resolution
        
        // Mock cache miss and template resolution
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->andReturn('<div class="matrix-block">Complex content</div>');
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $startTime = microtime(true);
        $result = $matrixLoader->load($context);
        $endTime = microtime(true);
        
        $actualTime = $endTime - $startTime;
        
        $this->assertPerformanceWithinBounds($actualTime, self::MAX_TEMPLATE_RESOLUTION_TIME);
        $this->assertNotEmpty($result);
    }

    public function testTemplatePathGenerationPerformance(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article',
            'slug' => 'very-long-slug-name-for-testing-performance'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $startTime = microtime(true);
        
        // Generate paths multiple times to test performance
        for ($i = 0; $i < 100; $i++) {
            $paths = $this->hierarchyLoader->generateTemplatePaths($context, \wabisoft\bonsaitwig\enums\TemplateType::ENTRY);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Path generation should be very fast - 100 iterations in under 10ms
        $this->assertPerformanceWithinBounds($totalTime, 0.010);
        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);
    }

    public function testMemoryUsageOptimization(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        $initialMemory = memory_get_usage(true);
        
        // Create and process multiple templates
        for ($i = 1; $i <= 50; $i++) {
            $entry = $this->createMockEntry([
                'id' => $i,
                'sectionHandle' => 'blog',
                'typeHandle' => 'article'
            ]);
            
            $context = new TemplateContext(
                element: $entry,
                path: 'entry'
            );
            
            // Mock minimal setup for memory testing
            $this->mockPerformanceMonitor
                ->shouldReceive('startTiming')
                ->andReturn("timer_{$i}");
            
            $this->mockPerformanceMonitor
                ->shouldReceive('endTiming')
                ->andReturn(0.001);
            
            $this->mockCacheService
                ->shouldReceive('get')
                ->andReturn('<div>Cached</div>');
            
            $entryLoader->load($context);
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 1MB for 50 templates)
        $maxMemoryIncrease = 1024 * 1024; // 1MB
        
        $this->assertLessThan(
            $maxMemoryIncrease,
            $memoryIncrease,
            "Memory usage increased by {$memoryIncrease} bytes, should be less than {$maxMemoryIncrease}"
        );
    }

    public function testCacheEfficiencyBenchmark(): void
    {
        $entryLoader = new EntryLoader($this->hierarchyLoader);
        
        $entry = $this->createMockEntry();
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        // First call - cache miss (should be slower)
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_miss');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_miss')
            ->andReturn(0.008); // 8ms for cache miss
        
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->once()
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->once()
            ->andReturn('<div>Content</div>');
        
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        $startTime1 = microtime(true);
        $result1 = $entryLoader->load($context);
        $endTime1 = microtime(true);
        $cacheMissTime = $endTime1 - $startTime1;
        
        // Second call - cache hit (should be faster)
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_hit');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->with('timer_hit')
            ->andReturn(0.0005); // 0.5ms for cache hit
        
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn('<div>Content</div>');
        
        $startTime2 = microtime(true);
        $result2 = $entryLoader->load($context);
        $endTime2 = microtime(true);
        $cacheHitTime = $endTime2 - $startTime2;
        
        // Cache hit should be significantly faster than cache miss
        $this->assertLessThan($cacheMissTime, $cacheHitTime);
        $this->assertEquals($result1, $result2);
        
        // Both should be within acceptable bounds
        $this->assertPerformanceWithinBounds($cacheMissTime, self::MAX_TEMPLATE_RESOLUTION_TIME);
        $this->assertPerformanceWithinBounds($cacheHitTime, self::MAX_CACHED_RESOLUTION_TIME);
    }

    /**
     * Helper method to assert performance is within acceptable bounds.
     */
    private function assertPerformanceWithinBounds(float $actualTime, float $maxTime): void
    {
        $this->assertLessThanOrEqual(
            $maxTime,
            $actualTime,
            "Performance exceeded acceptable bounds. Expected <= {$maxTime}s, got {$actualTime}s"
        );
    }
}