
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `mtlda_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtlda_archive` (
  `archive_idx` int(11) NOT NULL AUTO_INCREMENT,
  `archive_guid` varchar(255) DEFAULT NULL,
  `archive_file_name` varchar(255) DEFAULT NULL,
  `archive_file_hash` varchar(255) DEFAULT NULL,
  `archive_file_size` int(11) DEFAULT NULL,
  `archive_signing_icon_position` int(11) DEFAULT NULL,
  `archive_time` int(11) DEFAULT NULL,
  `archive_version` varchar(255) DEFAULT NULL,
  `archive_derivation` int(11) DEFAULT NULL,
  `archive_derivation_guid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`archive_idx`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtlda_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtlda_queue` (
  `queue_idx` int(11) NOT NULL AUTO_INCREMENT,
  `queue_guid` varchar(255) DEFAULT NULL,
  `queue_file_name` varchar(255) DEFAULT NULL,
  `queue_file_hash` varchar(255) DEFAULT NULL,
  `queue_file_size` int(11) DEFAULT NULL,
  `queue_signing_icon_position` int(11) DEFAULT NULL,
  `queue_state` varchar(255) DEFAULT NULL,
  `queue_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`queue_idx`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
