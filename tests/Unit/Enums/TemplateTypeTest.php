<?php

namespace wabisoft\bonsaitwig\tests\Unit\Enums;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\enums\TemplateType;

/**
 * Unit tests for TemplateType enum.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\Enums
 * @since 6.4.0
 */
class TemplateTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('entry', TemplateType::ENTRY->value);
        $this->assertEquals('category', TemplateType::CATEGORY->value);
        $this->assertEquals('item', TemplateType::ITEM->value);
        $this->assertEquals('matrix', TemplateType::MATRIX->value);
    }

    public function testGetDefaultPath(): void
    {
        $this->assertEquals('entry', TemplateType::ENTRY->getDefaultPath());
        $this->assertEquals('category', TemplateType::CATEGORY->getDefaultPath());
        $this->assertEquals('item', TemplateType::ITEM->getDefaultPath());
        $this->assertEquals('matrix', TemplateType::MATRIX->getDefaultPath());
    }

    public function testFromString(): void
    {
        $this->assertEquals(TemplateType::ENTRY, TemplateType::from('entry'));
        $this->assertEquals(TemplateType::CATEGORY, TemplateType::from('category'));
        $this->assertEquals(TemplateType::ITEM, TemplateType::from('item'));
        $this->assertEquals(TemplateType::MATRIX, TemplateType::from('matrix'));
    }

    public function testTryFromString(): void
    {
        $this->assertEquals(TemplateType::ENTRY, TemplateType::tryFrom('entry'));
        $this->assertEquals(TemplateType::CATEGORY, TemplateType::tryFrom('category'));
        $this->assertEquals(TemplateType::ITEM, TemplateType::tryFrom('item'));
        $this->assertEquals(TemplateType::MATRIX, TemplateType::tryFrom('matrix'));
        $this->assertNull(TemplateType::tryFrom('invalid'));
    }

    public function testAllCases(): void
    {
        $cases = TemplateType::cases();
        
        $this->assertCount(4, $cases);
        $this->assertContains(TemplateType::ENTRY, $cases);
        $this->assertContains(TemplateType::CATEGORY, $cases);
        $this->assertContains(TemplateType::ITEM, $cases);
        $this->assertContains(TemplateType::MATRIX, $cases);
    }

    public function testEnumInArrays(): void
    {
        $templateTypes = [
            TemplateType::ENTRY,
            TemplateType::CATEGORY,
            TemplateType::ITEM,
            TemplateType::MATRIX
        ];
        
        $this->assertContains(TemplateType::ENTRY, $templateTypes);
        $this->assertNotContains('entry', $templateTypes); // String should not match enum
    }

    public function testEnumInSwitchStatement(): void
    {
        $result = match(TemplateType::ENTRY) {
            TemplateType::ENTRY => 'entry_matched',
            TemplateType::CATEGORY => 'category_matched',
            TemplateType::ITEM => 'item_matched',
            TemplateType::MATRIX => 'matrix_matched',
        };
        
        $this->assertEquals('entry_matched', $result);
    }
}