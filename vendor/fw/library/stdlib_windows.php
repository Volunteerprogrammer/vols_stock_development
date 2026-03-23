<?php //stdlibrary

    spl_autoload_register(function ($class) {
        $prefix = 'cypo\\';
        $base_dir = __DIR__;
        $base_dir = str_replace('library','',$base_dir);
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });

    
    function cleanup(&$data,$trimonly=false) { 
        //string cleansing before posting to database
        $data = trim($data);
        if (!$trimonly) {
              $data = stripslashes($data);
            $data = htmlspecialchars($data);
        }
        return $data;
    };

    function time_diff($dt1,$dt2,$unit='s'){
        //returns the difference between (string) dates dt1 and dt2 in seconds
        // $dt1 and $dt2 are assumed to be in the format yyyy-mm-dd hh:ii:ss    
        // $dt1 is assumed to be the later date. $dt2 is subtracted from $dt1
        // if unit is provided, i=mins,H=hours,d=days,w=weeks 
      // echo "'".$dt1."'".$dt2."'";
        $y1 = substr($dt1,0,4);
        $m1 = substr($dt1,5,2);
        $d1 = substr($dt1,8,2);
        $h1 = substr($dt1,11,2);
        $i1 = substr($dt1,14,2);
        $s1 = substr($dt1,17,2);   
        
        $y2 = substr($dt2,0,4);
        $m2 = substr($dt2,5,2);
        $d2 = substr($dt2,8,2);
        $h2 = substr($dt2,11,2);
        $i2 = substr($dt2,14,2);
        $s2 = substr($dt2,17,2);   

        $r1=date('U',mktime($h1,$i1,$s1,$m1,$d1,$y1));
        $r2=date('U',mktime($h2,$i2,$s2,$m2,$d2,$y2));
        $diff = $r1-$r2;
        switch ($unit) {
            case 'i': $diff = $diff/60; break;
            case 'H': $diff = $diff/(60*60); break;
            case 'd': $diff = $diff/(60*60*24); break;
            case 'w': $diff = $diff/(60*60*24*7); break;
        }
        return $diff;
    }

    function datetime_diff($dt1,$dt2,$unit='s'){
        //returns the difference between (DateTime objects) dt1 and dt2 in seconds
        // if unit is provided, i=mins,H=hours,d=days,w=weeks 
      // echo "'".$dt1."'".$dt2."'";
        return time_diff($dt1->format("Y-m-d H:i:s"),$dt2->format("Y-m-d H:i:s"),$unit);
    }


    function getGUID(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }

    function validateDate($date,$format)
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    function stripascii160($text)    {
        $text = trim($text);
        // when there's no in $_POST[$var], seems it returns a single character ascii 160!
        while (substr($text ,0,1) == "\xA0") $text = substr ($text ,1);
        return $text; 
    }
    function array_orderby()
    // Pass the array, followed by the column names and sort flags
    // e.g $sortedarray = array_orderby($dataarray, 'volume', SORT_DESC, 'edition', SORT_ASC);
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }


    function array_2Dfindmatchingrows()
    // Pass the array, followed by pairs of (columnname in the inner arrays and the target value)
    // e.g $searchedarray = array_2Dsearchy($array2D, 'domain_id', 5,"total",200);
    // returns all the rows in $args[0] that match the search criteria
    {
        $args = func_get_args();
        $outerarry = array_shift($args);// take the outer array off from the first arg
        if (is_array($outerarry)) {  
            while (count($args)) {
                $innercolumn= array_shift($args); //
                if (count($args)) {
                    $value  = array_shift($args);
                    if (isset($innercolumn) && isset($value)) {
			foreach ($outerarry as $key=>$row) {
                            if (!(is_array($row)))  {  
                               unset($outerarry[$key]);
                            } else if (!(isset($row[$innercolumn]))) { 
                               unset($outerarry[$key]);
                            } else if (!($row[$innercolumn]==$value)) { 
                               unset($outerarry[$key]);
                            } else  {
							}
                        }
                    }
                }  
            }
            return $outerarry;
        }
        return [];
    }

    function array_2Dsearch($outerarray,$field,$target)
    { // returns the $key of the first row in $outerarray that contains $value in $field
      // returns false if value is not found. 
      // beware: can return $key = 0 or false, which are NOT the same, so test result thus: if ($result === false)...
        if (is_array($outerarray)) {  
            foreach ($outerarray as $key=>$row) {
                if (is_array($row))  {
                    if (isset($row[$field])) {
                        if ($row[$field] == $target) {
                            return $key;
                        }   
                    }
                }  
            }
        }
        return false;
    }


    function newdatetime($time='',$timezone='')
    {   global  $siteglobals;
        return new DateTime($time, new DateTimeZone(empty($timezone)?$siteglobals["DEFAULTTIMEZONE"]:$timezone) );
    }
    function datetimestring($datetime,$format='',$timezone='')
    {   global $siteglobals;
        return $datetime->format(empty($format)?$siteglobals["DATETIMEFORMAT"]:$format);   
    }  
    function nowstring($format='',$timezone='')
    {   global $siteglobals,$now;
        if (empty($now)) $now = newdatetime('',$timezone);
        return $now->format(empty($format)?$siteglobals["DATETIMEFORMAT"]:$format);   
    }  
    function guinowstring($format='',$timezone='')
    {   global  $siteglobals,$now;
        if (empty($now)) $now = newdatetime('',$timezone);
        return $now->format(empty($format)?$siteglobals["GUIDATETIMEFORMAT"]:$format);   
    }  
