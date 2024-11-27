<?php

namespace Tests\Unit;

use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use Lloople\PHPUnitExtensions\Runners\SlowestTests\MySQL;

class MySQLTest extends TestCase
{
    protected $mysqlChannel;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup in-memory SQLite for testing
        $this->mysqlChannel = new MySQL(
            [
                'database' => ':memory:',
                'table' => 'test_results',
                'username' => '',
                'password' => '',
                'host' => '127.0.0.1'
            ],
            5,
            200
        );
    }

    public function testTableCreation()
    {
        $pdo = $this->getPDOConnection();
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_results';")->fetch();

        $this->assertNotEmpty($result, 'The table was not created.');
    }

    public function testInsertData()
    {
        $this->mysqlChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->mysqlChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms

        // Simulate the end of the test run
        $this->mysqlChannel->executeAfterLastTest();

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
        // Insert more rows than the limit
        $this->mysqlChannel->executeAfterTest('TestClass1::testMethod1', 0.3); // 300ms
        $this->mysqlChannel->executeAfterTest('TestClass2::testMethod2', 0.5); // 500ms
        $this->mysqlChannel->executeAfterTest('TestClass3::testMethod3', 0.4); // 400ms
        $this->mysqlChannel->executeAfterTest('TestClass4::testMethod4', 0.6); // 600ms
        $this->mysqlChannel->executeAfterTest('TestClass5::testMethod5', 0.7); // 700ms
        $this->mysqlChannel->executeAfterTest('TestClass6::testMethod6', 0.8); // 800ms (should not be included)

        // Simulate the end of the test run
        $this->mysqlChannel->executeAfterLastTest();

        // Verify the table only contains the top 5 results
        $pdo = $this->getPDOConnection();
        $result = $pdo->query("SELECT * FROM test_results ORDER BY time DESC;")->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(5, $result);
        $this->assertEquals('TestClass6::testMethod6', $result[0]['name']);
    }

    public function testHandleExceptionsGracefully()
    {
        $this->expectOutputRegex('/failed:/');

        // Simulate a failure by passing invalid credentials
        new MySQL(
            [
                'database' => '',
                'username' => 'invalid_user',
                'password' => 'invalid_password',
                'host' => 'invalid_host'
            ]
        );
    }

    protected function getPDOConnection(): PDO
    {
        // Create a new PDO connection to the in-memory SQLite database
        return new PDO('sqlite::memory:');
    }
}
