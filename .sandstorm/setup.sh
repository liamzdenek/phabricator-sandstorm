#!/bin/bash

# When you change this file, you must take manual action. Read this doc:
# - https://docs.sandstorm.io/en/latest/vagrant-spk/customizing/#setupsh

set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y nginx php5-fpm php5-mysql php5-cli php5-curl git php5-dev php5-gd php5-apcu mysql-server python-pygments
service nginx stop
service php5-fpm stop
service mysql stop
systemctl disable nginx
systemctl disable php5-fpm
systemctl disable mysql
# patch /etc/php5/fpm/pool.d/www.conf to not change uid/gid to www-data
sed --in-place='' \
        --expression='s/^listen.owner = www-data/;listen.owner = www-data/' \
        --expression='s/^listen.group = www-data/;listen.group = www-data/' \
        --expression='s/^user = www-data/;user = www-data/' \
        --expression='s/^group = www-data/;group = www-data/' \
        /etc/php5/fpm/pool.d/www.conf
# patch /etc/php5/fpm/php-fpm.conf to not have a pidfile
sed --in-place='' \
        --expression='s/^pid =/;pid =/' \
        /etc/php5/fpm/php-fpm.conf
# patch /etc/php5/fpm/pool.d/www.conf to no clear environment variables
# so we can pass in SANDSTORM=1 to apps
sed --in-place='' \
        --expression='s/^;clear_env = no/clear_env=no/' \
        /etc/php5/fpm/pool.d/www.conf
# patch mysql conf to not change uid
sed --in-place='' \
        --expression='s/^user\t\t= mysql/#user\t\t= mysql/' \
        /etc/mysql/my.cnf
# patch mysql conf to use smaller transaction logs to save disk space
cat <<EOF > /etc/mysql/conf.d/sandstorm.cnf
[mysqld]
# Set the transaction log file to the minimum allowed size to save disk space.
innodb_log_file_size = 1048576
# Set the main data file to grow by 1MB at a time, rather than 8MB at a time.
innodb_autoextend_increment = 1

max_allowed_packet = 33554432

sql_mode = STRICT_ALL_TABLES
ft_stopword_file=/opt/app/phabricator/resources/sql/stopwords.txt
ft_min_word_len=3
ft_boolean_syntax=' |-><()~*:""&^'
innodb_buffer_pool_size=1600M
EOF
ln -s /usr/lib/git-core/git-http-backend /usr/bin

# Give pygmentize a homedir so Python doesn't freak out
cat <<EOF > /usr/local/bin/pygmentize
#!/bin/bash

exec env HOME=/var/home /usr/bin/pygmentize \$@
EOF
chmod a+x /usr/local/bin/pygmentize

# Install custom PHP settings for Phabricator
cp /opt/app/.sandstorm/service-config/php.conf/* /etc/php5/fpm/conf.d/
