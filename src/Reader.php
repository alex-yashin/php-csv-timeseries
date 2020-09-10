<?php

namespace CSVTimeseries;

class Reader
{

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
        $path = rtrim($this->path, '/') . '/' . substr($this->day, 0, 4);
        $filePath = $path . '/' . $this->day . '.csv';
        $this->resource = fopen($filePath, 'r');
        if (!empty($this->pos)) {
            fseek($this->resource, $this->pos);
        }
        return $this->resource;
    }
    
    public function close()
    {
        fclose($this->resource);
        $this->resource = null;
    }
    
    protected function getAvailableDays()
    {
        $years = array_filter(array_map('intval', scandir($this->path)));
        $days = [];
        foreach ($years as $year) {
            $days = array_merge($days, array_filter(array_map('intval', scandir($this->path.'/'.$year))));
        }
        return $days;
    }
    
    protected function hasNextDay()
    {
        return !empty($this->getNextDay());
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
