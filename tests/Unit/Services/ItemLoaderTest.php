<?php

namespace wabisoft\bonsaitwig\tests\Unit\Services;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;

use craft\elements\Entry;
use craft\elements\Category;
use Mockery;

/**
 * Unit tests for ItemLoader service.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\Services
 * @since 6.4.0
 */
class ItemLoaderTest extends TestCase
{
    private ItemLoader $itemLoader;
    private $mockHierarchyLoader;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHierarchyLoader = Mockery::mock(HierarchyTemplateLoader::class);
        $this->itemLoader = new ItemLoader($this->mockHierarchyLoader);
    }

    public function testLoadWithValidEntry(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'products',
            'typeHandle' => 'product'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'item'
        );
        
        $expectedOutput = '<div>Item content</div>';
        
        $this->mockHierarchyLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type(TemplateContext::class))
            ->andReturn($expectedOutput);
        
        $result = $this->itemLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testLoadWithValidCategory(): void
    {
        $category = $this->createMockCategory([
            'groupHandle' => 'productCategories'
        ]);
        
        $context = new TemplateContext(
            element: $category,
            path: 'item'
        );
        
        $expectedOutput = '<div>Category item content</div>';
        
        $this->mockHierarchyLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type(TemplateContext::class))
            ->andReturn($expectedOutput);
        
        $result = $this->itemLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testLoadThrowsExceptionForInvalidElement(): void
    {
        $invalidElement = Mockery::mock('craft\base\Element');
        
        $context = new TemplateContext(
            element: $invalidElement,
            path: 'item'
        );
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected Entry or Category element');
        
        $this->itemLoader->load($context);
    }

    public function testGenerateTemplatePathsWithEntry(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'products',
            'typeHandle' => 'product',
            'slug' => 'awesome-product'
        ]);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'item'
        );
        
        $paths = $this->itemLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'item/products/product/awesome-product',
            'item/products/product/_item',
            'item/products/_item',
            'item/_item'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testGenerateTemplatePathsWithCategory(): void
    {
        $category = $this->createMockCategory([
            'groupHandle' => 'productCategories',
            'slug' => 'electronics'
        ]);
        
        $context = new TemplateContext(
            element: $category,
            path: 'item'
        );
        
        $paths = $this->itemLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'item/productCategories/electronics',
            'item/productCategories/_item',
            'item/_item'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testValidateElementWithValidEntry(): void
    {
        $entry = $this->createMockEntry();
        
        $result = $this->itemLoader->validateElement($entry);
        
        $this->assertTrue($result);
    }

    public function testValidateElementWithValidCategory(): void
    {
        $category = $this->createMockCategory();
        
        $result = $this->itemLoader->validateElement($category);
        
        $this->assertTrue($result);
    }

    public function testValidateElementWithInvalidElement(): void
    {
        $invalidElement = Mockery::mock('craft\base\Element');
        
        $result = $this->itemLoader->validateElement($invalidElement);
        
        $this->assertFalse($result);
    }
}