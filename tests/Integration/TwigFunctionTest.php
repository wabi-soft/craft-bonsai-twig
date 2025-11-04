<?php

namespace wabisoft\bonsaitwig\tests\Integration;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\web\twig\Templates;
use wabisoft\bonsaitwig\BonsaiTwig;
use craft\web\View;
use craft\test\TestCase as CraftTestCase;
use Craft;
use Mockery;

/**
 * Integration tests for Twig function implementations.
 *
 * Tests the complete workflow from Twig function calls through
 * service layers to final template output, focusing on backward
 * compatibility and function signature preservation.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Integration
 * @since 6.4.0
 */
class TwigFunctionTest extends TestCase
{
    private Templates $twigExtension;
    private $mockPlugin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the plugin instance
        $this->mockPlugin = Mockery::mock(BonsaiTwig::class);
        
        // Mock the service properties
        $this->mockPlugin->entryLoader = Mockery::mock('alias:wabisoft\bonsaitwig\services\EntryLoader');
        $this->mockPlugin->categoryLoader = Mockery::mock('alias:wabisoft\bonsaitwig\services\CategoryLoader');
        $this->mockPlugin->itemLoader = Mockery::mock('alias:wabisoft\bonsaitwig\services\ItemLoader');
        $this->mockPlugin->matrixLoader = Mockery::mock('alias:wabisoft\bonsaitwig\services\MatrixLoader');
        
        // Mock BonsaiTwig::getInstance() to return our mock
        BonsaiTwig::shouldReceive('getInstance')
            ->andReturn($this->mockPlugin);
        
