<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Lloople\PHPUnitExtensions\Runners\SlowestTests\Csv;

class CsvTest extends TestCase
{
    protected $csvChannel;
    protected $testFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary file to store the CSV output
        $this->testFile = sys_get_temp_dir() . '/phpunit_results_test.csv';

        // Initialize the Csv class with the temporary file
        $this->csvChannel = new Csv($this->testFile, 5, 200);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the temporary file
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testCsvWritesCorrectResults()
    {
        // Add some tests
        $this->csvChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->csvChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms
        $this->csvChannel->executeAfterTest('TestClass3::testMethod3', 0.1); // 100ms (ignored, below threshold)

        // Trigger CSV writing
        $this->csvChannel->executeAfterLastTest();

        // Assert the file exists
        $this->assertFileExists($this->testFile);

        // Read the file and verify its contents
        $contents = file($this->testFile, FILE_IGNORE_NEW_LINES);

        // Expected CSV output
        $expected = [
            "time,method,class,name",
            "500,testMethod2,TestClass2,TestClass2::testMethod2",
            "300,testMethod1,TestClass1,TestClass1::testMethod1"
        ];

        // Assert contents match
        $this->assertEquals($expected, $contents);
    }

    public function testCsvRespectsRowLimit()
    {
        // Add more tests than the row limit
        $this->csvChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->csvChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms
        $this->csvChannel->executeAfterTest('TestClass3::testMethod3', 0.4); // 400ms
        $this->csvChannel->executeAfterTest('TestClass4::testMethod4', 0.6); // 600ms
        $this->csvChannel->executeAfterTest('TestClass5::testMethod5', 0.7); // 700ms
        $this->csvChannel->executeAfterTest('TestClass6::testMethod6', 0.8); // 800ms (should not be included)

        // Trigger CSV writing
        $this->csvChannel->executeAfterLastTest();

        // Read the file
        $contents = file($this->testFile, FILE_IGNORE_NEW_LINES);

        // Extract rows without headers for validation
        $rows = array_slice($contents, 1);

        // Assert only 5 rows are present
        $this->assertCount(5, $rows);
    }

    public function testCsvHandlesEmptyResultsGracefully()
    {
        // Trigger CSV writing without any tests
        $this->csvChannel->executeAfterLastTest();

        // Assert the file exists
        $this->assertFileExists($this->testFile);

        // Read the file and verify its contents
        $contents = file($this->testFile, FILE_IGNORE_NEW_LINES);

        // Expected output (only the header)
        $expected = [
            "time,method,class,name"
        ];

        // Assert contents match
        $this->assertEquals($expected, $contents);
    }
}
