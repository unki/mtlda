<?php

require_once "config.php";

class MTDLA_Process_Incoming {

   public function __construct()
   {

   } // __construct()

   public function __destruct()
   {

   } // _destruct()

   function cleanup()
   {
      global $db;
      global $sth;

      try {
         if($sth) $sth->closeCursor();
      }
      catch (Exception $e) {
         $sth = NULL;
      }

      $db = NULL;

   } // cleanup()

   function handle_queue()
   {
      $options = array(
         'debug' => 2,
         'portability' => 'DB_PORTABILITY_ALL'
      );

      global $mysql_db;
      global $mysql_host;
      global $mysql_user;
      global $mysql_pass;

      $dsn = "mysql:dbname=". $mysql_db .";host=". $mysql_host;

      try {
         $db = new PDO($dsn, $mysql_user, $mysql_pass, array(
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
         ));
         $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
      }
      catch (PDOException $e) {
         print "Error!: " . $e->getMessage() . "<br/>";
         die();
      }

      $incoming_directory = "/home/unki/git/mtlda/data/incoming";
      $working_directory = "/home/unki/git/mtlda/data/working";

      $sth = $db->prepare("
         INSERT INTO mtlda_queue (
            queue_file_name,
            queue_file_hash,
            queue_state,
            queue_time
         ) VALUES (
            ?,
            ?,
            ?,
            ?
         )
      ");

      if (( $incoming = opendir($incoming_directory)) === false) {
         print "Error!: failed to access ". $incoming_directory;
         die();
      }

      while($file = readdir($incoming)) {

         $in_file = $incoming_directory ."/". $file;
         $work_file = $working_directory ."/". $file;

         if(!file_exists($in_file))
            continue;

         if(!is_file($in_file))
            continue;

         if(!is_readable($in_file))
            continue;

         if(($hash = sha1_file($in_file)) === false)
            continue;

         rename($in_file, $work_file);

         $sth->execute(array(
            $file,
            $hash,
            'new',
            time()
         ));

      }

   } // handle_queue()

} // class MTDLA_Process_Incoming

$queue = new MTDLA_Process_Incoming;
$queue->handle_queue();
$queue->cleanup();

?>
