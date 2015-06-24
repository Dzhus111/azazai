<?php
namespace yii\helpers;
use Yii;
class SqlUtils{
    public static function createEventsTable(){
        $db= Yii::$app->db;
        $sql_1 = "DROP TABLE IF EXISTS `events`";
        $sql_2 ="CREATE TABLE IF NOT EXISTS events(
                event_id INT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
                event_name varchar(255),
                description varchar(255),
                address text,
                required_people_number int,
                subscribers_count int,
                created_date int,
                meeting_date int,
                search_text text,
                status tinyint(1),
                user_id int,
                FULLTEXT search (search_text),
                INDEX evn_idx (status, meeting_date)

                )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
         $command_1 = $db->createCommand($sql_1)->execute();
         $command_2 = $db->createCommand($sql_2)->execute();  
            
        
    }
}