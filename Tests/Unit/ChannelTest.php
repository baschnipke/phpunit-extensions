<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Lloople\PHPUnitExtensions\Runners\SlowestTests\Channel;

class ChannelTest extends TestCase
{
    protected $mockChannel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a concrete implementation of the abstract Channel class
        $this->mockChannel = new class(5, 200) extends Channel {
            protected function printResults(): void
            {
                echo "Results: " . implode(", ", $this->testsToPrint());
            }
        };
    }

    public function testExecuteAfterTestAddsSlowTests()
    {
        // Add a test with time greater than the threshold
        $this->mockChannel->executeAfterTest('Test1', 0.3); // 300ms
        $this->mockChannel->executeAfterTest('Test2', 0.15); // 150ms (should not be added)

        $this->assertArrayHasKey('Test1', $this->mockChannel->tests);
        $this->assertArrayNotHasKey('Test2', $this->mockChannel->tests);
        $this->assertEquals(300, $this->mockChannel->tests['Test1']);
    }

    public function testSortTestsBySpeed()
    {
        // Add tests
        $this->mockChannel->executeAfterTest('Test1', 0.3); // 300ms
        $this->mockChannel->executeAfterTest('Test2', 0.5); // 500ms
        $this->mockChannel->executeAfterTest('Test3', 0.4); // 400ms

        // Sort tests
        $this->mockChannel->executeAfterLastTest();

        // Access protected tests property to assert sorting
        $sortedTests = $this->mockChannel->testsToPrint();

        $this->assertEquals(['Test2' => 500, 'Test3' => 400, 'Test1' => 300], $sortedTests);
    }

    public function testTestsToPrintRespectsRowLimit()
    {
        // Add more tests than the row limit
        $this->mockChannel->executeAfterTest('Test1', 0.3); // 300ms
        $this->mockChannel->executeAfterTest('Test2', 0.5); // 500ms
        $this->mockChannel->executeAfterTest('Test3', 0.4); // 400ms
        $this->mockChannel->executeAfterTest('Test4', 0.6); // 600ms
        $this->mockChannel->executeAfterTest('Test5', 0.7); // 700ms
        $this->mockChannel->executeAfterTest('Test6', 0.8); // 800ms (should not be included)

        // Sort tests
        $this->mockChannel->executeAfterLastTest();

        // Get the top 5 tests (row limit)
        $topTests = $this->mockChannel->testsToPrint();

        $this->assertCount(5, $topTests);
        $this->assertArrayHasKey('Test6', $this->mockChannel->tests); // Test6 exists but isn't in top 5
        $this->assertEquals(['Test6' => 800, 'Test5' => 700, 'Test4' => 600, 'Test2' => 500, 'Test3' => 400], $topTests);
    }

    public function testTimeToMillisecondsConversion()
    {
        $method = new \ReflectionMethod($this->mockChannel, 'timeToMiliseconds');
        $method->setAccessible(true);

        $this->assertEquals(300, $method->invoke($this->mockChannel, 0.3));
        $this->assertEquals(150, $method->invoke($this->mockChannel, 0.15));
        $this->assertEquals(0, $method->invoke($this->mockChannel, 0));
    }

    public function testGetClassNameReturnsCorrectClassName()
    {
        $method = new \ReflectionMethod($this->mockChannel, 'getClassName');
        $method->setAccessible(true);

        $this->assertEquals(get_class($this->mockChannel), $method->invoke($this->mockChannel));
    }
}
