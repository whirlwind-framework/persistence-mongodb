<?php

declare(strict_types=1);

namespace Test\Unit;

use DG\BypassFinals;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use Whirlwind\Persistence\Mongo\ConditionBuilder\ConditionBuilder;
use Whirlwind\Persistence\Mongo\MongoConnection;
use Whirlwind\Persistence\Mongo\Query\MongoQueryFactory;
use Whirlwind\Persistence\Mongo\Structure\MongoCollection;
use Whirlwind\Persistence\Mongo\MongoTableGateway;
use PHPUnit\Framework\TestCase;

class MongoTableGatewayTest extends TestCase
{
    private MockObject $connection;
    private MockObject $queryFactory;
    private MockObject $conditionBuilder;
    private string $collectionName = 'test';
    private MongoTableGateway $tableGateway;

    protected function setUp(): void
    {
        BypassFinals::enable();
        parent::setUp();

        $this->connection = $this->createMock(MongoConnection::class);
        $this->queryFactory = $this->createMock(MongoQueryFactory::class);
        $this->conditionBuilder = $this->createMock(ConditionBuilder::class);

        $this->tableGateway = new MongoTableGateway(
            $this->connection,
            $this->queryFactory,
            $this->conditionBuilder,
            $this->collectionName
        );
    }

    /**
     * @param array $data
     * @param $expected
     * @return void
     * @dataProvider idDataProvider
     */
    public function testInsertDifferentId(array $data, $expected)
    {
        $collection = $this->createMock(MongoCollection::class);
        $this->connection->expects(self::once())
            ->method('getCollection')
            ->willReturn($collection);

        $collection->expects(self::once())
            ->method('insert')
            ->with(self::isType(IsType::TYPE_ARRAY))
            ->willReturnCallback(static function (array $data) {
                return $data['_id'];
            });

        $actual = $this->tableGateway->insert($data);
        self::assertEquals(\json_encode($expected), \json_encode($actual));
    }

    public function idDataProvider(): array
    {
        return [
            [
                'data' => [
                    '_id' => '60d30180b614f25b337f3429'
                ],
                'expected' => [
                    '_id' => new ObjectId('60d30180b614f25b337f3429')
                ]
            ],
            [
                'data' => [
                    '_id' => 'test'
                ],
                'expected' => ['_id' => 'test']
            ],
        ];
    }

    public function testInsertAutogeneratedId()
    {
        $collection = $this->createMock(MongoCollection::class);
        $this->connection->expects(self::once())
            ->method('getCollection')
            ->willReturn($collection);

        $collection->expects(self::once())
            ->method('insert')
            ->with(self::isType(IsType::TYPE_ARRAY))
            ->willReturnCallback(static function (array $data) {
                return $data['_id'];
            });

        $actual = $this->tableGateway->insert([]);
        self::assertArrayHasKey('_id', $actual);
        self::assertInstanceOf(ObjectId::class, $actual['_id']);
    }
}
