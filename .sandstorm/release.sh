#!/bin/bash

#config
cp /opt/app/.sandstorm/service-config/local.json /opt/app/phabricator/conf/local/

#migrations
cp /opt/app/.sandstorm/migrations/* /opt/app/phabricator/resources/sql/autopatches/

#phutil
cd /opt/app/libphutil/src/extensions
cp /opt/app/.sandstorm/src-new/PhutilSandstormAuthAdapter.php . # /opt/app/libphutil/src/auth/ #replaced file

#phabricator
cd /opt/app/phabricator/src/extensions
cp /opt/app/.sandstorm/src-new/PhabricatorSandstormAuthProvider.php . # /opt/app/phabricator/src/applications/auth/provider/ # new file
cp /opt/app/.sandstorm/src-new/PhabricatorBarePageView.php . # /opt/app/phabricator/src/view/page/PhabricatorBarePageView.php # replaced file
cp /opt/app/.sandstorm/src-new/AphrontArbitraryScript.php . # /opt/app/phabricator/src/view/AphrontArbitraryScript.php # new file
