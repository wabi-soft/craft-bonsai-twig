<?php

namespace wabisoft\bonsaitwig\tests\Unit;

use wabisoft\bonsaitwig\tests\TestCase;

/**
 * Simple test to verify the testing framework is working.
 */
class SimpleTest extends TestCase
{
    public function testFrameworkWorks(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
    }
    
    public function testMockeryWorks(): void
    {
        $mock = \Mockery::mock('stdClass');
        $mock->shouldReceive('test')->once()->andReturn('success');
        
        $result = $mock->test();
        $this->assertEquals('success', $result);
    }
}