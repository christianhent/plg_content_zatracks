CREATE TABLE IF NOT EXISTS `#__zatracks` (
  `content_id` int(11) NOT NULL,
  `context` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `activity` tinyint(3) NOT NULL DEFAULT '1',
  `geojson` mediumtext CHARACTER SET utf8 NOT NULL,
  `polyline` mediumtext CHARACTER SET utf8 NOT NULL,
  `distance` decimal(5,2) NOT NULL,
  `duration` bigint(20) NOT NULL,
  `starttime` datetime NOT NULL,
  `avs` decimal(4,2) NOT NULL,
  `strava_activity_id` bigint(20) NOT NULL,
  `custom` mediumtext CHARACTER SET utf8 NOT NULL
);