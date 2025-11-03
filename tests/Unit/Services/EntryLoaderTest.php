<?php

namespace wabisoft\bonsaitwig\tests\Unit\Services;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use wabisoft\bonsaitwig\enums\TemplateType;

use craft\elements\Entry;
use craft\elements\Category;
use Mockery;

/**
 * Unit tests for EntryLoader service.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\Services
 * @since 6.4.0
 */
class EntryLoaderTest extends TestCase
{
    private EntryLoader $entryLoader;
    private $mockHierarchyLoader;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHierarchyLoader = Mockery::mock(HierarchyTemplateLoader::class);
        $this->entryLoader = new EntryLoader($this->mockHierarchyLoader);
    }

    public function testLoadWithValidEntry(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $expectedOutput = '<div>Entry content</div>';
        
        $this->mockHierarchyLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type(TemplateContext::class))
            ->andReturn($expectedOutput);
        
        $result = $this->entryLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testLoadThrowsExceptionForInvalidElement(): void
    {
        $category = $this->createMockCategory();
        
        $context = new TemplateContext(
            element: $category,
            path: 'entry'
        );
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected Entry element');
        
        $this->entryLoader->load($context);
    }

    public function testGenerateTemplatePathsWithBasicEntry(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article',
            'slug' => 'my-article'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $paths = $this->entryLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'entry/blog/article/my-article',
            'entry/blog/article/_entry',
            'entry/blog/_entry',
            'entry/_entry'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testGenerateTemplatePathsWithSiteHandle(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article',
            'siteHandle' => 'en-us'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            baseSite: 'en-us'
        );
        
        $paths = $this->entryLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'entry/en-us/blog/article/_entry',
            'entry/blog/article/_entry'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testValidateElementWithValidEntry(): void
    {
        $entry = $this->createMockEntry();
        
        $result = $this->entryLoader->validateElement($entry);
        
        $this->assertTrue($result);
    }

    public function testValidateElementWithInvalidElement(): void
    {
        $category = $this->createMockCategory();
        
        $result = $this->entryLoader->validateElement($category);
        
        $this->assertFalse($result);
    }

    public function testGenerateTemplatePathsWithNullSafeOperators(): void
    {
        // Test null-safe operator usage for optional properties
        $entry = Mockery::mock(Entry::class);
        $entry->shouldReceive('getId')->andReturn(1);
        $entry->shouldReceive('getSlug')->andReturn('test-entry');
        
        // Mock section that might be null
        $entry->shouldReceive('getSection')->andReturn(null);
        $entry->shouldReceive('getType')->andReturn(null);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $paths = $this->entryLoader->generateTemplatePaths($context);
        
        // Should handle null section/type gracefully
        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);
        $this->assertContains('entry/_entry', $paths);
    }
}