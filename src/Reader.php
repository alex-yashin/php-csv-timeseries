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
        if (!empty($pointer)) {
            list($this->day, $this->pos) = explode(':', $pointer);
        } else {
            $days = $this->getAvailableDays();
            $this->day = !empty($days) ? min($days) : '';
            $this->pos = 0;
        }
        return $this;
    }
    
    public function getAvailableDays()
    {
        $years = array_filter(array_map('intval', scandir($this->path)));
        $days = [];
        foreach ($years as $year) {
            $days = array_merge($days, array_filter(array_map('intval', scandir($this->path.'/'.$year))));
        }
        return $days;
    }
    
    public function pointer()
    {
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
    
    public function hasNextDay()
    {
        $days = $this->getAvailableDays();
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
        $date = \DateTime::createFromFormat('Ymd', $this->day);
        $date->add(new DateInterval('P1D'));
        $this->day = $date->format('Ymd');
        $this->pos = 0;
    }

}
