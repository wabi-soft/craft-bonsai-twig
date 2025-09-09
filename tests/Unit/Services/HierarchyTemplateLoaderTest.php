<?php

namespace wabisoft\bonsaitwig\tests\Unit\Services;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\services\CacheService;
use wabisoft\bonsaitwig\services\PerformanceMonitor;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use wabisoft\bonsaitwig\valueobjects\DebugInfo;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\enums\DebugMode;
use wabisoft\bonsaitwig\exceptions\TemplateNotFoundException;
use craft\web\View;
use Mockery;

/**
 * Unit tests for HierarchyTemplateLoader service.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\Services
 * @since 6.4.0
 */
class HierarchyTemplateLoaderTest extends TestCase
{
    private HierarchyTemplateLoader $hierarchyLoader;
    private $mockView;
    private $mockCacheService;
    private $mockPerformanceMonitor;

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

    public function testLoadWithExistingTemplate(): void
    {
        $entry = $this->createMockEntry();
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $templatePaths = ['entry/test/_entry'];
        $expectedOutput = '<div>Template content</div>';
        
        // Mock cache miss
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        // Mock template exists and render
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->with($templatePaths[0])
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->with($templatePaths[0], Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        // Mock cache set
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        // Mock performance monitoring
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_id');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->once()
            ->with('timer_id')
            ->andReturn(0.001);
        
        $result = $this->hierarchyLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testLoadThrowsExceptionWhenNoTemplateFound(): void
    {
        $entry = $this->createMockEntry();
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        // Mock cache miss
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        // Mock no templates exist
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->andReturn(false);
        
        // Mock performance monitoring
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_id');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->once()
            ->with('timer_id')
            ->andReturn(0.001);
        
        $this->expectException(TemplateNotFoundException::class);
        
        $this->hierarchyLoader->load($context);
    }

    public function testLoadWithCacheHit(): void
    {
        $entry = $this->createMockEntry();
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $cachedOutput = '<div>Cached content</div>';
        
        // Mock cache hit
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn($cachedOutput);
        
        // Mock performance monitoring
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_id');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->once()
            ->with('timer_id')
            ->andReturn(0.0001);
        
        $result = $this->hierarchyLoader->load($context);
        
        $this->assertEquals($cachedOutput, $result);
    }

    public function testLoadWithDebugMode(): void
    {
        $entry = $this->createMockEntry();
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            showDebug: true
        );
        
        $templateContent = '<div>Template content</div>';
        $debugContent = '<div>Debug info</div>';
        $expectedOutput = $templateContent . $debugContent;
        
        // Mock cache miss
        $this->mockCacheService
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        // Mock template exists and render
        $this->mockView
            ->shouldReceive('doesTemplateExist')
            ->andReturn(true);
        
        $this->mockView
            ->shouldReceive('renderTemplate')
            ->twice() // Once for main template, once for debug
            ->andReturn($templateContent, $debugContent);
        
        // Mock cache set
        $this->mockCacheService
            ->shouldReceive('set')
            ->once();
        
        // Mock performance monitoring
        $this->mockPerformanceMonitor
            ->shouldReceive('startTiming')
            ->once()
            ->andReturn('timer_id');
        
        $this->mockPerformanceMonitor
            ->shouldReceive('endTiming')
            ->once()
            ->with('timer_id')
            ->andReturn(0.002);
        
        $result = $this->hierarchyLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testGenerateTemplatePaths(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article',
            'slug' => 'my-post'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $paths = $this->hierarchyLoader->generateTemplatePaths($context, TemplateType::ENTRY);
        
        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);
        
        // Should contain hierarchical paths
        $expectedPaths = [
            'entry/blog/article/my-post',
            'entry/blog/article/_entry',
            'entry/blog/_entry',
            'entry/_entry'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testCreateDebugInfo(): void
    {
        $templatePaths = [
            'entry/blog/article/_entry',
            'entry/blog/_entry',
            'entry/_entry'
        ];
        
        $debugInfo = $this->hierarchyLoader->createDebugInfo(
            'entry',
            $templatePaths,
            'entry/blog/article/_entry',
            TemplateType::ENTRY,
            0.001
        );
        
        $this->assertInstanceOf(DebugInfo::class, $debugInfo);
        $this->assertEquals('entry', $debugInfo->directory);
        $this->assertEquals($templatePaths, $debugInfo->templates);
        $this->assertEquals('entry/blog/article/_entry', $debugInfo->currentTemplate);
        $this->assertEquals(TemplateType::ENTRY, $debugInfo->type);
        $this->assertEquals(0.001, $debugInfo->resolutionTime);
    }

    public function testSanitizePath(): void
    {
        // Test path traversal prevention
        $maliciousPath = '../../../etc/passwd';
        $sanitized = $this->hierarchyLoader->sanitizePath($maliciousPath);
        
        $this->assertStringNotContainsString('..', $sanitized);
        $this->assertEquals('etc/passwd', $sanitized);
    }

    public function testGenerateCacheKey(): void
    {
        $entry = $this->createMockEntry([
            'id' => 123,
            'sectionHandle' => 'blog'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $cacheKey = $this->hierarchyLoader->generateCacheKey($context);
        
        $this->assertIsString($cacheKey);
        $this->assertNotEmpty($cacheKey);
        
        // Should be consistent for same context
        $cacheKey2 = $this->hierarchyLoader->generateCacheKey($context);
        $this->assertEquals($cacheKey, $cacheKey2);
    }
}