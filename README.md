# php-csv-timeseries

## Usage

    $writer = new \CSVTimeseries\Writer;
    $writer->to(__DIR__ . '/data/');
    $i = 1;
    while ($i < 1000) {
        $writer->add(['test', $i++]);
    }
    //it adds current timestamp for each record and creates single file per day, for example: "data/2020/20200931.csv"
    //save call is optional. script shutdown triggers it.
    $writer->save();


    $reader = new \CSVTimeseries\Reader;
    $reader->from(__DIR__ . '/data/');
    while ($line = $reader->next()) {
        //first cell is timestamp, for example: 2020-08-28T19:25:09+00:00
        $ts = array_shift($line);
        //print assigned data
        print_r($line);
    }