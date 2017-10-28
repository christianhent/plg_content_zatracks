CREATE TABLE IF NOT EXISTS `#__zatracks` (
  `content_id` int(11) NOT NULL,
  `context` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `activity` tinyint(3) NOT NULL DEFAULT '1',
  `geojson` longtext CHARACTER SET utf8 NOT NULL,
  `polyline` longtext CHARACTER SET utf8 NOT NULL,
  `distance` decimal(5,2) NOT NULL,
  `duration` bigint(20) NOT NULL,
  `starttime` datetime NOT NULL,
  `avs` decimal(4,2) NOT NULL,
  `strava_activity_id` bigint(20) NOT NULL,
  `min_elevation` int(5) NOT NULL,
  `max_elevation` int(5) NOT NULL,
  `elevation_gain` int(5) NOT NULL,
  `elevation_loss` int(5) NOT NULL,
  `custom` varchar(255) NOT NULL
);