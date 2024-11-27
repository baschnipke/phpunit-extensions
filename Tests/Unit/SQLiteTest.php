<?php

namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use Lloople\PHPUnitExtensions\Runners\SlowestTests\SQLite;

class SQLiteTest extends TestCase
{
    protected $sqliteChannel;
    protected $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a temporary SQLite database for testing
        $this->testDatabase = sys_get_temp_dir() . '/phpunit_results_test.db';

        // Initialize the SQLite class with the temporary database
        $this->sqliteChannel = new SQLite(
            [
                'database' => $this->testDatabase,
                'table' => 'test_results',
            ],
            5,
            200
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up the test database file
        if (file_exists($this->testDatabase)) {
            unlink($this->testDatabase);
        }
    }

    public function testTableCreation()
    {
        $pdo = $this->getPDOConnection();
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_results';")->fetch();

        $this->assertNotEmpty($result, 'The table was not created.');
    }

    public function testInsertData()
    {
        $this->sqliteChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->sqliteChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms

        // Simulate the end of the test run
        $this->sqliteChannel->executeAfterLastTest();

        // Verify data in the table
        $pdo = $this->getPDOConnection();
        $result = $pdo->query("SELECT * FROM test_results;")->fetchAll(PDO::FETCH_ASSOC);

        $expected = [
            [
                'time' => 500,
                'name' => 'TestClass2::testMethod2',
                'method' => 'testMethod2',
                'class' => 'TestClass2'
            ],
            [
                'time' => 300,
                'name' => 'TestClass1::testMethod1',
                'method' => 'testMethod1',
                'class' => 'TestClass1'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testRowLimit()
    {
        // Add more rows than the limit
        $this->sqliteChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->sqliteChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms
        $this->sqliteChannel->executeAfterTest('TestClass3::testMethod3', 0.4); // 400ms
        $this->sqliteChannel->executeAfterTest('TestClass4::testMethod4', 0.6); // 600ms
        $this->sqliteChannel->executeAfterTest('TestClass5::testMethod5', 0.7); // 700ms
        $this->sqliteChannel->executeAfterTest('TestClass6::testMethod6', 0.8); // 800ms (should not be included)

        // Simulate the end of the test run
        $this->sqliteChannel->executeAfterLastTest();

        // Verify the table only contains the top 5 results
        $pdo = $this->getPDOConnection();
        $result = $pdo->query("SELECT * FROM test_results ORDER BY time DESC;")->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(5, $result);
        $this->assertEquals('TestClass6::testMethod6', $result[0]['name']);
    }

    public function testConflictHandling()
    {
        // Insert the same test multiple times with different times
        $this->sqliteChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->sqliteChannel->executeAfterTest('TestClass1::testMethod1', 0.5); // 500ms (updated)

        // Simulate the end of the test run
        $this->sqliteChannel->executeAfterLastTest();

        // Verify data in the table
        $pdo = $this->getPDOConnection();
        $result = $pdo->query("SELECT * FROM test_results WHERE name = 'TestClass1::testMethod1';")->fetch(PDO::FETCH_ASSOC);

        $expected = [
            'time' => 500,
            'name' => 'TestClass1::testMethod1',
            'method' => 'testMethod1',
            'class' => 'TestClass1'
        ];

        $this->assertEquals($expected, $result);
    }

    protected function getPDOConnection(): PDO
    {
        // Create a PDO connection to the test SQLite database
        return new PDO("sqlite:{$this->testDatabase}");
    }
}
