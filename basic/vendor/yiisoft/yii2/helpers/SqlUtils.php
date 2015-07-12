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
    
    public static function updateTagsTable(){
        $db= Yii::$app->db;
        $sql_1 = "ALTER TABLE tags ADD events_count INT;";
        $sql_2 = "ALTER TABLE tags ENGINE=InnoDB;";
        $command_1 = $db->createCommand($sql_1)->execute();
        $command_2 = $db->createCommand($sql_2)->execute();
    }
    
    public static function createCommentsTable(){
        $db= Yii::$app->db;
        $sql_1 = "DROP TABLE IF EXISTS `comments`";
        $sql_2 ="CREATE TABLE IF NOT EXISTS comments(
                comment_id INT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
                event_id int,
                date int,
                comment_text text,
                user_id int,
                INDEX comments_event_id(event_id)
                )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
         $command_1 = $db->createCommand($sql_1)->execute();
         $command_2 = $db->createCommand($sql_2)->execute();  
            
        
    }
    public static function createSubscribersTable(){
        $db= Yii::$app->db;
        $sql_1 = "DROP TABLE IF EXISTS `subscribers`";
        $sql_2 ="CREATE TABLE IF NOT EXISTS subscribers(
                event_id int,
                user_id int,
                INDEX subscribers_event_id (event_id),
                INDEX event_id_user_id(user_id)
                )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
         $command_1 = $db->createCommand($sql_1)->execute();
         $command_2 = $db->createCommand($sql_2)->execute();  
            
        
    }
    public static function createTagsTable(){
        $db= Yii::$app->db;
        $sql_1 = "DROP TABLE IF EXISTS `tags`";
        $sql_2 ="CREATE TABLE IF NOT EXISTS tags(
                tag_id INT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
                tag_name varchar(255),
                events_count int,
                INDEX tag_name (tag_name)
                )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
         $command_1 = $db->createCommand($sql_1)->execute();
         $command_2 = $db->createCommand($sql_2)->execute();  
            
        
    }
    
    public static function createTagsEventsTable(){
        $db= Yii::$app->db;
        $sql_1 = "DROP TABLE IF EXISTS `tags_events`";
        $sql_2 ="CREATE TABLE IF NOT EXISTS tags_events(
                tag_id INT,
                event_id int,
                INDEX tag_id (tag_id)
                )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
         $command_1 = $db->createCommand($sql_1)->execute();
         $command_2 = $db->createCommand($sql_2)->execute();  
    }
    
    public static function regenerateDb(){
        self::createEventsTable();
        self::createCommentsTable();
        self::createSubscribersTable();
        self::createTagsTable();
        self::createTagsEventsTable();
        return true;
    }
}