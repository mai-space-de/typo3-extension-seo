--
-- Table structure additions for ext:mai_seo
--

CREATE TABLE pages (
    tx_maiseo_structured_data mediumtext DEFAULT NULL,
    tx_maiseo_og_title varchar(255) DEFAULT '' NOT NULL,
    tx_maiseo_og_description text,
    tx_maiseo_schema_type varchar(64) DEFAULT '' NOT NULL
);
