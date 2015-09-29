CREATE TABLE IF NOT EXISTS staging.wikidata_getclaims_property_use
  (
     date     DATE NOT NULL,
     property VARCHAR(6) NOT NULL,
     count    INT(12)
  );