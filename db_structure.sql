--
-- Table structure for table `servers`
--

CREATE TABLE IF NOT EXISTS `servers` (
`id` int(10) unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `hostname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `port` int(5) NOT NULL,
  `username` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `root_path` varchar(200) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `servers`
--
ALTER TABLE `servers`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `servers`
--
ALTER TABLE `servers`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

--
-- Table structure for table `repositories`
--

CREATE TABLE IF NOT EXISTS `repositories` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(200) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `repositories`
--
ALTER TABLE `repositories`
ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `repositories`
--
ALTER TABLE `repositories`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;

--
-- Table structure for table `deployments`
--
CREATE TABLE IF NOT EXISTS `deployments` (
  `id` int(10) unsigned NOT NULL,
  `server_id` int(10) unsigned NOT NULL DEFAULT '0',
  `repository_id` int(10) unsigned NOT NULL DEFAULT '0',
  `branch` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `target_path` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `deployments`
--
ALTER TABLE `deployments`
ADD PRIMARY KEY (`id`), ADD KEY `server_id` (`server_id`,`repository_id`);

--
-- AUTO_INCREMENT for table `deployments`
--
ALTER TABLE `deployments`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;