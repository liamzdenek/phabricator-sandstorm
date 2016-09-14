<?php
class UserMapper extends PhabricatorLiskDAO {
    protected $id;  
    protected $ss_id;
    protected $phabricator_user_phid;

    public function getApplicationName() {
        return 'user';
    }

    protected function getConfiguration() {
        return array(
            self::CONFIG_COLUMN_SCHEMA => array(
                'ss_id' => 'text32',
                'phabricator_user_phid' => 'phid?',
            ),
            self::CONFIG_KEY_SCHEMA => array(
                'key_ss_id' => array(
                    'columns' => array('ss_id'),
                    'unique' => true,
                ),
            ),
        ) + parent::getConfiguration();
    }
}
