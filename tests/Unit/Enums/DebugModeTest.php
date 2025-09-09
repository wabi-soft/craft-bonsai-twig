<?php

namespace wabisoft\bonsaitwig\tests\Unit\Enums;

use wabisoft\bonsaitwig\tests\TestCase;
use wabisoft\bonsaitwig\enums\DebugMode;

/**
 * Unit tests for DebugMode enum.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Unit\Enums
 * @since 6.4.0
 */
class DebugModeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals('', DebugMode::DISABLED->value);
        $this->assertEquals('path', DebugMode::PATH->value);
        $this->assertEquals('hierarchy', DebugMode::HIERARCHY->value);
        $this->assertEquals('full', DebugMode::FULL->value);
    }

    public function testIsEnabled(): void
    {
        $this->assertFalse(DebugMode::DISABLED->isEnabled());
        $this->assertTrue(DebugMode::PATH->isEnabled());
        $this->assertTrue(DebugMode::HIERARCHY->isEnabled());
        $this->assertTrue(DebugMode::FULL->isEnabled());
    }

    public function testFromString(): void
    {
        $this->assertEquals(DebugMode::DISABLED, DebugMode::from(''));
        $this->assertEquals(DebugMode::PATH, DebugMode::from('path'));
        $this->assertEquals(DebugMode::HIERARCHY, DebugMode::from('hierarchy'));
        $this->assertEquals(DebugMode::FULL, DebugMode::from('full'));
    }

    public function testTryFromString(): void
    {
        $this->assertEquals(DebugMode::DISABLED, DebugMode::tryFrom(''));
        $this->assertEquals(DebugMode::PATH, DebugMode::tryFrom('path'));
        $this->assertEquals(DebugMode::HIERARCHY, DebugMode::tryFrom('hierarchy'));
        $this->assertEquals(DebugMode::FULL, DebugMode::tryFrom('full'));
        $this->assertNull(DebugMode::tryFrom('invalid'));
    }

    public function testAllCases(): void
    {
        $cases = DebugMode::cases();
        
        $this->assertCount(4, $cases);
        $this->assertContains(DebugMode::DISABLED, $cases);
        $this->assertContains(DebugMode::PATH, $cases);
        $this->assertContains(DebugMode::HIERARCHY, $cases);
        $this->assertContains(DebugMode::FULL, $cases);
    }

    public function testDebugModeLogic(): void
    {
        // Test that only enabled modes show debug info
        $enabledModes = array_filter(DebugMode::cases(), fn($mode) => $mode->isEnabled());
        
        $this->assertCount(3, $enabledModes);
        $this->assertNotContains(DebugMode::DISABLED, $enabledModes);
    }

    public function testEnumInConditionals(): void
    {
        $mode = DebugMode::FULL;
        
        $shouldShowDebug = match($mode) {
            DebugMode::DISABLED => false,
            DebugMode::PATH, DebugMode::HIERARCHY, DebugMode::FULL => true,
        };
        
        $this->assertTrue($shouldShowDebug);
    }
}