<?php

namespace wabisoft\bonsaitwig\tests\Integration;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\web\twig\Templates;
use wabisoft\bonsaitwig\services\EntryLoader;
use wabisoft\bonsaitwig\services\CategoryLoader;
use wabisoft\bonsaitwig\services\ItemLoader;
use wabisoft\bonsaitwig\services\MatrixLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\utilities\InputValidator;
use wabisoft\bonsaitwig\utilities\SecurityUtils;
use craft\web\View;
use Mockery;

/**
 * Integration tests for Twig function implementations.
 *
 * Tests the complete workflow from Twig function calls through
 * service layers to final template output.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Integration
 * @since 6.4.0
 */
class TwigFunctionTest extends TestCase
{
    private Templates $twigExtension;
    private $mockEntryLoader;
    private $mockCategoryLoader;
    private $mockItemLoader;
    private $mockMatrixLoader;
    private $mockInputValidator;
    private $mockSecurityUtils;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockEntryLoader = Mockery::mock(EntryLoader::class);
        $this->mockCategoryLoader = Mockery::mock(CategoryLoader::class);
        $this->mockItemLoader = Mockery::mock(ItemLoader::class);
        $this->mockMatrixLoader = Mockery::mock(MatrixLoader::class);
        $this->mockInputValidator = Mockery::mock(InputValidator::class);
        $this->mockSecurityUtils = Mockery::mock(SecurityUtils::class);
        
