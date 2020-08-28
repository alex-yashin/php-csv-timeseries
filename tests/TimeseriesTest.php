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
        @mkdir(__DIR__ . '/data/2019', 0777, true);
        @mkdir(__DIR__ . '/data/2020', 0777, true);
        file_put_contents(__DIR__ . '/data/2019/20191231.csv', '');
        file_put_contents(__DIR__ . '/data/2020/20200102.csv', '');
        file_put_contents(__DIR__ . '/data/2020/20200103.csv', '');
        
        $reader = new \CSVTimeseries\Reader;
        $reader->from(__DIR__ . '/data/');
        $line = $reader->next();
        $reader->close();
        $ts = array_shift($line);
        
        $i = 1;
        $this->assertEquals('test', $line[0]);
        $this->assertEquals($i++, $line[1]);

        $expectedPointer = date('Ymd') . ':' . '40';

        $this->assertEquals($expectedPointer, $reader->pointer());

        $reader = new \CSVTimeseries\Reader;
        $reader->from(__DIR__ . '/data/', $expectedPointer);
        $line = $reader->next();
        $ts = array_shift($line);
        $this->assertEquals('test', $line[0]);
        $this->assertEquals($i++, $line[1]);
        $expectedPointer = date('Ymd') . ':' . '80';
        $this->assertEquals($expectedPointer, $reader->pointer());
        
        while ($line = $reader->next()) {
            $ts = array_shift($line);
            $this->assertEquals('test', $line[0]);
            $this->assertEquals($i++, $line[1]);
            $this->assertEquals('simple', $line[2]);
        }
    }

}
