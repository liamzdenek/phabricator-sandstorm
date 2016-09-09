<?php
class UserMapper extends PhabricatorLiskDAO {
    protected $id;  
    protected $ss_id;
    protected $phabricator_user_id;

    public function getApplicationName() {
        return 'user';
    }
}
