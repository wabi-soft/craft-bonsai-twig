<?php

namespace wabisoft\bonsaitwig\tests\Unit\Services;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\services\MatrixLoader;
use wabisoft\bonsaitwig\services\HierarchyTemplateLoader;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;

use craft\elements\MatrixBlock;
use craft\elements\Entry;
use Mockery;

/**
 * Unit tests for MatrixLoader service.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\Services
 * @since 6.4.0
 */
class MatrixLoaderTest extends TestCase
{
    private MatrixLoader $matrixLoader;
    private $mockHierarchyLoader;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHierarchyLoader = Mockery::mock(HierarchyTemplateLoader::class);
        $this->matrixLoader = new MatrixLoader($this->mockHierarchyLoader);
    }

    public function testLoadWithValidMatrixBlock(): void
    {
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
            path: 'matrix'
        );
        
        $expectedOutput = '<div>Matrix block content</div>';
        
        $this->mockHierarchyLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type(TemplateContext::class))
            ->andReturn($expectedOutput);
        
        $result = $this->matrixLoader->load($context);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testLoadThrowsExceptionForInvalidElement(): void
    {
        $entry = $this->createMockEntry();
        
        $context = new TemplateContext(
            element: $entry,
            path: 'matrix'
        );
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected MatrixBlock element');
        
        $this->matrixLoader->load($context);
    }

    public function testGenerateTemplatePathsWithBasicMatrixBlock(): void
    {
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
            path: 'matrix'
        );
        
        $paths = $this->matrixLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'matrix/pages/page/textBlock',
            'matrix/pages/textBlock',
            'matrix/textBlock',
            'matrix/_matrix'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testGenerateTemplatePathsWithStyle(): void
    {
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
        
        $paths = $this->matrixLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'matrix/pages/page/textBlock/highlighted',
            'matrix/pages/textBlock/highlighted',
            'matrix/textBlock/highlighted',
            'matrix/pages/page/textBlock',
            'matrix/pages/textBlock',
            'matrix/textBlock'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testGenerateTemplatePathsWithContext(): void
    {
        $ownerEntry = $this->createMockEntry([
            'sectionHandle' => 'pages',
            'typeHandle' => 'page'
        ]);
        
        $contextEntry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $matrixBlock = $this->createMockMatrixBlock([
            'typeHandle' => 'textBlock',
            'owner' => $ownerEntry
        ]);
        
        $context = new TemplateContext(
            element: $matrixBlock,
            path: 'matrix',
            context: $contextEntry
        );
        
        $paths = $this->matrixLoader->generateTemplatePaths($context);
        
        $expectedPaths = [
            'matrix/blog/article/textBlock',
            'matrix/blog/textBlock',
            'matrix/pages/page/textBlock',
            'matrix/pages/textBlock',
            'matrix/textBlock'
        ];
        
        $this->assertContainsTemplatePaths($paths, $expectedPaths);
    }

    public function testValidateElementWithValidMatrixBlock(): void
    {
        $matrixBlock = $this->createMockMatrixBlock();
        
        $result = $this->matrixLoader->validateElement($matrixBlock);
        
        $this->assertTrue($result);
    }

    public function testValidateElementWithInvalidElement(): void
    {
        $entry = $this->createMockEntry();
        
        $result = $this->matrixLoader->validateElement($entry);
        
        $this->assertFalse($result);
    }

    public function testGenerateTemplatePathsWithNullSafeOperators(): void
    {
        // Test null-safe operator usage for optional properties
        $matrixBlock = Mockery::mock(MatrixBlock::class);
        $matrixBlock->shouldReceive('getId')->andReturn(1);
        $matrixBlock->shouldReceive('getAttribute')->with('typeHandle')->andReturn('testBlock');
        
        // Mock owner that might be null
        $matrixBlock->shouldReceive('getOwner')->andReturn(null);
        
        $context = new TemplateContext(
            element: $matrixBlock,
            path: 'matrix'
        );
        
        $paths = $this->matrixLoader->generateTemplatePaths($context);
        
        // Should handle null owner gracefully
        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);
        $this->assertContains('matrix/testBlock', $paths);
        $this->assertContains('matrix/_matrix', $paths);
    }
}