        $this->twigExtension = new Templates();
    }

    public function testEntryTemplatesFunctionSignature(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $expectedOutput = '<article>Entry content</article>';
        
        // Mock entry loader to return expected output
        $this->mockPlugin->entryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        // Test that the function accepts the expected parameters
        $functions = $this->twigExtension->getFunctions();
        $entryFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'entryTemplates') {
                $entryFunction = $function;
                break;
            }
        }
        
        $this->assertNotNull($entryFunction, 'entryTemplates function should be registered');
        $this->assertTrue($entryFunction->getOptions()['is_safe']['html'] ?? false, 'entryTemplates should be HTML safe');
        
        // Test function call with entry parameter
        $callable = $entryFunction->getCallable();
        $result = call_user_func($callable, ['entry' => $entry]);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testEntryTemplatesWithOptionalParameters(): void
    {
        $entry = $this->createMockEntry();
        $variables = ['showAuthor' => true, 'entry' => $entry, 'path' => 'custom'];
        
        $expectedOutput = '<article>Entry with author</article>';
        
        // Mock entry loader
        $this->mockPlugin->entryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($params) use ($variables) {
                return $params['path'] === 'custom' && 
                       $params['showAuthor'] === true &&
                       $params['entry'] === $variables['entry'];
            }))
            ->andReturn($expectedOutput);
        
        // Test function call with additional variables
        $functions = $this->twigExtension->getFunctions();
        $entryFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'entryTemplates') {
                $entryFunction = $function;
                break;
            }
        }
        
        $callable = $entryFunction->getCallable();
        $result = call_user_func($callable, $variables);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testCategoryTemplatesFunctionSignature(): void
    {
        $category = $this->createMockCategory([
            'groupHandle' => 'topics'
        ]);
        
        $expectedOutput = '<div class="category">Category content</div>';
        
        // Mock category loader
        $this->mockPlugin->categoryLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        // Test that the function accepts the expected parameters
        $functions = $this->twigExtension->getFunctions();
        $categoryFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'categoryTemplates') {
                $categoryFunction = $function;
                break;
            }
        }
        
        $this->assertNotNull($categoryFunction, 'categoryTemplates function should be registered');
        $this->assertTrue($categoryFunction->getOptions()['is_safe']['html'] ?? false, 'categoryTemplates should be HTML safe');
        
        // Test function call with category parameter
        $callable = $categoryFunction->getCallable();
        $result = call_user_func($callable, ['entry' => $category]);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testItemTemplatesFunctionSignature(): void
    {
        $entry = $this->createMockEntry([
            'sectionHandle' => 'products',
            'typeHandle' => 'product'
        ]);
        
        $expectedOutput = '<div class="item">Item content</div>';
        
        // Mock item loader
        $this->mockPlugin->itemLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        // Test that the function accepts the expected parameters
        $functions = $this->twigExtension->getFunctions();
        $itemFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'itemTemplates') {
                $itemFunction = $function;
                break;
            }
        }
        
        $this->assertNotNull($itemFunction, 'itemTemplates function should be registered');
        $this->assertTrue($itemFunction->getOptions()['is_safe']['html'] ?? false, 'itemTemplates should be HTML safe');
        
        // Test function call with entry parameter
        $callable = $itemFunction->getCallable();
        $result = call_user_func($callable, ['entry' => $entry]);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testMatrixTemplatesFunctionSignature(): void
    {
        $ownerEntry = $this->createMockEntry();
        $matrixBlock = $this->createMockMatrixBlock([
            'typeHandle' => 'textBlock',
            'owner' => $ownerEntry
        ]);
        
        $expectedOutput = '<div class="matrix-block">Matrix content</div>';
        
        // Mock matrix loader
        $this->mockPlugin->matrixLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedOutput);
        
        // Test that the function accepts the expected parameters
        $functions = $this->twigExtension->getFunctions();
        $matrixFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'matrixTemplates') {
                $matrixFunction = $function;
                break;
            }
        }
        
        $this->assertNotNull($matrixFunction, 'matrixTemplates function should be registered');
        $this->assertTrue($matrixFunction->getOptions()['is_safe']['html'] ?? false, 'matrixTemplates should be HTML safe');
        
        // Test function call with block parameter
        $callable = $matrixFunction->getCallable();
        $result = call_user_func($callable, ['block' => $matrixBlock]);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testMatrixTemplatesWithStyleParameter(): void
    {
        $ownerEntry = $this->createMockEntry();
        $matrixBlock = $this->createMockMatrixBlock([
            'typeHandle' => 'textBlock',
            'owner' => $ownerEntry
        ]);
        
        $expectedOutput = '<div class="matrix-block highlighted">Styled matrix content</div>';
        
        // Mock matrix loader
        $this->mockPlugin->matrixLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($params) {
                return $params['style'] === 'highlighted' && 
                       isset($params['block']);
            }))
            ->andReturn($expectedOutput);
        
        // Test function call with style parameter
        $functions = $this->twigExtension->getFunctions();
        $matrixFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'matrixTemplates') {
                $matrixFunction = $function;
                break;
            }
        }
        
        $callable = $matrixFunction->getCallable();
        $result = call_user_func($callable, [
            'block' => $matrixBlock,
            'style' => 'highlighted'
        ]);
        
        $this->assertEquals($expectedOutput, $result);
    }

    public function testBtPathFunctionSignature(): void
    {
        // Test that btPath function is registered correctly
        $functions = $this->twigExtension->getFunctions();
        $btPathFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'btPath') {
                $btPathFunction = $function;
                break;
            }
        }
        
        $this->assertNotNull($btPathFunction, 'btPath function should be registered');
        $this->assertTrue($btPathFunction->getOptions()['is_safe']['html'] ?? false, 'btPath should be HTML safe');
        $this->assertTrue($btPathFunction->getOptions()['needs_context'] ?? false, 'btPath should need context');
        
        // Test function call with context
        $context = [
            '_btTemplates' => ['template1.twig', 'template2.twig'],
            '_btResolvedTemplate' => 'template1.twig',
            'entry' => $this->createMockEntry()
        ];
        
        $callable = $btPathFunction->getCallable();
        $result = call_user_func($callable, $context);
        
        // Should return HTML output in dev mode or empty string in production
        $this->assertIsString($result);
    }

    public function testMatrixTemplatesWithContextParameter(): void
    {
        $matrixBlock = $this->createMockMatrixBlock();
        $contextEntry = $this->createMockEntry([
            'sectionHandle' => 'blog',
            'typeHandle' => 'article'
        ]);
        
        $expectedOutput = '<div class="matrix-in-context">Matrix with context</div>';
        
        // Mock matrix loader
        $this->mockPlugin->matrixLoader
            ->shouldReceive('load')
            ->once()
            ->with(Mockery::on(function($params) use ($contextEntry) {
                return $params['ctx'] === $contextEntry && 
                       isset($params['block']);
            }))
            ->andReturn($expectedOutput);
        
        // Test function call with context parameter
        $functions = $this->twigExtension->getFunctions();
        $matrixFunction = null;
        
        foreach ($functions as $function) {
            if ($function->getName() === 'matrixTemplates') {
                $matrixFunction = $function;
                break;
            }
        }
        
        $callable = $matrixFunction->getCallable();
        $result = call_user_func($callable, [
            'block' => $matrixBlock,
            'ctx' => $contextEntry
        ]);
        
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

    public function testAllFunctionsAreHtmlSafe(): void
    {
        $functions = $this->twigExtension->getFunctions();
        
        foreach ($functions as $function) {
            $this->assertTrue(
                $function->getOptions()['is_safe']['html'] ?? false,
                "Function {$function->getName()} should be HTML safe"
            );
        }
    }

    public function testFunctionParameterCompatibility(): void
    {
        // Test that all functions accept array parameters as expected
        $entry = $this->createMockEntry();
        $category = $this->createMockCategory();
        $matrixBlock = $this->createMockMatrixBlock();
        
        // Mock all loaders to return simple output
        $this->mockPlugin->entryLoader->shouldReceive('load')->andReturn('<div>entry</div>');
        $this->mockPlugin->categoryLoader->shouldReceive('load')->andReturn('<div>category</div>');
        $this->mockPlugin->itemLoader->shouldReceive('load')->andReturn('<div>item</div>');
        $this->mockPlugin->matrixLoader->shouldReceive('load')->andReturn('<div>matrix</div>');
        
        $functions = $this->twigExtension->getFunctions();
        
        // Test entryTemplates accepts entry parameter
        $entryFunction = array_filter($functions, fn($f) => $f->getName() === 'entryTemplates')[0];
        $callable = $entryFunction->getCallable();
        $result = call_user_func($callable, ['entry' => $entry]);
        $this->assertIsString($result);
        
        // Test categoryTemplates accepts entry parameter (for category)
        $categoryFunction = array_filter($functions, fn($f) => $f->getName() === 'categoryTemplates')[0];
        $callable = $categoryFunction->getCallable();
        $result = call_user_func($callable, ['entry' => $category]);
        $this->assertIsString($result);
        
        // Test itemTemplates accepts entry parameter
        $itemFunction = array_filter($functions, fn($f) => $f->getName() === 'itemTemplates')[0];
        $callable = $itemFunction->getCallable();
        $result = call_user_func($callable, ['entry' => $entry]);
        $this->assertIsString($result);
        
        // Test matrixTemplates accepts block parameter
        $matrixFunction = array_filter($functions, fn($f) => $f->getName() === 'matrixTemplates')[0];
        $callable = $matrixFunction->getCallable();
        $result = call_user_func($callable, ['block' => $matrixBlock]);
        $this->assertIsString($result);
    }
}