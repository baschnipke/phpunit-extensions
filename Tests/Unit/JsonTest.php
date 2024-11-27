<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Lloople\PHPUnitExtensions\Runners\SlowestTests\Json;

class JsonTest extends TestCase
{
    protected $jsonChannel;
    protected $testFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary file to store the JSON output
        $this->testFile = sys_get_temp_dir() . '/phpunit_results_test.json';

        // Initialize the Json class with the temporary file
        $this->jsonChannel = new Json($this->testFile, 5, 200);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the temporary file
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testJsonWritesCorrectResults()
    {
        // Add some tests
        $this->jsonChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->jsonChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms
        $this->jsonChannel->executeAfterTest('TestClass3::testMethod3', 0.1); // 100ms (ignored, below threshold)

        // Trigger JSON writing
        $this->jsonChannel->executeAfterLastTest();

        // Assert the file exists
        $this->assertFileExists($this->testFile);

        // Read the file and decode JSON
        $contents = file_get_contents($this->testFile);
        $data = json_decode($contents, true);

        // Expected JSON data
        $expected = [
            [
                'time' => 500,
                'method' => 'testMethod2',
                'class' => 'TestClass2',
                'name' => 'TestClass2::testMethod2'
            ],
            [
                'time' => 300,
                'method' => 'testMethod1',
                'class' => 'TestClass1',
                'name' => 'TestClass1::testMethod1'
            ]
        ];

        // Assert JSON structure and content
        $this->assertEquals($expected, $data);
    }

    public function testJsonRespectsRowLimit()
    {
        // Add more tests than the row limit
        $this->jsonChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->jsonChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms
        $this->jsonChannel->executeAfterTest('TestClass3::testMethod3', 0.4); // 400ms
        $this->jsonChannel->executeAfterTest('TestClass4::testMethod4', 0.6); // 600ms
        $this->jsonChannel->executeAfterTest('TestClass5::testMethod5', 0.7); // 700ms
        $this->jsonChannel->executeAfterTest('TestClass6::testMethod6', 0.8); // 800ms (should not be included)

        // Trigger JSON writing
        $this->jsonChannel->executeAfterLastTest();

        // Read the file and decode JSON
        $contents = file_get_contents($this->testFile);
        $data = json_decode($contents, true);

        // Assert only 5 rows are present
        $this->assertCount(5, $data);

        // Assert the top 5 tests are included in descending order
        $expectedTopTests = [
            ['time' => 800, 'method' => 'testMethod6', 'class' => 'TestClass6', 'name' => 'TestClass6::testMethod6'],
            ['time' => 700, 'method' => 'testMethod5', 'class' => 'TestClass5', 'name' => 'TestClass5::testMethod5'],
            ['time' => 600, 'method' => 'testMethod4', 'class' => 'TestClass4', 'name' => 'TestClass4::testMethod4'],
            ['time' => 500, 'method' => 'testMethod2', 'class' => 'TestClass2', 'name' => 'TestClass2::testMethod2'],
            ['time' => 400, 'method' => 'testMethod3', 'class' => 'TestClass3', 'name' => 'TestClass3::testMethod3']
        ];

        $this->assertEquals($expectedTopTests, $data);
    }

    public function testJsonHandlesEmptyResultsGracefully()
    {
        // Trigger JSON writing without any tests
        $this->jsonChannel->executeAfterLastTest();

        // Assert the file exists
        $this->assertFileExists($this->testFile);

        // Read the file and decode JSON
        $contents = file_get_contents($this->testFile);
        $data = json_decode($contents, true);

        // Assert the JSON is an empty array
        $this->assertEquals([], $data);
    }
}
