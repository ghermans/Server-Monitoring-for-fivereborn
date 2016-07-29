-- phpMyAdmin SQL Dump
-- version 4.6.3
-- https://www.phpmyadmin.net/
--
-- Host: dd32100
-- Erstellungszeit: 29. Jul 2016 um 17:26
-- Server-Version: 5.5.50-nmm1-log
-- PHP-Version: 5.5.38-nmm1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `d022beea`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_gametype`
--

CREATE TABLE `statistics_gametype` (
  `id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_map`
--

CREATE TABLE `statistics_map` (
  `id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_server`
--

CREATE TABLE `statistics_server` (
  `id` int(11) NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NULL DEFAULT NULL,
  `name` varchar(64) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `port` int(5) NOT NULL,
  `gametype` int(11) DEFAULT NULL,
  `map` int(64) DEFAULT NULL,
  `maxclients` int(4) NOT NULL,
  `is_online` tinyint(1) NOT NULL,
  `is_updating` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_server_entries`
--

CREATE TABLE `statistics_server_entries` (
  `serverid` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `online` tinyint(1) NOT NULL,
  `ping` int(11) NOT NULL,
  `clients` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_server_lifetime`
--

CREATE TABLE `statistics_server_lifetime` (
  `serverid` int(11) NOT NULL,
  `up_seconds` int(11) NOT NULL DEFAULT '0',
  `down_seconds` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_server_map`
--

CREATE TABLE `statistics_server_map` (
  `serverid` int(11) NOT NULL,
  `mapid` int(11) NOT NULL,
  `minutes` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_server_map_changes`
--

CREATE TABLE `statistics_server_map_changes` (
  `serverid` int(11) NOT NULL,
  `mapid` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_server_player`
--

CREATE TABLE `statistics_server_player` (
  `serverid` int(11) NOT NULL,
  `playerid` int(11) NOT NULL,
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_server_records`
--

CREATE TABLE `statistics_server_records` (
  `serverid` int(11) NOT NULL,
  `maxclients` int(4) DEFAULT NULL,
  `minping` int(11) DEFAULT NULL,
  `maxping` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `statistics_variables`
--

CREATE TABLE `statistics_variables` (
  `name` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `value` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `statistics_variables`
--

INSERT INTO `statistics_variables` (`name`, `type`, `value`) VALUES
('delete_entries', 'interval', '3600'),
('delete_entries', 'last_update', '2000-01-01 00:00:00'),
('delete_entries', 'time', '86400'),
('http_request', 'timeout', '5'),
('udp_connection', 'timeout', '1'),
('udp_response', 'timeout', '2'),
('update_server', 'interval', '30'),
('update_server', 'last_update', '2000-01-01 00:00:00'),
('update_serverlist', 'interval', '300'),
('update_serverlist', 'last_update', '2000-01-01 00:00:00'),
('update_server_amount', 'limit', '30');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `statistics_gametype`
--
ALTER TABLE `statistics_gametype`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indizes für die Tabelle `statistics_map`
--
ALTER TABLE `statistics_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indizes für die Tabelle `statistics_server`
--
ALTER TABLE `statistics_server`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gametype` (`gametype`),
  ADD KEY `map` (`map`);

--
-- Indizes für die Tabelle `statistics_server_entries`
--
ALTER TABLE `statistics_server_entries`
  ADD PRIMARY KEY (`serverid`,`timestamp`);

--
-- Indizes für die Tabelle `statistics_server_lifetime`
--
ALTER TABLE `statistics_server_lifetime`
  ADD PRIMARY KEY (`serverid`);

--
-- Indizes für die Tabelle `statistics_server_map`
--
ALTER TABLE `statistics_server_map`
  ADD PRIMARY KEY (`serverid`,`mapid`),
  ADD KEY `mapid_fk_map` (`mapid`);

--
-- Indizes für die Tabelle `statistics_server_map_changes`
--
ALTER TABLE `statistics_server_map_changes`
  ADD PRIMARY KEY (`serverid`,`mapid`,`timestamp`),
  ADD KEY `mapid_fk_mapchanges` (`mapid`);

--
-- Indizes für die Tabelle `statistics_server_player`
--
ALTER TABLE `statistics_server_player`
  ADD PRIMARY KEY (`serverid`,`name`);

--
-- Indizes für die Tabelle `statistics_server_records`
--
ALTER TABLE `statistics_server_records`
  ADD PRIMARY KEY (`serverid`);

--
-- Indizes für die Tabelle `statistics_variables`
--
ALTER TABLE `statistics_variables`
  ADD PRIMARY KEY (`name`,`type`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `statistics_gametype`
--
ALTER TABLE `statistics_gametype`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT für Tabelle `statistics_map`
--
ALTER TABLE `statistics_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;
--
-- AUTO_INCREMENT für Tabelle `statistics_server`
--
ALTER TABLE `statistics_server`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=333;
--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `statistics_server`
--
ALTER TABLE `statistics_server`
  ADD CONSTRAINT `gametypeid_fk_server` FOREIGN KEY (`gametype`) REFERENCES `statistics_gametype` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mapid_fk_server` FOREIGN KEY (`map`) REFERENCES `statistics_map` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `statistics_server_entries`
--
ALTER TABLE `statistics_server_entries`
  ADD CONSTRAINT `serverid_fk_entries` FOREIGN KEY (`serverid`) REFERENCES `statistics_server` (`id`);

--
-- Constraints der Tabelle `statistics_server_lifetime`
--
ALTER TABLE `statistics_server_lifetime`
  ADD CONSTRAINT `serverid_fk_lifetime` FOREIGN KEY (`serverid`) REFERENCES `statistics_server` (`id`);

--
-- Constraints der Tabelle `statistics_server_map`
--
ALTER TABLE `statistics_server_map`
  ADD CONSTRAINT `mapid_fk_map` FOREIGN KEY (`mapid`) REFERENCES `statistics_map` (`id`),
  ADD CONSTRAINT `serverid_fk_map` FOREIGN KEY (`serverid`) REFERENCES `statistics_server` (`id`);

--
-- Constraints der Tabelle `statistics_server_map_changes`
--
ALTER TABLE `statistics_server_map_changes`
  ADD CONSTRAINT `mapid_fk_mapchanges` FOREIGN KEY (`mapid`) REFERENCES `statistics_map` (`id`),
  ADD CONSTRAINT `serverid_fk_mapchanges` FOREIGN KEY (`serverid`) REFERENCES `statistics_server` (`id`);

--
-- Constraints der Tabelle `statistics_server_player`
--
ALTER TABLE `statistics_server_player`
  ADD CONSTRAINT `players_fk_serverid` FOREIGN KEY (`serverid`) REFERENCES `statistics_server` (`id`);

--
-- Constraints der Tabelle `statistics_server_records`
--
ALTER TABLE `statistics_server_records`
  ADD CONSTRAINT `serverid_fk_records` FOREIGN KEY (`serverid`) REFERENCES `statistics_server` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
