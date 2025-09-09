<?php

namespace wabisoft\bonsaitwig\tests\Unit\ValueObjects;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\valueobjects\DebugInfo;
use wabisoft\bonsaitwig\enums\TemplateType;

/**
 * Unit tests for DebugInfo value object.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\ValueObjects
 * @since 6.4.0
 */
class DebugInfoTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $templates = ['entry/_entry', 'entry/blog/_entry'];
        
        $debugInfo = new DebugInfo(
            directory: 'entry',
            templates: $templates,
            currentTemplate: 'entry/blog/_entry',
            type: TemplateType::ENTRY
        );
        
        $this->assertEquals('entry', $debugInfo->directory);
        $this->assertEquals($templates, $debugInfo->templates);
        $this->assertEquals('entry/blog/_entry', $debugInfo->currentTemplate);
        $this->assertEquals(TemplateType::ENTRY, $debugInfo->type);
        $this->assertEquals(0.0, $debugInfo->resolutionTime);
    }

    public function testConstructorWithAllParameters(): void
    {
        $templates = ['matrix/textBlock', 'matrix/_matrix'];
        $resolutionTime = 0.0025;
        
        $debugInfo = new DebugInfo(
            directory: 'matrix',
            templates: $templates,
            currentTemplate: 'matrix/textBlock',
            type: TemplateType::MATRIX,
            resolutionTime: $resolutionTime
        );
        
        $this->assertEquals('matrix', $debugInfo->directory);
        $this->assertEquals($templates, $debugInfo->templates);
        $this->assertEquals('matrix/textBlock', $debugInfo->currentTemplate);
        $this->assertEquals(TemplateType::MATRIX, $debugInfo->type);
        $this->assertEquals($resolutionTime, $debugInfo->resolutionTime);
    }

    public function testReadonlyProperties(): void
    {
        $debugInfo = new DebugInfo(
            directory: 'entry',
            templates: ['entry/_entry'],
            currentTemplate: 'entry/_entry',
            type: TemplateType::ENTRY
        );
        
        // Properties should be readonly - this test ensures the class is properly defined
        $reflection = new \ReflectionClass($debugInfo);
        $this->assertTrue($reflection->isReadOnly());
        
        // Individual properties should be readonly
        $directoryProperty = $reflection->getProperty('directory');
        $this->assertTrue($directoryProperty->isReadOnly());
        
        $templatesProperty = $reflection->getProperty('templates');
        $this->assertTrue($templatesProperty->isReadOnly());
    }

    public function testWithDifferentTemplateTypes(): void
    {
        $entryDebugInfo = new DebugInfo(
            directory: 'entry',
            templates: ['entry/_entry'],
            currentTemplate: 'entry/_entry',
            type: TemplateType::ENTRY
        );
        
        $categoryDebugInfo = new DebugInfo(
            directory: 'category',
            templates: ['category/_category'],
            currentTemplate: 'category/_category',
            type: TemplateType::CATEGORY
        );
        
        $this->assertEquals(TemplateType::ENTRY, $entryDebugInfo->type);
        $this->assertEquals(TemplateType::CATEGORY, $categoryDebugInfo->type);
        $this->assertNotEquals($entryDebugInfo->type, $categoryDebugInfo->type);
    }

    public function testWithEmptyTemplatesList(): void
    {
        $debugInfo = new DebugInfo(
            directory: 'entry',
            templates: [],
            currentTemplate: '',
            type: TemplateType::ENTRY
        );
        
        $this->assertEquals([], $debugInfo->templates);
        $this->assertEquals('', $debugInfo->currentTemplate);
    }

    public function testWithMultipleTemplates(): void
    {
        $templates = [
            'entry/blog/article/my-post',
            'entry/blog/article/_entry',
            'entry/blog/_entry',
            'entry/_entry'
        ];
        
        $debugInfo = new DebugInfo(
            directory: 'entry',
            templates: $templates,
            currentTemplate: 'entry/blog/article/_entry',
            type: TemplateType::ENTRY
        );
        
        $this->assertCount(4, $debugInfo->templates);
        $this->assertEquals($templates, $debugInfo->templates);
        $this->assertContains('entry/blog/article/my-post', $debugInfo->templates);
        $this->assertContains('entry/_entry', $debugInfo->templates);
    }

    public function testPerformanceTimingAccuracy(): void
    {
        $preciseTime = 0.001234567;
        
        $debugInfo = new DebugInfo(
            directory: 'entry',
            templates: ['entry/_entry'],
            currentTemplate: 'entry/_entry',
            type: TemplateType::ENTRY,
            resolutionTime: $preciseTime
        );
        
        $this->assertEquals($preciseTime, $debugInfo->resolutionTime);
        $this->assertIsFloat($debugInfo->resolutionTime);
    }

    public function testNamedParameters(): void
    {
        // Test that named parameters work in any order
        $debugInfo = new DebugInfo(
            type: TemplateType::ITEM,
            currentTemplate: 'item/_item',
            templates: ['item/_item'],
            directory: 'item',
            resolutionTime: 0.002
        );
        
        $this->assertEquals('item', $debugInfo->directory);
        $this->assertEquals(['item/_item'], $debugInfo->templates);
        $this->assertEquals('item/_item', $debugInfo->currentTemplate);
        $this->assertEquals(TemplateType::ITEM, $debugInfo->type);
        $this->assertEquals(0.002, $debugInfo->resolutionTime);
    }
}