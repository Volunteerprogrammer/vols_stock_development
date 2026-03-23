<?php
    function connectandconfigure(&$db,&$config,&$configmanager,$trace=false) {
        if ($trace) { echo "Enter ".__METHOD__."<br>"; }
            // config.php contains the setting required to connect to the database, and the remaining config settings
            // are stored in the config table in the database. 
            // We initially create the $config array from the config.php file so we can connect...
        $config["app"] = parse_ini_file(sprintf("%sconfig%sconfig.php",APP_DIR,DS));
        makedatabaseconnection($db,$config["app"],$trace);// throws an exception if it fails
            // ... then complete it from the db once connected. 
            // However there's a three-way catch22 here - normally the database requires the errorhandler, but 
            // errorhandler->init() requires the complete config settings, which are retrieved from the db. 
            // So...
            // To retrieve the config data, $configmanager->getconfigdata() calls configtable->selectall(), passing "true" as the 
            // argument "$noerrorhandler". This argument disables all calls to the errorhandler during the execution of this query,
            // allowing $db to complete the creation of $config["app"]... 
        $configmanager->setdb($db);
        getdbconfigarray($config,$configmanager,$trace);
        if ($trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    function makedatabaseconnection (&$db,$config,$trace=false) {
        if ($trace) { echo "Enter ".__METHOD__."<br>"; }
        if (!(empty($config["DBHOST"]) && empty($config["DBUSERNAME"]) && empty($config["DBNAME"])))  {
            $db->connect( $config["DBHOST"],
                          $config["DBUSERNAME"],
                          $config["DBPASSWORD"],
                          $config["DBNAME"]);
        } else {
            if ($this->trace) { echo "Leave ".__METHOD__."<br>"; }
            throw new \Exception("Database configuration is incomplete.");
        }
        if ($trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    function getdbconfigarray(&$config,$configmanager,$trace=false) {
        if ($trace) { echo "Enter ".__METHOD__."<br>"; }
        $configmanager->getconfigdata($data,"",false,$noerrorhandler = true);
        foreach ($data as $record) {
            $config["app"][$record["name"]] = $record["value"];
        }
        if ($trace) { echo "Leave ".__METHOD__."<br>"; }
     }
?>
