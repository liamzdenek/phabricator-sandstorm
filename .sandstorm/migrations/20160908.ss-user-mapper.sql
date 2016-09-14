use {$NAMESPACE}_user;

CREATE TABLE user_mapper (
     id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
    ,ss_id VARCHAR(32) NOT NULL
    ,phabricator_user_phid VARBINARY(64) NOT NULL
    ,dateCreated int(10) unsigned NOT NULL
    ,dateModified int(10) unsigned NOT NULL
    ,PRIMARY KEY(id)
    ,UNIQUE `key_ss_id` (ss_id)
);
