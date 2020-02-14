CREATE TABLE /*_*/pageissues_report (
    pir_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    pir_actor INT NOT NULL,
    pir_page INT NOT NULL,
    pir_revision INT NOT NULL,
    pir_issues BLOB NOT NULL,
    pir_note BLOB NOT NULL,
    pir_timestamp VARBINARY(14) NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/pir_actor ON /*_*/pageissues_report (pir_actor);
CREATE INDEX /*i*/pir_page ON /*_*/pageissues_report (pir_page);
