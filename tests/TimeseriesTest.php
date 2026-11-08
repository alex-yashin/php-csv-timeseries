<?php

use PHPUnit\Framework\TestCase;

class TimeseriesTest extends TestCase
{

    /** Временный каталог данных для изолированных тестов */
    protected $tmpDir = null;

    protected function tearDown()
    {
        if (!is_null($this->tmpDir)) {
            $this->removeDir($this->tmpDir);
            $this->tmpDir = null;
        }
    }

    protected function removeDir($dir)
    {
        foreach (array_diff(scandir($dir), ['.', '..']) as $entry) {
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

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

    public function testReadSkipsMissingDay()
    {
        $this->tmpDir = sys_get_temp_dir() . '/csv-timeseries-' . uniqid();
        $existingDay = '20210105';
        $missingDay = '20210103';
        $dayAfterAll = '20210110';
        mkdir($this->tmpDir . '/2021', 0777, true);
        $file = $this->tmpDir . '/2021/' . $existingDay . '.csv';
        file_put_contents($file, "ts1,first\nts2,second\n");

        // Файла дня из указателя нет: читатель переходит к следующему доступному дню с начала файла
        $reader = new \CSVTimeseries\Reader;
        $reader->from($this->tmpDir, $missingDay . ':10');
        $this->assertEquals(['ts1', 'first'], $reader->next());
        $this->assertEquals(['ts2', 'second'], $reader->next());
        $this->assertEquals($existingDay . ':' . filesize($file), $reader->pointer());
        $reader->close();

        // Следующих дней нет: без ошибки возвращается пустой результат, указатель не меняется
        $reader = new \CSVTimeseries\Reader;
        $reader->from($this->tmpDir, $dayAfterAll . ':0');
        $this->assertEmpty($reader->next());
        $this->assertEquals($dayAfterAll . ':0', $reader->pointer());
        $reader->close();
    }

}
