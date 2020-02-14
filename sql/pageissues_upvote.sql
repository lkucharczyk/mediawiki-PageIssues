CREATE TABLE /*_*/pageissues_upvote (
    piu_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    piu_actor INT NOT NULL,
    piu_page INT NOT NULL,
    piu_timestamp BINARY(14) NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/piu_actor ON /*_*/pageissues_upvote (piu_actor,piu_page);
CREATE UNIQUE INDEX /*i*/piu_page ON /*_*/pageissues_upvote (piu_page,piu_actor);
