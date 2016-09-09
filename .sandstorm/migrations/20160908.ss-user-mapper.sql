use {$NAMESPACE}_user;

CREATE TABLE user_mapper (
     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
    ,ss_id VARCHAR(32)
    ,phabricator_user_id INT UNSIGNED NOT NULL
    ,dateCreated int(10) unsigned NOT NULL
    ,dateModified int(10) unsigned NOT NULL
    ,PRIMARY KEY(id)
    ,UNIQUE(ss_id)
);
