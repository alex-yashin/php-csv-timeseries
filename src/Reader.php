<?php

namespace CSVTimeseries;

class Reader
{

    const FILE_EXTENSION = '.csv';

    protected $resource = null;
    protected $path = '';
    protected $day = '';
    protected $pos = 0;

    public function __construct()
    {
        $this->day = date('Ymd');
        $this->pos = 0;
    }
    
    public function from($path, $pointer = '')
    {
        $this->path = $path;
        if (!empty($pointer) && preg_match("/\d+\:\d+/s", $pointer)) {
            list($this->day, $this->pos) = explode(':', $pointer);
        } else {
            $days = $this->getAvailableDays();
            $this->day = !empty($days) ? min($days) : '';
            $this->pos = 0;
        }
        return $this;
    }
    
    
    public function pointer()
    {
        if (empty($this->day)) {
            return '';
        }
        return \implode(':', [$this->day, $this->pos]);
    }

    public function next()
    {
        $r = $this->open();
        if (is_null($r)) {
            return [];
        }
        $line = fgetcsv($r);
        $this->pos = ftell($r);
        if (empty($line) && $this->hasNextDay()) {
            $this->nextDay();
            return $this->next();
        }
        return $line;
    }

    public function open()
    {
        if (!is_null($this->resource)) {
            return $this->resource;
        }
        if (empty($this->day)) {
            return null;
        }
        if (!$this->skipMissingDays()) {
            return null;
        }
        $resource = fopen($this->getFilePath($this->day), 'r');
        if ($resource === false) {
            return null;
        }
        $this->resource = $resource;
        if (!empty($this->pos)) {
            fseek($this->resource, $this->pos);
        }
        return $this->resource;
    }
    
    public function close()
    {
        if (!is_null($this->resource)) {
            fclose($this->resource);
        }
        $this->resource = null;
    }

    /**
     * Если файла текущего дня нет, переключается на ближайший следующий доступный день.
     * Возвращает false, если доступных файлов не осталось (указатель при этом не меняется,
     * чтобы можно было дочитать файл, когда он появится).
     */
    protected function skipMissingDays()
    {
        while (!is_file($this->getFilePath($this->day))) {
            $nextDay = $this->getNextDay();
            if (empty($nextDay)) {
                return false;
            }
            $this->day = $nextDay;
            $this->pos = 0;
        }
        return true;
    }

    protected function getFilePath($day)
    {
        return rtrim($this->path, '/') . '/' . substr($day, 0, 4) . '/' . $day . self::FILE_EXTENSION;
    }
    
    protected function getAvailableDays()
    {
        $days = [];
        foreach ($this->scanNumericEntries($this->path) as $year) {
            $days = array_merge($days, $this->scanNumericEntries(rtrim($this->path, '/') . '/' . $year));
        }
        return $days;
    }

    /**
     * Числовые имена записей каталога (годы или дни); пустой массив, если каталога нет.
     */
    protected function scanNumericEntries($dir)
    {
        if (!is_dir($dir)) {
            return [];
        }
        $entries = scandir($dir);
        if ($entries === false) {
            return [];
        }
        return array_values(array_filter(array_map('intval', $entries)));
    }
    
    protected function hasNextDay()
    {
        $nextDay = $this->getNextDay();
        return !empty($nextDay);
    }
    
    protected function getNextDay()
    {
        $days = $this->getAvailableDays();
        $current = $this->day;
        $days = array_filter(array_map(function ($a) use ($current) {
                return $a > $current ? $a : 0;
            }, $days));
        return count($days) ? min($days) : '';
    }
    
    protected function nextDay()
    {
        $this->setNextDay();
        if (!is_null($this->resource)) {
            fclose($this->resource);
            $this->resource = null;
        }
    }
    
    protected function setNextDay()
    {
        $this->day = $this->getNextDay();
        $this->pos = 0;
    }

}
