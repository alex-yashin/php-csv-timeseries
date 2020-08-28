<?php

use PHPUnit\Framework\TestCase;
use Pina\Input;

class TimeseriesTest extends TestCase
{

    public function testWrite()
    {

        $file = __DIR__ . '/data/' . date('Y') . '/' . date('Ymd') . '.csv';
        if (file_exists($file)) {
            unlink($file);
        }

        $writer = new \CSVTimeseries\Writer;
        $writer->to(__DIR__ . '/data/');
        $i = 1;
        while ($i < 1000) {
            $writer->add(['test', $i++, 'simple']);
        }
        $writer->save();
        $this->assertEquals(0, $writer->getBufferLength());
    }

    public function testRead()
    {
        $reader = new \CSVTimeseries\Reader;
        $reader->from(__DIR__ . '/data/');
//        print_r($reader);
        $line = $reader->next();
        $reader->close();
        $ts = array_shift($line);
        $this->assertEquals('test', $line[0]);
        $this->assertEquals(1, $line[1]);

        $expectedPointer = date('Ymd') . ':' . '40';

        $this->assertEquals($expectedPointer, $reader->pointer());

        $reader = new \CSVTimeseries\Reader;
        $reader->from(__DIR__ . '/data/', $expectedPointer);
        $line = $reader->next();
        $ts = array_shift($line);
        $this->assertEquals('test', $line[0]);
        $this->assertEquals(2, $line[1]);
        $expectedPointer = date('Ymd') . ':' . '80';
        $this->assertEquals($expectedPointer, $reader->pointer());
    }

}
