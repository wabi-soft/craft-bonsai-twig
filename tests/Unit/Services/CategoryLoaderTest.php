<?php

namespace wabisoft\bonsaitwig\tests\Unit\Services;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;

use craft\elements\Category;
use craft\elements\Entry;
use Mockery;

/**
 * Unit tests for CategoryLoader service.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\Services
 * @since 6.4.0
 */
class CategoryLoaderTest extends TestCase
{
    private CategoryLoader $categoryLoader;
    private $mockHierarchyLoader;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHierarchyLoader = Mockery::mock(HierarchyTemplateLoader::class);
        $this->categoryLoader = new CategoryLoader($this->mockHierarchyLoader);
    }

    public function testLoadWithValidCategory(): void
    {
        $category = $this->createMockCategory([
            'groupHandle' => 'topics'
        ]);
        
        $context = new TemplateContext(
            element: $category,
            path: 'category'
        );
        
        $expectedOutput = '<div>Category content</div>';
        
        $this->mockHierarchyLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type(TemplateContext::class))
            ->andReturn($expectedOutput);
        
        $result = $this->categoryLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testLoadThrowsExceptionForInvalidElement(): void
    {
        $entry = $this->createMockEntry();
        
        $context = new TemplateContext(
            element: $entry,
            path: 'category'
        );
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected Category element');
        
        $this->categoryLoader->load($context);
    }

    public function testGenerateTemplatePathsWithBasicCategory(): void
    {
        $category = $this->createMockCategory([
            'groupHandle' => 'topics',
            'slug' => 'technology'
        ]);
        
        $context = new TemplateContext(
            element: $category,
            path: 'category'
        );
        
        $paths = $this->categoryLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'category/topics/technology',
            'category/topics/_category',
            'category/_category'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testGenerateTemplatePathsWithSiteHandle(): void
    {
        $category = $this->createMockCategory([
            'groupHandle' => 'topics',
            'siteHandle' => 'fr'
        ]);
        
        $context = new TemplateContext(
            element: $category,
            path: 'category',
            baseSite: 'fr'
        );
        
        $paths = $this->categoryLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'category/fr/topics/_category',
            'category/topics/_category'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testValidateElementWithValidCategory(): void
    {
        $category = $this->createMockCategory();
        
        $result = $this->categoryLoader->validateElement($category);
        
        $this->assertTrue($result);
    }

    public function testValidateElementWithInvalidElement(): void
    {
        $entry = $this->createMockEntry();
        
        $result = $this->categoryLoader->validateElement($entry);
        
        $this->assertFalse($result);
    }

    public function testGenerateTemplatePathsWithNullSafeOperators(): void
    {
        // Test null-safe operator usage for optional properties
        $category = Mockery::mock(Category::class);
        $category->shouldReceive('getId')->andReturn(1);
        $category->shouldReceive('getSlug')->andReturn('test-category');
        
        // Mock group that might be null
        $category->shouldReceive('getGroup')->andReturn(null);
        
        $context = new TemplateContext(
            element: $category,
            path: 'category'
        );
        
        $paths = $this->categoryLoader->generateTemplatePaths($context);
        
        // Should handle null group gracefully
        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);
        $this->assertContains('category/_category', $paths);
    }
}