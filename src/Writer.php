<?php

namespace CSVTimeseries;

class Writer
{

    protected $path = '';
    protected $buffer = [];
    protected $maxBufferLength = 999;
    protected $registered = false;

    public function __construct()
    {
        $this->path = '/var/tmp';
    }
    
    public function to($path)
    {
        $this->path = $path;
    }
    
    public function me()
    {
        return $this;
    }

    public function add($data)
    {
        $ts = new \DateTime;
        array_unshift($data, $ts->format("c"));

        $day = $ts->format("Ymd");
        if (!isset($this->buffer[$day])) {
            $this->buffer[$day] = [];
        }
        $this->buffer[$day][] = $data;
        
        if (count($this->buffer[$day]) > $this->maxBufferLength) {
            $this->save();
        }

        if (!$this->registered) {
            $this->registered = true;
            $obj = $this->me();
            register_shutdown_function(function() use ($obj) {
                $obj->save();
            });
        }
    }

    public function save()
    {
        foreach ($this->buffer as $day => &$buffer) {
            $year = substr($day, 0, 4);
            $path = rtrim($this->path, '/') . '/' . $year;
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $filePath = $path . '/' . $day . '.csv';
            $r = fopen($filePath, 'a');
            flock($r, LOCK_EX);
            foreach ($buffer as $line) {
                fputcsv($r, $line);
            }
            flock($r, LOCK_UN);
            fclose($r);
            $buffer = [];
        }
    }
    
    public function getBufferLength()
    {
        $acc = 0;
        foreach ($this->buffer as $day => &$buffer) {
            $acc += count($buffer);
        }
        return $acc;
    }

}
