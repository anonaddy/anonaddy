# AnonAddy Self-Hosting Instructions

## Setting up the server

Choosing a provider (that you trust), Vultr, Greenhost, OVH, Hetzner, Linode, Cockbox (make sure the host allows post 25 to be used, some providers block it).

With Vultr you may need to open a support ticket and request for them to unblock port 25.

Before starting you will want to check the IP of your new server to make sure it is not on any blacklists - https://multirbl.valli.org/lookup/

If it is, destroy it and deploy a new one. You might notice that some providers such as Vultr have entire ranges of IPs listed.

You should have a fresh 18.04 Ubuntu server. I'm assuming that you have taken proper steps to secure the server (no root login, key auth only, 2FA, automatic security updates etc.).

Add Fail2ban, a Firewall (e.g UFW), make sure that ports 25, 22 (or whatever your SSH port is if you've changed it) 443 and 80 are open.

A good place to get started - https://github.com/imthenachoman/How-To-Secure-A-Linux-Server

https://jacyhong.wordpress.com/2016/06/27/my-first-10-minutes-on-a-server-primer-for-securing-ubuntu/

https://plusbryan.com/my-first-5-minutes-on-a-server-or-essential-security-for-linux-servers

I will be running all commands as a sudo user called `johndoe`. The domain used will be `example.com` and the hostname `mail.example.com`. I'll be using Vultr for this example (Note: if you also use Vultr for managing DNS records they do not currently support TSLA records required for DANE).

To check your server's hostname run:

```bash
hostname -f
```

If your hostname is not what it should be update it by running:

```bash
sudo hostnamectl set-hostname mail.example.com
```

Making sure to replace mail.example.com with your own domain.

## DNS records

Now let's add some basic DNS records.

We'll start with the MX record. This tells email sent to your domain where it should go.

```
MX @ mail.example.com
```

We want to direct it to our server's fully qualifed domain name (FQDN). Give it a priority of 10 (or just make sure it has the lowest priority if you have other MX records).

If you want to use wildcard subdomains e.g. (alias@username.example.com) then you also need to add a wildcard MX record:

```
MX * mail.example.com
```

This will tell email sent to any subdomain of example.com to go to the same place.

Add a wildcard A and AAAA (if using IPv6) record too if you want to use all subdomains (or just an A record for unsubscribe.example.com if not).

```
A * <Your-IPv4-address>
AAAA * <Your-IPv4-address>
```

If you want to just use the example.com domain and not bother with subdomains then you can skip the wildcard MX, A, AAAA records above (you will still need to add one for unsubscribe.example.com though to handle deactivating aliases).

Next we will add an explicit A record for the hostname `mail.example.com` and for where the web app will be located `app.example.com`

```
A mail.example.com <Your-IPv4-address>
A app.example.com <Your-IPv4-address>
```

If you are using IPv6 then you will also need to add an AAAA record

```
AAAA mail.example.com <Your-IPv6-address>
AAAA app.example.com <Your-IPv6-address>
```

Make sure to replace the placeholders above with the actual IP address of your server.

Now we need to set up the correct PTR record for reverse DNS lookups. This needs to be set as your FQDN (fully qualified domain name) which in our case is mail.example.com.

On your server run `host <Your-IPv4-address>` to check what it is.

You will likely need to login to your hosting provider to update your PTR record.

In Vultr you can update your reverse DNS by clicking on your server, then going to the settings tab, then IPv4 and click on the value in the "Reverse DNS" column.

Change it to `mail.example.com`. Don't forget to update this for IPv6 if you are using it too.

## Installing Postfix

Now we're going to install our MTA (mail transfer agent) Postfix.

```bash
sudo apt update
sudo apt install postfix
```
For configuration type select "Internet Site".

For System mail name: enter "example.com" note the missing mail subdomain.

Postfix should now begin installing.

If you would like to check the version of Postfix that you are running you can do:

```bash
sudo postconf mail_version
```

At the time of writing this I am running `mail_version = 3.3.0`.

We'll install an extension we will need later so that Postfix can query our database.

```bash
sudo apt install postfix-mysql
```

Now let's update our Postfix config file. A lot of the items in this file don't exist yet, but don't worry, we'll create them soon.

```bash
sudo vim /etc/postfix/main.cf
```

Or use nano if you like.

Replace the file contents with the following (replacing example.com with your own domain):

```cf
smtpd_banner = $myhostname ESMTP
biff = no

# appending .domain is the MUA's job.
append_dot_mydomain = no

readme_directory = no

# See http://www.postfix.org/COMPATIBILITY_README.html -- default to 2 on
# fresh installs.
compatibility_level = 2

# SMTPD
smtpd_tls_cert_file=/etc/nginx/conf.d/example.com.d/server.crt
smtpd_tls_key_file=/etc/nginx/conf.d/example.com.d/server.key
smtpd_use_tls=yes
smtpd_tls_session_cache_database = btree:${data_directory}/smtpd_scache
smtpd_tls_CApath = /etc/ssl/certs
smtpd_tls_security_level = may
smtpd_tls_protocols = !SSLv2, !SSLv3, !TLSv1
smtpd_tls_loglevel = 1
smtpd_tls_session_cache_database = btree:${data_directory}/smtpd_scache
smtpd_tls_mandatory_exclude_ciphers = MD5, DES, ADH, RC4, PSD, SRP, 3DES, eNULL, aNULL
smtpd_tls_exclude_ciphers = MD5, DES, ADH, RC4, PSD, SRP, 3DES, eNULL, aNULL
smtpd_tls_mandatory_protocols = !SSLv2, !SSLv3, !TLSv1
smtpd_tls_mandatory_ciphers = high
smtpd_tls_ciphers = high
smtpd_tls_eecdh_grade = ultra
tls_high_cipherlist=EECDH+ECDSA+AESGCM:EECDH+aRSA+AESGCM:EECDH+ECDSA+SHA384:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA384:EECDH+aRSA+SHA256:EECDH+aRSA+RC4:EECDH:EDH+aRSA:RC4:!aNULL:!eNULL:!LOW:!3DES:!MD5:!EXP:!PSK:!SRP:!DSS
tls_preempt_cipherlist = yes
tls_ssl_options = NO_COMPRESSION

# SMTP
smtp_tls_CApath = /etc/ssl/certs
smtp_use_tls=yes
smtp_tls_loglevel = 1
smtp_tls_session_cache_database = btree:${data_directory}/smtp_scache
smtp_tls_mandatory_protocols = !SSLv2, !SSLv3, !TLSv1
smtp_tls_protocols = !SSLv2, !SSLv3, !TLSv1
smtp_tls_mandatory_ciphers = high
smtp_tls_ciphers = high
smtp_tls_mandatory_exclude_ciphers = MD5, DES, ADH, RC4, PSD, SRP, 3DES, eNULL, aNULL
smtp_tls_exclude_ciphers = MD5, DES, ADH, RC4, PSD, SRP, 3DES, eNULL, aNULL
smtp_tls_security_level = may

smtpd_relay_restrictions = permit_mynetworks permit_sasl_authenticated defer_unauth_destination
myhostname = mail.example.com
mydomain = example.com
alias_maps = hash:/etc/aliases
alias_database = hash:/etc/aliases
myorigin = /etc/mailname

mydestination = localhost.$mydomain, localhost

virtual_transport = anonaddy:
virtual_mailbox_domains = $mydomain, unsubscribe.$mydomain, mysql:/etc/postfix/mysql-virtual-alias-domains-and-subdomains.cf

relayhost =
mynetworks = 127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128
mailbox_size_limit = 0
recipient_delimiter = +
inet_interfaces = all
inet_protocols = all

local_recipient_maps =

smtpd_helo_required = yes
smtpd_helo_restrictions =
    permit_mynetworks
    permit_sasl_authenticated
    reject_invalid_helo_hostname
    reject_non_fqdn_helo_hostname
    reject_unknown_helo_hostname

smtpd_sender_restrictions =
   permit_mynetworks
   permit_sasl_authenticated
   reject_non_fqdn_sender
   reject_unknown_sender_domain
   reject_unknown_reverse_client_hostname

smtpd_recipient_restrictions =
   permit_mynetworks,
   reject_unauth_destination,
   check_recipient_access mysql:/etc/postfix/mysql-recipient-access.cf, mysql:/etc/postfix/mysql-recipient-access-domains-and-additional-usernames.cf,
   check_policy_service unix:private/policyd-spf
   reject_rhsbl_helo dbl.spamhaus.org,
   reject_rhsbl_reverse_client dbl.spamhaus.org,
   reject_rhsbl_sender dbl.spamhaus.org,
   reject_rbl_client zen.spamhaus.org
   reject_rbl_client dul.dnsbl.sorbs.net

# Block clients that speak too early.
smtpd_data_restrictions = reject_unauth_pipelining

# Milter configuration
milter_default_action = accept
milter_protocol = 6
smtpd_milters = local:opendkim/opendkim.sock,local:opendmarc/opendmarc.sock
non_smtpd_milters = $smtpd_milters

disable_vrfy_command = yes
strict_rfc821_envelopes = yes
```

Make sure your hostname is correct in the Postfix config file.

```bash
sudo postconf myhostname
```

You should see mail.example.com if you don't edit `/etc/postfix/main.cf` and update the myhostname value.

Open up `/etc/postfix/master.cf` and update this line at the top of the file:

```cf
smtp       inet  n       -       -       -       -       smtpd
        -o content_filter=anonaddy:dummy
```

This should be the only line for smtp.

Then add these lines to the bottom of the file:

```cf
anonaddy unix - n n - - pipe
  flags=F user=johndoe argv=php /var/www/anonaddy/artisan anonaddy:receive-email --sender=${sender} --recipient=${recipient} --local_part=${user} --extension=${extension} --domain=${domain} --size=${size}
```

Making sure to replace `johndoe` with the username of the user who will run the artisan command and also to update the /path to wherever you plan to place the web app installation. For this tutorial I'm going to use the location `/var/www/anonaddy`.

This command will pipe the email through to our applicaton so that we can determine who the alias belongs to and who to forward the email to.

## Installing Nginx

We'll install the mainline version of Nginx.

Import the nginx signing key and the repository.

```bash
sudo apt-key adv --fetch-keys 'https://nginx.org/keys/nginx_signing.key'
sudo sh -c "echo 'deb https://nginx.org/packages/mainline/ubuntu/ '$(lsb_release -cs)' nginx' > /etc/apt/sources.list.d/Nginx.list"
```

Install and check the version.

```bash
sudo apt update
sudo apt install nginx
sudo nginx -v
```

At the time of writing this I have `nginx version: nginx/1.17.8`.

Create the directory for where the application will be stored.

```bash
sudo mkdir -p /var/www/
sudo chown -R $USER:$USER /var/www/
sudo chmod -R 755 /var/www/
```

First we'll generate a stronger DHE parameter for Nginx by running:

```bash
sudo mkdir /etc/nginx/ssl
sudo openssl dhparam -out /etc/nginx/ssl/dhparam.pem 4096
```

The above command wil take quite some time.

Next create the Nginx server block:

```bash
sudo mkdir /etc/nginx/conf.d/example.com.d
sudo nano /etc/nginx/conf.d/example.com.conf
```

Add the following inside

```
server {
        listen 80;
        listen [::]:80;

        server_name app.example.com;
        return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name app.example.com;
    root /var/www/anonaddy/public;
    server_tokens off;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; font-src 'self'; object-src 'none'";
    add_header Referrer-Policy "origin-when-cross-origin";
    add_header Expect-CT "enforce, max-age=604800";

    index index.html index.htm index.php;

    charset utf-8;

    ssl_certificate             /etc/nginx/conf.d/example.com.d/server.crt;
    ssl_certificate_key         /etc/nginx/conf.d/example.com.d/server.key;
    ssl_trusted_certificate     /root/.acme.sh/example.com/fullchain.cer;

    ssl_prefer_server_ciphers   on;
    ssl_session_timeout         5m;
    ssl_protocols               TLSv1.2 TLSv1.3;
    ssl_stapling                on;
    ssl_stapling_verify         on;
    ssl_ciphers                 "ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384";
    ssl_ecdh_curve              secp384r1;
    ssl_session_cache           shared:SSL:10m;
    ssl_session_tickets         off;
    ssl_dhparam                 /etc/nginx/ssl/dhparam.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo nano /etc/nginx/nginx.conf
```
Change the user to johndoe.

We won't restart nginx yet because it won't be able to find the SSL certificates and will throw an error.

## Installing PHP

We're going to install the latest version of PHP at the time of writing this - version 7.4

```bash
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
```

Install PHP7.4 and check the version.

```bash
sudo apt install php7.4-fpm
php-fpm7.4 -v
```

Install some required extensions:

```bash
sudo apt install php7.4-common php7.4-mysql php7.4-dev php7.4-mbstring php7.4-gd php7.4-imagick php7.4-opcache php7.4-soap php7.4-zip php7.4-cli php7.4-curl -y
```

Now we need to install the php-mailparse (for parsing emails) and php-gnupg (for encrypting emails) extensions.

Install the required tools:

```bash
sudo apt install autoconf libpcre3-dev
```

Check out the latest versions here:

https://pecl.php.net/package/mailparse
https://pecl.php.net/package/gnupg

At the time of writing this the latest version for mailparse is 3.0.4 and for gnupg is 1.4.0.

You need to switch to the root user in order to install these next two extensions

```bash
sudo su
```

### Method 1.

```bash
cd ~
wget http://pear.php.net/go-pear.phar
php go-pear.phar
pecl install mailparse
pecl install gnupg
```

When installing mailparse you may get an error stating: "The mailparse extension requires the mbstring extension" even though you do have it installed. This is a known bug, please install mailparse using Method 2 below.

If you get this error "configure: error: Please reinstall the gpgme distribution" then run

```bash
sudo apt install libgpgme11-dev
```

If you have issues with the above then follow the instructions below for option 2.

### Method 2.


First let's install mailparse

Set a couple of variables:

```bash
MODULE_NAME="mailparse"
MODULE_VERSION="3.0.4"
```

```bash
cd ~

pecl download $MODULE_NAME

tar -zxvf $MODULE_NAME-$MODULE_VERSION.tgz

cd $MODULE_NAME-$MODULE_VERSION

# Ignore the error that appears if you have never compiled it before
make clean

phpize7.4 -clean

phpize7.4

./configure --with-libdir=lib64 CFLAGS='-O2 -fPIE -fstack-protector-strong -Wformat -Werror=format-security -Wall -pedantic -fsigned-char -fno-strict-aliasing'

# For mailparse only! Due to a known bug where it thinks mbstring is not installed even though it is
sed -i 's/#if\s!HAVE_MBSTRING/#ifndef MBFL_MBFILTER_H/' ./mailparse.c

make install

echo "extension=$MODULE_NAME.so" > /etc/php/7.4/mods-available/$MODULE_NAME.ini

# Create symbolic links
ln -s /etc/php/7.4/mods-available/$MODULE_NAME.ini /etc/php/7.4/fpm/conf.d/20-$MODULE_NAME.ini
ln -s /etc/php/7.4/mods-available/$MODULE_NAME.ini /etc/php/7.4/cli/conf.d/20-$MODULE_NAME.ini

service php7.4-fpm restart
```

Follow the steps above again for gnupg but make sure to change `MODULE_NAME` and `MODULE_VERSION` like below:

```bash
MODULE_NAME="gnupg"
MODULE_VERSION="1.4.0"
```

Check the installed modules to see if both are there.

```bash
php -m
```

If you are still logged in as the root user `exit` back to johndoe.

```bash
sudo nano /etc/php/7.4/fpm/pool.d/www.conf
```

```conf
user = johndoe
group = johndoe
listen.owner = johndoe
listen.group = johndoe
```

## Let's Encrypt

Now we need to get an SSL certificate using Acme.sh.

We again need to switch to the root user to run these commands:

```bash
sudo su
```

Download the install script from GitHub and run it.

```bash
cd ~
git clone https://github.com/acmesh-official/acme.sh.git
cd ./acme.sh
./acme.sh --install
```
You should set up automatic DNS API integration for wildcard certs if you are using them, this will allow automatic renewal of certificates.

https://github.com/acmesh-official/acme.sh#8-automatic-dns-api-integration

For example instructions for Vultr are here - https://github.com/acmesh-official/acme.sh/wiki/dnsapi#82-use-vultr-dns-api-to-automatically-issue-cert

I would run:

```bash
export VULTR_API_KEY="<Your API key>"
```

To install the certificate run:

```bash
./acme.sh --issue -d example.com -d '*.example.com' --dns dns_vultr \
--keylength 4096 \
--key-file       /etc/nginx/conf.d/example.com.d/server.key  \
--fullchain-file /etc/nginx/conf.d/example.com.d/server.crt \
--reloadcmd     "service nginx force-reload"
```

Make sure to change example.com to your domain.

## SPF and DKIM

Follow the instructions in the linked blog post at the end of this section on how to install OpenDKIM and then add an SPF record.

The only differences are the following couple of points:

```conf
Canonicalization   simple
Mode               sv
SubDomains         yes
```

Also when editing `/etc/opendkim/signing.table` add this line too so that emails from subdomain aliases will also be signed.

```
*@*.example.com    default._domainkey.example.com
```

https://www.linuxbabe.com/mail-server/setting-up-dkim-and-spf

Once you've finished following the above post you should have SPF and DKIM set up for your domain.

## DMARC

Next follow this blog post on how to install OpenDMARC.

https://www.linuxbabe.com/mail-server/opendmarc-postfix-ubuntu

Then take a look at this one on how to add a DMARC record to your domain:

https://www.linuxbabe.com/mail-server/create-dmarc-record

After following those posts you should now have a valid DMARC record for your domain.

## Installing MariaDB

At the time of writing this the latest stable release is v10.4. Make sure to check for any newer releases.

Follow the instructions on this link to install MariaDB:

https://downloads.mariadb.org/mariadb/repositories/#distro=Ubuntu&distro_release=bionic--ubuntu_bionic&mirror=digitalocean-sfo&version=10.4

Make sure it is running correctly and check the version

```bash
sudo systemctl status mariadb
sudo mysql -V
```

At the time of writing this I am using "Ver 15.1 Distrib 10.4.12-MariaDB"

Keep the default answers when running the below but set a secure MySQL root password and make a note of it somewhere e.g. password manager.

```bash
sudo mysql_secure_installation
```

Next we're going to create the database and also a user with correct permissions.

```bash
sudo mysql -u root -p
```
Once in the MariaDB shell create a new database called anonaddy_database (or whatever you like)

```sql
CREATE DATABASE anonaddy_database;
```

Then create a new user and give them a strong password (replace below)

```sql
CREATE USER 'anonaddy'@'localhost' IDENTIFIED BY 'STRONG-PASSWORD-HERE';
```

Grant the user privileges for the new database.

```sql
GRANT ALL PRIVILEGES ON anonaddy_database.* TO 'anonaddy'@'localhost' WITH GRANT OPTION;
```

Flush privileges.

```sql
FLUSH PRIVILEGES;
exit
```

Create a new file `/etc/postfix/mysql-virtual-alias-domains-and-subdomains.cf` and enter the following inside:

```
user = anonaddy
password = your-database-password
hosts = 127.0.0.1
dbname = anonaddy_database
query = SELECT (SELECT 1 FROM users WHERE CONCAT(username, '.example.com') = '%s') AS users, (SELECT 1 FROM additional_usernames WHERE CONCAT(additional_usernames.username, '.example.com') = '%s') AS usernames, (SELECT 1 FROM domains WHERE domains.domain = '%s' AND domains.domain_verified_at IS NOT NULL) AS domains LIMIT 1;
```

This file is responsible for determining whether the server should accept email for a certain domain/subdomain. If no results are found from the query then the email will not be accepted.

Next create another new file `/etc/postfix/mysql-recipient-access-domains-and-additional-usernames.cf` and enter the following inside:

```
user = anonaddy
password = your-database-password
hosts = 127.0.0.1
dbname = anonaddy_database
query = SELECT (SELECT 'DISCARD' FROM additional_usernames WHERE (CONCAT(username, '.example.com') = SUBSTRING_INDEX('%s','@',-1)) AND active = 0) AS usernames, (SELECT 'DISCARD' FROM domains WHERE domain = SUBSTRING_INDEX('%s','@',-1) AND active = 0) AS domains LIMIT 1;
```

This file is responsible for checking whether the alias is for an additional username/custom domain and if so then is that additional username/custom domain set as active. If it is not set as active then the email is discarded.

Now we need to create a stored procedure that can be called.

In order for Postfix to REJECT or DISCARD emails sent to deleted or deactivated aliases you need to ceate a new file called `/etc/postfix/mysql-recipient-access.cf`.

In this file enter the following:

```
user = anonaddy
password = your-database-password
hosts = 127.0.0.1
dbname = anonaddy_database
query = CALL block_alias('%s')
```

This query calls a stored procedure that we will create next, it passes the recipient's email address as the argument and checks to see if the alias is either deactivated or has previously been deleted and returns the appropriate response (DISCARD for deactivated and REJECT for deleted).

The reason we're using a stored procedure here is because we need to run more than one SQL query which means we cannot just add it inline to `/etc/postfix/mysql-recipient-access.cf` as we have with the others.

Update the permissions and the group of all these files:

```bash
sudo chmod o= /etc/postfix/mysql-virtual-alias-domains-and-subdomains.cf /etc/postfix/mysql-recipient-access-domains-and-additional-usernames.cf /etc/postfix/mysql-recipient-access.cf

sudo chgrp postfix /etc/postfix/mysql-virtual-alias-domains-and-subdomains.cf /etc/postfix/mysql-recipient-access-domains-and-additional-usernames.cf /etc/postfix/mysql-recipient-access.cf
```

Either from the command line (`sudo mysql -u root -p`) or from an SQL client, run the following code to create the stored procedure.

You will need to set appropriate permissions for your database user to allow them to execute the stored procedure.

```sql
DELIMITER $$

USE `anonaddy_database`$$

DROP PROCEDURE IF EXISTS `block_alias`$$

CREATE DEFINER=`anonaddy`@`localhost` PROCEDURE `block_alias`(alias_email VARCHAR(254))
BEGIN
   UPDATE aliases SET
    emails_blocked = emails_blocked + 1
   WHERE email = alias_email AND active = 0 LIMIT 1;
   SELECT IF(deleted_at IS NULL,'DISCARD','REJECT') AS alias_action
   FROM aliases WHERE email = alias_email AND (active = 0 OR deleted_at IS NOT NULL) LIMIT 1;
 END$$

DELIMITER ;
```

Make a test call for the stored procedure as your database user to ensure everything is working as expected.

```sql
USE anonaddy_database;
CALL block_alias('email@example.com');
```

You will get an error stating "Table 'anonaddy_database.aliases' doesn't exist" as we have not yet migrated the database.

## Installing Redis

Follow this blog post on Digital Ocean to install Redis.

https://www.digitalocean.com/community/tutorials/how-to-install-and-secure-redis-on-ubuntu-18-04

We'll be using Redis for queues, user limits, sessions and caching.

## The web application

Next let's get the actual AnonAddy application from GitHub.

```bash
cd /var/www/
git clone https://github.com/anonaddy/anonaddy.git
cd /var/www/anonaddy
```

Make sure composer is installed (`composer -V`), if not then goto - https://getcomposer.org/download/ for instructions.

You can add the following flags when installing composer:

```bash
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
```

Make sure node is installed (`node -v`) if not then install it using NVM - https://www.digitalocean.com/community/tutorials/how-to-install-node-js-on-ubuntu-18-04#installing-using-nvm

At the time of writing this I'm using the latest LTS - ........

```bash
cd /var/www/anonaddy
composer install && npm install
npm run production
```

Next copy the .env.example file and update it with correct values (database password, app url, redis password etc.)

```bash
cp .env.example .env
nano .env
```

Make sure to update the database settings and the AnonAddy variables, you can use Redis for queue, sessions and cache.

`APP_KEY` will be generted in the next step, this is used by Laravel for securely encrypting values.

For more information on Laravel configuration please visit - https://laravel.com/docs/6.x/installation#configuration

For the `ANONADDY_DKIM_SIGNING_KEY` you only need to fill in this variable if you plan to add any custom domains through the web application.

You can either use the same private DKIM signing key we generated earlier from this tutorial - https://www.linuxbabe.com/mail-server/setting-up-dkim-and-spf

Or you can generate a new private/public keypair and give your user `johndoe` ownership of the private key.

If you want to use the same key we already generated then you will need to add `johndoe` to the `opendkim` group by running:

```
sudo usermod -a -G opendkim johndoe
```

Then we will generate an app key, migrate the database, link the storage directory, restart the queue and install laravel passport.

```bash
php artisan key:generate
php artisan migrate
php artisan storage:link

php artisan config:cache
php artisan view:cache
php artisan route:cache
php artisan queue:restart

php artisan passport:install
```

## Installing Supervisor

We will be using supervisor for keeping the Laravel queue worker alive.

```bash
sudo apt install supervisor
```

Create a new configuration file:

```bash
sudo nano /etc/supervisor/conf.d/anonaddy.conf
```

Enter the following inside (change user and command location if you need to):

```conf
[program:anonaddy]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/anonaddy/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=johndoe
numprocs=8
redirect_stderr=true
stopwaitsecs=3600
```

Then run:

```bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start anonaddy:*
```

## Creating your account

You should now be able to visit `app.example.com` if you've set the correct DNS records.

Register an account and start using it straight away!

You can disable user registration after you've created your account to prevent anyone else from signing up.

Just update the value of `ANONADDY_ENABLE_REGISTRATION` to false in your .env file (and then run `php artisan config:cache` to update).

## Adding your private key to sign emails

If you are using encryption and want to sign your forwarded emails then you'll need to create a new GPG key pair.

A simple guide can be found here - https://www.linuxbabe.com/security/a-practical-guide-to-gpg-part-1-generate-your-keypair

Then update the value of `ANONADDY_SIGNING_KEY_FINGERPRINT=` in your .env file to match the fingerprint of your key e.g. `26A987650243B28802524E2F809FD0D502E2F695`.

## What to do next

The above steps are enough to get you set up and running with AnonAddy but if you'd like to take it further then keep reading.

## Installing Spamassassin

We can use Spamassassin to reject spam emails arriving at our server.

```bash
sudo apt install spamassassin spamc

sudo systemctl enable spamassassin

sudo systemctl start spamassassin
```

Next install the milter so that we can reject emails if they have a high spam score.

```bash
sudo apt install spamass-milter
```

Update `/etc/postfix/main.cf` and add the milter to smtpd_milters.

```cf
# Milter configuration
milter_default_action = accept
milter_protocol = 6
smtpd_milters = local:opendkim/opendkim.sock,local:opendmarc/opendmarc.sock,local:spamass/spamass.sock
non_smtpd_milters = $smtpd_milters
```

You can change the score needed in order for an email to be rejected by editing `/etc/default/spamass-milter`.

```
# Reject emails with spamassassin scores > 15.
#OPTIONS="${OPTIONS} -r 15"
```

Just uncomment the OPTIONS line and change 15 to something else, for example 7.5

```
# Reject emails with spamassassin scores > 7.5.
OPTIONS="${OPTIONS} -r 7.5"
```

Next restart Spamassassin and Postfix

```bash
sudo systemctl restart postfix spamass-milter
```

If you want to test if Spamassasin is working then send an email with the content from this link in it.

https://spamassassin.apache.org/gtube/

It should be rejected with the message `ERROR_CODE :550, ERROR_CODE :5.7.1 Blocked by SpamAssassin`.

## Setting up a local caching DNS resolver

This is to speed up queries and to prevent you getting rate limited when querying DNSBLs (DNS black lists) etc.

Follow the below blog post on how to install bind9.

https://www.linuxbabe.com/ubuntu/set-up-local-dns-resolver-ubuntu-18-04-16-04-bind9

Now open up `/etc/nginx/conf.d/example.com.conf` and add these two lines below the ssl parameters.

```
resolver                    127.0.0.1 valid=86400s;
resolver_timeout            5s;
```

Restart nginx:

```bash
sudo service nginx restart
```

Restart the server by running `sudo reboot` and then SSH back in.

Next to test if everything is working run:

```bash
host -tTXT 2.0.0.127.multi.uribl.com
```

You should see the response

```bash
2.0.0.127.multi.uribl.com descriptive text "permanent testpoint"
```

This means you can query URIBL successfully now.

Update `/etc/spamassassin/local.cf` and add this near the top:

```cf
dns_available yes
```


## Adding DNSSEC



// TODO
DNSSEC, DANE (TLSA DNS record), SMTP TLS Reporting (TLS-RPT).

