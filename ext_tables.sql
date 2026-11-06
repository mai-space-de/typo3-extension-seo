--
-- Table structure additions for ext:mai_seo
--

CREATE TABLE pages (
    tx_maiseo_structured_data mediumtext DEFAULT NULL,
    tx_maiseo_og_title varchar(255) DEFAULT '' NOT NULL,
    tx_maiseo_og_description text,
    tx_maiseo_schema_type varchar(64) DEFAULT '' NOT NULL
);

CREATE TABLE tx_maiseo_analytics_hit (
    uid int(11) unsigned NOT NULL auto_increment,
    pid int(11) unsigned DEFAULT 0 NOT NULL,
    tstamp int(11) unsigned DEFAULT 0 NOT NULL,
    site varchar(64) DEFAULT '' NOT NULL,
    language_id int(11) DEFAULT 0 NOT NULL,
    locale varchar(35) DEFAULT '' NOT NULL,
    path varchar(512) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    referrer_host varchar(255) DEFAULT '' NOT NULL,
    channel varchar(32) DEFAULT '' NOT NULL,
    device varchar(16) DEFAULT '' NOT NULL,
    browser varchar(32) DEFAULT '' NOT NULL,
    visitor_hash varchar(64) DEFAULT '' NOT NULL,
    hit_token varchar(36) DEFAULT '' NOT NULL,
    duration int(11) unsigned DEFAULT 0 NOT NULL,

    PRIMARY KEY (uid),
    KEY site_tstamp (site, tstamp),
    KEY page_tstamp (pid, tstamp),
    KEY hit_token (hit_token)
);
