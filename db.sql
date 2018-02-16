# {"time" : "2018-01-13 12:09:30", "model" : "Oil Watchman", "id" : 145433412, "flags" : 128, "maybetemp" : 26, "temperature_C" : 5.000, "binding_countdown" : 0, "depth" : 118}

DROP TABLE IF EXISTS `oiltank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oiltank` (
  `cap_time` datetime NOT NULL,
  `temperature` float DEFAULT NULL,
  `depth` float DEFAULT NULL,
  PRIMARY KEY (`cap_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `temperature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temperature` (
  `cap_time` datetime NOT NULL,
  `cap_id` char(20) NOT NULL,
  `temperature` float DEFAULT NULL,
  `battery` char(20) DEFAULT NULL,
  PRIMARY KEY (`cap_time`,`cap_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