        $this->twigExtension = new Templates(
            $this->mockEntryLoader,
            $this->mockCategoryLoader,
            $this->mockItemLoader,
            $this->mockMatrixLoader,
            $this->mockInputValidator,
            $this->mockSecurityUtils
        );
    }

    public function testEntryTemplatesFunction(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $expectedOutput = '<article>Entry content</article>';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($entry)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('entry')
            ->andReturn('entry');
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('entry')
            ->andReturn('entry');
        
        // Mock entry loader
        $this->mockEntryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('wabisoft\bonsaitwig\valueobjects\TemplateContext'))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->entryTemplates($entry, 'entry');
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testEntryTemplatesWithOptionalParameters(): void
    {
        $entry = $this->createMockEntry();
        $variables = ['showAuthor' => true];
        
        $expectedOutput = '<article>Entry with author</article>';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($entry)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('custom')
            ->andReturn('custom');
        
        $this->mockInputValidator
            ->shouldReceive('validateVariables')
            ->with($variables)
            ->andReturn($variables);
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('custom')
            ->andReturn('custom');
        
        // Mock entry loader
        $this->mockEntryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($context) use ($variables) {
                return $context->path === 'custom' && 
                       $context->variables === $variables;
            }))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->entryTemplates($entry, 'custom', null, null, $variables);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testCategoryTemplatesFunction(): void
    {
        $category = $this->createMockCategory([
            'groupHandle' => 'topics'
        ]);
        
        $expectedOutput = '<div class="category">Category content</div>';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($category)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('category')
            ->andReturn('category');
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('category')
            ->andReturn('category');
        
        // Mock category loader
        $this->mockCategoryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('wabisoft\bonsaitwig\valueobjects\TemplateContext'))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->categoryTemplates($category, 'category');
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testItemTemplatesFunction(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'products',
            'typeHandle' => 'product'
        ]);
        
        $expectedOutput = '<div class="item">Item content</div>';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($entry)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('item')
            ->andReturn('item');
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('item')
            ->andReturn('item');
        
        // Mock item loader
        $this->mockItemLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('wabisoft\bonsaitwig\valueobjects\TemplateContext'))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->itemTemplates($entry, 'item');
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testMatrixTemplatesFunction(): void
    {
        $ownerEntry = $this->createMockEntry();
        $matrixBlock = $this->createMockMatrixBlock([
            'typeHandle' => 'textBlock',
            'owner' => $ownerEntry
        ]);
        
        $expectedOutput = '<div class="matrix-block">Matrix content</div>';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($matrixBlock)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('matrix')
            ->andReturn('matrix');
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('matrix')
            ->andReturn('matrix');
        
        // Mock matrix loader
        $this->mockMatrixLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('wabisoft\bonsaitwig\valueobjects\TemplateContext'))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->matrixTemplates($matrixBlock, 'matrix');
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testMatrixTemplatesWithStyle(): void
    {
        $ownerEntry = $this->createMockEntry();
        $matrixBlock = $this->createMockMatrixBlock([
            'typeHandle' => 'textBlock',
            'owner' => $ownerEntry
        ]);
        
        $expectedOutput = '<div class="matrix-block highlighted">Styled matrix content</div>';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($matrixBlock)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('matrix')
            ->andReturn('matrix');
        
        $this->mockInputValidator
            ->shouldReceive('validateStyle')
            ->with('highlighted')
            ->andReturn('highlighted');
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('matrix')
            ->andReturn('matrix');
        
        $this->mockSecurityUtils
            ->shouldReceive('sanitizeString')
            ->with('highlighted')
            ->andReturn('highlighted');
        
        // Mock matrix loader
        $this->mockMatrixLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($context) {
                return $context->style === 'highlighted';
            }))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->matrixTemplates($matrixBlock, 'matrix', 'highlighted');
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testFunctionWithDebugMode(): void
    {
        $entry = $this->createMockEntry();
        
        $templateOutput = '<article>Content</article>';
        $debugOutput = '<div class="debug">Debug info</div>';
        $expectedOutput = $templateOutput . $debugOutput;
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($entry)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('entry')
            ->andReturn('entry');
        
        $this->mockInputValidator
            ->shouldReceive('isDebugEnabled')
            ->with('full')
            ->andReturn(true);
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('entry')
            ->andReturn('entry');
        
        // Mock entry loader
        $this->mockEntryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($context) {
                return $context->showDebug === true;
            }))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->entryTemplates($entry, 'entry', null, 'full');
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testFunctionWithContext(): void
    {
        $matrixBlock = $this->createMockMatrixBlock();
        $contextEntry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $expectedOutput = '<div class="matrix-in-context">Matrix with context</div>';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($matrixBlock)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($contextEntry)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with('matrix')
            ->andReturn('matrix');
        
        // Mock security validation
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with('matrix')
            ->andReturn('matrix');
        
        // Mock matrix loader
        $this->mockMatrixLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($context) use ($contextEntry) {
                return $context->context === $contextEntry;
            }))
            ->andReturn($expectedOutput);
        
        $result = $this->twigExtension->matrixTemplates($matrixBlock, 'matrix', null, $contextEntry);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->twigExtension->getFunctions();
        
        $this->assertIsArray($functions);
        $this->assertCount(5, $functions);
        
        $functionNames = array_map(fn($func) => $func->getName(), $functions);
        
        $this->assertContains('entryTemplates', $functionNames);
        $this->assertContains('categoryTemplates', $functionNames);
        $this->assertContains('itemTemplates', $functionNames);
        $this->assertContains('matrixTemplates', $functionNames);
        $this->assertContains('btPath', $functionNames);
    }

    public function testInputValidationFailure(): void
    {
        $entry = $this->createMockEntry();
        
        // Mock validation failure
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($entry)
            ->andReturn(false);
        
        $this->expectException(\InvalidArgumentException::class);
        
        $this->twigExtension->entryTemplates($entry, 'entry');
    }

    public function testSecuritySanitization(): void
    {
        $entry = $this->createMockEntry();
        $maliciousPath = '../../../etc/passwd';
        $sanitizedPath = 'etc/passwd';
        
        // Mock input validation
        $this->mockInputValidator
            ->shouldReceive('validateElement')
            ->with($entry)
            ->andReturn(true);
        
        $this->mockInputValidator
            ->shouldReceive('validatePath')
            ->with($maliciousPath)
            ->andReturn($maliciousPath);
        
        // Mock security sanitization
        $this->mockSecurityUtils
            ->shouldReceive('sanitizePath')
            ->with($maliciousPath)
            ->andReturn($sanitizedPath);
        
        // Mock entry loader
        $this->mockEntryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($context) use ($sanitizedPath) {
                return $context->path === $sanitizedPath;
            }))
            ->andReturn('<div>Safe content</div>');
        
        $result = $this->twigExtension->entryTemplates($entry, $maliciousPath);
        
        $this->assertStringContainsString('Safe content', $result);
    }
}