<?php
namespace Hyperion\Tests\Workflow\Loggers;

use Hyperion\Workflow\Loggers\MemoryLogger;

class MemoryLoggerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider sampleTextDataProvider
     * @small
     */
    public function testBackspaceNormalisation($sample, $expected)
    {
        $logger = new MemoryLogger(false);
        $logger->info($sample);
        $this->assertEquals($expected, $logger->getLog());
    }

    public function sampleTextDataProvider()
    {
        return [
            ["hello dear".str_repeat(chr(8), 5)." world", "hello world"],
            ["hello ".str_repeat(chr(8), 15)."world", "world"],
            ["hello world".str_repeat(chr(8), 3), "hello wo"],
            ["hello world".str_repeat(chr(8), 20), ""],
            ["hello world".str_repeat(chr(8), 20)."!", "!"],
            ["hello\nworld".str_repeat(chr(8), 7), "hell"],
            ["hello\r\nworld".str_repeat(chr(8), 7), "hell"],
            ["hello\r\nworld", "hello\nworld"],
            ["hello\rworld", "world"],
            ["hel".str_repeat(chr(8), 3)."lo\rworld", "world"],
            ["hello".str_repeat(chr(8), 5)."\rworld", "world"],
            ["hello \rworld".str_repeat(chr(8), 3), "wo"],
            ["hello \r".str_repeat(chr(8), 3)."world", "world"],
            ["hello\nwor\rld", "hello\nld"],
            ["hello\r\nwor\rld", "hello\nld"],
            ["hello wor\n\rld", "hello wor\nld"],
            ["hello world\n\r", "hello world\n"],
        ];
    }

}
 