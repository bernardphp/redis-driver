<?php

namespace Bernard\Driver\Redis\Tests;

use Bernard\Driver\Redis\Driver;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @requires extension redis
 */
final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Redis|ObjectProphecy
     */
    private $redis;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp()
    {
        $this->redis = $this->prophesize(\Redis::class);

        $this->driver = new Driver($this->redis->reveal());
    }

    /**
     * @test
     */
    public function it_is_a_driver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    /**
     * @test
     */
    public function it_lists_queues()
    {
        $queues = [
            'failed',
            'queue1',
        ];

        $this->redis->sMembers('queues')->willReturn($queues);

        $this->assertEquals($queues, $this->driver->listQueues());
    }

    /**
     * @test
     */
    public function it_creates_a_queue()
    {
        $this->redis->sAdd('queues', 'send-newsletter')->shouldBeCalled();

        $this->driver->createQueue('send-newsletter');
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->redis->lLen('queue:send-newsletter')->willReturn(4);

        $this->assertEquals(4, $this->driver->countMessages('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_pushes_a_message()
    {
        $this->redis->rPush('queue:send-newsletter', 'This is a message')->shouldBeCalled();

        $this->driver->pushMessage('send-newsletter', 'This is a message');
    }

    /**
     * @test
     */
    public function it_pop_messages()
    {
        $this->redis->blPop(['queue:send-newsletter'], 5)->willReturn(['my-queue', 'message1']);
        $this->redis->blPop(['queue:ask-forgiveness'], 30)->willReturn(['my-queue2', 'message2']);

        $this->assertEquals(['message1', null], $this->driver->popMessage('send-newsletter'));
        $this->assertEquals(['message2', null], $this->driver->popMessage('ask-forgiveness', 30));
    }

    /**
     * @test
     */
    public function it_peeks_in_a_queue()
    {
        $this->redis->lrange('queue:my-queue', 4, 13)->willReturn(['message1']);
        $this->redis->lrange('queue:send-newsletter', 0, 19)->willReturn(['message2']);

        $this->assertEquals(['message1'], $this->driver->peekQueue('my-queue', 4, 10));
        $this->assertEquals(['message2'], $this->driver->peekQueue('send-newsletter'));
    }

    /**
     * @test
     */
    public function it_removes_a_queue()
    {
        $this->redis->del('queue:name')->shouldBeCalled();
        $this->redis->srem('queues', 'name')->shouldBeCalled();

        $this->driver->removeQueue('name');
    }
}
