<?php

namespace wabisoft\bonsaitwig\tests\Unit\ValueObjects;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use craft\elements\Entry;

/**
 * Unit tests for TemplateContext value object.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\ValueObjects
 * @since 6.4.0
 */
class TemplateContextTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $entry = $this->createMockEntry();
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $this->assertSame($entry, $context->element);
        $this->assertEquals('entry', $context->path);
        $this->assertNull($context->style);
        $this->assertNull($context->context);
        $this->assertNull($context->baseSite);
        $this->assertEquals([], $context->variables);
        $this->assertFalse($context->showDebug);
    }

    public function testConstructorWithAllParameters(): void
    {
        $entry = $this->createMockEntry();
        $contextEntry = $this->createMockEntry(['id' => 2]);
        $variables = ['key' => 'value', 'number' => 42];
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            style: 'featured',
            context: $contextEntry,
            baseSite: 'en-us',
            variables: $variables,
            showDebug: true
        );
        
        $this->assertSame($entry, $context->element);
        $this->assertEquals('entry', $context->path);
        $this->assertEquals('featured', $context->style);
        $this->assertSame($contextEntry, $context->context);
        $this->assertEquals('en-us', $context->baseSite);
        $this->assertEquals($variables, $context->variables);
        $this->assertTrue($context->showDebug);
    }

    public function testReadonlyProperties(): void
    {
        $entry = $this->createMockEntry();
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        // Properties should be readonly - this test ensures the class is properly defined
        $reflection = new \ReflectionClass($context);
        $this->assertTrue($reflection->isReadOnly());
        
        // Individual properties should be readonly
        $elementProperty = $reflection->getProperty('element');
        $this->assertTrue($elementProperty->isReadOnly());
        
        $pathProperty = $reflection->getProperty('path');
        $this->assertTrue($pathProperty->isReadOnly());
    }

    public function testNamedParameters(): void
    {
        $entry = $this->createMockEntry();
        
        // Test that named parameters work in any order
        $context = new TemplateContext(
            showDebug: true,
            path: 'entry',
            element: $entry,
            style: 'compact'
        );
        
        $this->assertSame($entry, $context->element);
        $this->assertEquals('entry', $context->path);
        $this->assertEquals('compact', $context->style);
        $this->assertTrue($context->showDebug);
    }

    public function testWithVariables(): void
    {
        $entry = $this->createMockEntry();
        $variables = [
            'title' => 'Custom Title',
            'showAuthor' => true,
            'metadata' => ['created' => '2024-01-01']
        ];
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            variables: $variables
        );
        
        $this->assertEquals($variables, $context->variables);
        $this->assertEquals('Custom Title', $context->variables['title']);
        $this->assertTrue($context->variables['showAuthor']);
        $this->assertIsArray($context->variables['metadata']);
    }

    public function testWithContext(): void
    {
        $entry = $this->createMockEntry(['id' => 1]);
        $contextEntry = $this->createMockEntry(['id' => 2, 'sectionHandle' => 'blog']);
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            context: $contextEntry
        );
        
        $this->assertSame($contextEntry, $context->context);
        $this->assertNotSame($entry, $context->context);
    }

    public function testDebugModeToggle(): void
    {
        $entry = $this->createMockEntry();
        
        $contextWithoutDebug = new TemplateContext(
            element: $entry,
            path: 'entry'
        );
        
        $contextWithDebug = new TemplateContext(
            element: $entry,
            path: 'entry',
            showDebug: true
        );
        
        $this->assertFalse($contextWithoutDebug->showDebug);
        $this->assertTrue($contextWithDebug->showDebug);
    }

    public function testSiteHandling(): void
    {
        $entry = $this->createMockEntry();
        
        $context = new TemplateContext(
            element: $entry,
            path: 'entry',
            baseSite: 'fr-ca'
        );
        
        $this->assertEquals('fr-ca', $context->baseSite);
    }
}