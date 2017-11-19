CREATE TABLE IF NOT EXISTS `#__zatracks` (
  `content_id` int(11) NOT NULL,
  `context` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `activity` tinyint(3) NOT NULL DEFAULT '1',
  `geojson` longtext CHARACTER SET utf8 NOT NULL,
  `polyline` longtext CHARACTER SET utf8 NOT NULL,
  `distance` decimal(5,2) NOT NULL DEFAULT '0',
  `duration` bigint(20) NOT NULL DEFAULT '0',
  `starttime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `avs` decimal(4,2) NOT NULL DEFAULT '0',
  `strava_activity_id` bigint(20) NOT NULL DEFAULT '0',
  `min_elevation` int(5) NOT NULL DEFAULT '0',
  `max_elevation` int(5) NOT NULL DEFAULT '0',
  `elevation_gain` int(5) NOT NULL DEFAULT '0',
  `elevation_loss` int(5) NOT NULL DEFAULT '0',
  `custom` varchar(255) NOT NULL DEFAULT ''
);