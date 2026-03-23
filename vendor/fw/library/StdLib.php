<?php //stdlibrary
namespace lib;

class StdLib {
    public static $nl = "<br> \n";    
    public static function capsToUnderscores($str) {
        // change a Capitalised string to all lowercase with underscores between words
        // e.g.  "PageAccess" becomes "page_access"  
        $str = lcfirst($str);
        $return =  preg_replace_callback('/[A-Z]/',function($match) { return "_".strtolower($match[0]); },$str);
        return trim($return);
     }
    public static function getLastToken($str,$delim) {
        $tokens = explode($delim,$str);
        return end($tokens);
      }
    public static function cpc_urlencode_spaces($url) { // echo the new line
        return str_replace(" ","%20",$url);
      }
    public static function dashesToCamelCase($string, $capitalizeFirstCharacter = false) {
        $str = str_replace('-', '', ucwords($string, '-'));
        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
         }
        return $str;
      }
    public static function e(...$args) { // echo then new line
        $dat = "";
        foreach ($args as $arg){
            $dat .= $arg."|";
         }
        echo $dat."<br>\n";
      }
    public static function ed(...$args) {  // e() then die
        StdLib::e(...$args);
        die();
     }
    public static function eob(...$args) { // return e()'s output
        ob_start();
        StdLib::e(...$args);
        return ob_get_clean();
     }
    public static function v(...$args) {   // var_dump
        echo '<pre>';
        foreach ($args as $arg){
            var_dump($arg);
            echo "<br>\n";
         }
        echo '</pre>';
     }
    public static function vd(...$args) {   // var_dump then die
        StdLib::v(...$args);
        die();
     }
    public static function vdb(...$args) {  // var_dump then die (with backtrace)
        var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        StdLib::v(...$args);
        die();
     }
    public static function vob(...$args) { // var_dump into buffer then return buffer - no output
        ob_start();
        StdLib::v($args);
        return ob_get_clean();
     }
    public static function pr(...$args) {   // print_r
        
        echo '<style>pre {margin: 0;}</style><pre>';
        foreach ($args as $arg){
            echo print_r($arg,true)."<br />\n";
        }
        echo '</pre>';
     }
    public static function prf($pattern=[],$replacement=[],...$args) {   // print_r
        echo '<pre>';
        foreach ($args as $arg){
            $str = trim(print_r($arg,true));
            if (count($pattern) && count($replacement)) {
                $str = preg_replace($pattern,$replacement,$str);
            }
            print_r ($str."<br>\n");
         }
        echo '</pre>';
     }
    public static function prd(...$args) {   // print_r then die
        StdLib::pr(...$args);
        die();
     }
    public static function prdb(...$args) {  // print_r then die (with backtrace)
        var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
        StdLib::pr(...$args);
        die();
     }
    public static function prob(...$args) { // print_r into buffer then return buffer - no output
        ob_start();
        StdLib::pr($args);
        return ob_get_clean();
     }
    public static function generateCallTrace($len=0) {
        $e = new \Exception();
        $trace = explode("\n", $e->getTraceAsString());
        // reverse array to make steps line up chronologically
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_shift($trace); // remove {index.php}
        array_pop($trace); // remove call to this method
        $length = count($trace);
        $length = (($length > $len) && ($len > 0)) ? $len : $length;
        $result = array();
        for ($i = 0; $i < $length; $i++)
        {
            $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
         }
        $result = str_replace('C:\xampp\htdocs\vols\\',"",$result);
        echo  "<br>".implode("<br>", $result)."<br><br>";
     }
    public static function cleanup(&$data,$trimonly=false) { 
        //string cleansing before posting to database
        $data = trim($data);
        if (!$trimonly) {
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
         }
        return $data;
     }
    public static function cpc_jsonerrors(){
        switch (json_last_error()) {
            case JSON_ERROR_NONE:           return   'JSON - No errors<BR/>';break;
            case JSON_ERROR_DEPTH:          return   'JSON - Maximum stack depth exceeded<BR/><BR/>';break;
            case JSON_ERROR_STATE_MISMATCH: return   'JSON - Underflow or the modes mismatch<BR/><BR/>';break;
            case JSON_ERROR_CTRL_CHAR:      return   'JSON - Unexpected control character found<BR/><BR/>';break;
            case JSON_ERROR_SYNTAX:         return   'JSON - Syntax error, malformed JSON<BR/><BR/>';break;
            case JSON_ERROR_UTF8:           return   'JSON - Malformed UTF-8 characters, possibly incorrectly encoded<BR/><BR/>';break;
            default:                        return   'JSON - Unknown error<BR/><BR/>';break;
         }
     }
    public static function getGUID(){
        if (function_exists('com_create_guid')) {
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
    public static function stripascii160($text)    {
        $text = trim($text);
        // when there's no in $_POST[$var], seems it returns a single character ascii 160! (&nbsp;)
        while (substr($text ,0,1) == "\xA0") $text = substr ($text ,1);
        return $text; 
     }

    public static function emptyarrayvals(&$array){
        foreach ($array as &$value) {
            switch(gettype($value)) {
                case "boolean":$value = false;break;
                case "integer":$value = 0;break;
                case "double" :$value = 0;break;
                case "string":$value = "";break;
                case "array":$value = [];break;
                case "object":$value = (object) [];break;
                case "NULL":$value = null;break;
                case "unknown type":$value = "";break;
                default:$value = "";
            }
        }
    } 
    public static function array_orderby()
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
    public static function array_2Dfindmatchingrows()
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
    public static function array_2Dsearch($outerarray,$field,$target,&$foundkey) { 
      // returns the $key of the first row in $outerarray that contains $target in $field
      // returns false if value is not found. 
      // beware: can return $key = 0 or false, which are NOT the same, so test result thus: if ($result === false)...
        if (is_array($outerarray)) {  
            foreach ($outerarray as $key=>$row) {
                if (is_array($row))  {
                    if (isset($row[$field])) {
                        if ($row[$field] == $target) {
                            $foundkey = $key;
                            return $key;
                         }   
                     }
                 }  
             }
         }
        return false;
     }
    public static function array_2D_to_string($delim1,$delim2,$array) {
      return implode($delim1,array_map(function($a){return implode(",",$a);},$array));
     }
    public static function getNextArrayVal(&$array, $curr_val)  {
        // note could fail if the array holds Boolean FALSE values
        reset($array);
        $next = current($array);
        do {
            $tmp_val = current($array);
            $res = next($array);
        } while ( ($tmp_val != $curr_val) && $res !== false);
        if( $res !== false ) {
            $next = current($array);
        }
        return $next;
     }
    public static function getPrevArrayVal(&$array, $curr_val) {
        // note could fail if the array holds Boolean FALSE values
        end($array);
        $prev = current($array);
        do {
            $tmp_val = current($array);
            $res = prev($array);
        } while ( ($tmp_val != $curr_val) && $res );
        if( $res ) {
            $prev = current($array);
        }
        return $prev;
     }
    public static function deleteArrayCol(&$array, $key) {
       return array_walk($array, function (&$row) use ($key) {
            unset($row[$key]);
        });
     } 

    public static function nowf($f=""){
        $format = $f !== "" ? $f : 'y-m-d H:i:s'; 
        $text =(string) date($format);
        return $text;
     }
    public static function time_diff($dt1,$dt2,$unit='s'){
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
    public static function gotodow($date,$dow=0,$dateformat="")  {
        // $date is a DateTime obj
        $datedow = (int) date('w', strtotime($date->format('Y-m-d')));
        $shift = ($dow - $datedow);
        $result = $date->modify("{$shift} days ");
        if ($dateformat !== "") { // convert to a string
            $result = $result->format($dateformat);
        }
        return $result;
     }
    public static function validateDate($date,$format="Y-m-d") {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
     }
    public static function newdatetime($datetime='',$timezone=null) {   
        $timezone = empty($timezone)?date_default_timezone_get():$timezone;
        return new \DateTime($datetime, new \DateTimeZone($timezone) );
     }
    public static function datetimestring($datetime,$format='') {   global $siteglobals;
        return $datetime->format(empty($format)?$siteglobals["DATETIMEFORMAT"]:$format);   
     }  
    public static function nowstring($format='',$timezone=null) {   global $siteglobals,$now;
        if (empty($now)) $now = new \datetime('',$timezone);
        return $now->format(empty($format)?$siteglobals["DATETIMEFORMAT"]:$format);   
     }  
    public static function datetime_diff($dt1,$dt2,$unit='s'){
        //returns the difference between (DateTime objects) dt1 and dt2 in seconds
        // if unit is provided, i=mins,H=hours,d=days,w=weeks 
      // echo "'".$dt1."'".$dt2."'";
        return StdLib::time_diff($dt1->format("Y-m-d H:i:s"),$dt2->format("Y-m-d H:i:s"),$unit);
     }

    public static function guinowstring($format='',$timezone=NULL) {
       global  $siteglobals,$now;
        if (empty($now)) $now = new \datetime('',$timezone);
        return $now->format(empty($format)?$siteglobals["GUIDATETIMEFORMAT"]:$format);   
     }
    public static function getnexttokeninstring(&$source,&$token,$delim=" "){
        $tpos = 0;
        $source = trim($source);
        $tpos = strpos($source,$delim);
        if ($tpos !== false) {
            $token = substr($source,0,$tpos);
            $source = trim(substr($source,$tpos+strlen($delim)));
        } else {
            $token = trim($source);
            $source = "";
        }
     }
    public static function NumberOfSetBits($i) {return strlen(str_replace("0","",decbin($i))); }
    public static function nthSetBitPosition($int,$n,$hbf=false) { 
        // decbin returns high bit first so may need strrev()
        $iarray = explode("1",($hbf?decbin($int):strrev(decbin($int))),$n+1);
        $pos = 0;
        for ($p = 0; $p<=$n;$p++) {
            $pos += strlen($iarray[$p])+1;
        }
        return $pos;
     }

}  
