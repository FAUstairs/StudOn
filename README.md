![](https://img.shields.io/badge/build-6.12.90--2-white?logo=debian&label=Debian) ![](https://img.shields.io/badge/build-11.8.6-brown?logo=mariadb&label=MariaDB) ![](https://img.shields.io/badge/build-8.2-purple?logo=php&label=PHP) ![](https://img.shields.io/badge/build-20.19.2-green?logo=node.js&label=Node.js) ![](https://img.shields.io/badge/build-9.2.0-green?logo=npm&label=npm)

# StudOn FAUstairs

StudOn FAUstairs is a customized version of FAU's StudOn platform based on ILIAS 9. It serves as a complementary instance to StudOn by providing LTI resources, such as the Lectures plugin, rather than functioning as a standalone learning management system. It is accessible at https://studon-staging.kwarc.info.

The following installation procedure is based on the official [ILIAS installation documentation](https://docu.ilias.de/ilias.php?baseClass=illmpresentationgui&cmd=layout&ref_id=367&obj_id=220443#install-ilias). Note that the exact dependency versions may vary depending on your operating system and distribution.

## Installation Dependencies

### MariaDB
```
sudo apt install mariadb-server mariadb-client
```
### Apache2 
```
sudo apt install apache2
```

### PHP
```
sudo apt install ca-certificates apt-transport-https lsb-release wget gnupg

wget -qO- https://packages.sury.org/php/apt.gpg | sudo gpg --dearmor -o /usr/share/keyrings/sury-php.gpg

echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ trixie main" | sudo tee /etc/apt/sources.list.d/sury-php.list

sudo apt install \
  libapache2-mod-php8.2 \
  php8.2 \
  php8.2-cli \
  php8.2-common \
  php8.2-mysql \
  php8.2-xml \
  php8.2-curl \
  php8.2-mbstring \
  php8.2-zip \
  php8.2-gd \
  php8.2-xsl \
  php8.2-imagick \
  php8.2-soap \
  php8.2-ldap \
  php8.2-intl

sudo update-alternatives --config php
php -v
```

### Node.js
```
sudo apt install nodejs
```

### Additional Dependencies
```
sudo apt install zip unzip maven ffmpeg git ghostscript
```

## Webserver Configuration
### 1. Create Directories
```
sudo mkdir /var/www/ilias
sudo mkdir /var/www/files
sudo mkdir /var/www/logs
sudo mkdir /var/www/config
sudo mkdir /var/www/.npm
```

### 2. Set Rights
```
sudo chown -R www-data:www-data /var/www/ilias
sudo chown -R www-data:www-data /var/www/files
sudo chown -R www-data:www-data /var/www/logs
sudo chown -R www-data:www-data /var/www/config
sudo chown -R www-data:www-data /var/www/.npm
```

### 3. Change Apache Configuration
```
sudo vim /etc/apache2/sites-enabled/000-default.conf
```
Add the following lines:
```
<VirtualHost *:80>
    ServerAdmin webmaster@example.com

    DocumentRoot /var/www/ilias/
    <Directory /var/www/ilias/>
        Options +FollowSymLinks -Indexes
        AllowOverride All
        Require all granted
    </Directory>

    # Possible values include: debug, info, notice, warn, error, crit,
    # alert, emerg.
    LogLevel warn

    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
```

### 4. Enable mod_rewrite
```
sudo a2enmod rewrite
sudo systemctl restart apache2.service
```

### 5. Update php.ini file
```
sudo nano /etc/php/8.2/apache2/php.ini
```
Add or change the following lines:
```
; you may choose higher values for max_execution_time and memory_limit
max_execution_time = 600
memory_limit = 512M

error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT ; PHP 5.4.0 and higher
display_errors = Off

; or any higher values for post_max_size and upload_max_filesize
post_max_size = 256M
upload_max_filesize = 256M

; choose a non-zero value for session.gc_probability, otherwise old session data will not be deleted
session.gc_probability = 1
session.gc_divisor = 100
session.gc_maxlifetime = 14400
session.hash_function = 0
session.cookie_httponly = On
session.save_handler = files
; If you installation is served via HTTPS also use:
session.cookie_secure = On

; for chat server
allow_url_fopen = 1

; How many GET/POST/COOKIE input variables may be accepted
max_input_vars = 10000
```
Restart apache:
```
sudo systemctl restart apache2.service
```

## Database Configuration
### 1. Update Config
```
sudo vim /etc/mysql/mariadb.conf.d/50-server.cnf
```
By default the engine of [MariaDB](https://mariadb.com/docs/server/server-usage/storage-engines/choosing-the-right-storage-engine) and [MySQL](https://dev.mysql.com/doc/refman/8.4/en/storage-engine-setting.html) is InnoDB
Add, uncomment or modifiy the following lines:
```
join_buffer_size > 128K
table_open_cache > 400
innodb_buffer_pool_size > 2G
character-set-server = utf8mb3
collation-server = utf8mb3_general_ci
```
Restart MariaDB:
```
sudo systemctl restart mariadb.service
```

### 2. Create StudOn Database
Replace `<PASSWORD>` with a password of your choice.
```
sudo mariadb

CREATE DATABASE ilias CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'ilias'@'localhost' IDENTIFIED BY <PASSWORD>;
GRANT LOCK TABLES on *.* TO 'ilias'@'localhost';
GRANT ALL PRIVILEGES ON ilias.* TO 'ilias'@'localhost';
FLUSH PRIVILEGES;
```

## Install StudOn
### 1. Download the GitHub Repo
```
cd /var/www/ilias
sudo -u www-data git clone https://github.com/FAUstairs/StudOn.git .
```

### 2. Download Dependencies
```
sudo -u www-data npm clean-install --omit=dev --ignore-scripts


sudo mkdir -p /var/www/ilias/libs/composer/vendor
sudo chown -R www-data:www-data /var/www/ilias/libs/composer

cd /var/www/ilias
sudo mkdir -p Services/Component/artifacts
sudo mkdir -p Services/GlobalScreen/artifacts
sudo chown -R www-data:www-data Services/Component/artifacts
sudo chown -R www-data:www-data Services/GlobalScreen/artifacts

sudo -u www-data composer install --no-dev
```

### 3. Prepare and install config file
Copy the `minimal-config.json` to `/var/www/config/ilias.json`
```
sudo cp /var/www/ilias/setup/minimal-config.json /var/www/config/ilias.json
```
Update `password`, `http path` and `contact` in `ilias.json`:
```
{
    "common": {
        "client_id": "myilias"
    },
    "database": {
        "user": "ilias",
        "password": <PASSWORD>,
        "database": "ilias"
    },
    "filesystem": {
        "data_dir": "/var/www/files/ilias"
    },
    "logging": {
        "enable": true,
        "path_to_logfile": "/var/www/logs/ilias.log",
        "errorlog_dir": "/var/www/logs/"
    },
    "http": {
        "path": "http://www.example.com"
    },
    "systemfolder": {
        "contact": {
            "firstname": "Max",
            "lastname": "Mustermann",
            "email": "max.mustermann@fau.de"
        }
    },
    "utilities": {
        "path_to_convert": "/usr/bin/convert"
    },
    "mediaobject": {
        "path_to_ffmpeg": "/usr/bin/ffmpeg"
    },
    "preview": {
        "path_to_ghostscript": "/usr/bin/gs"
    }
}
```
Install Config:
```
cd /var/www/ilias/
sudo -u www-data php setup/setup.php install /var/www/config/ilias.json
```

## Fixes
### Clock
If the time displayed in the PHP configuration or the user login logs is incorrect, update the following files:
```
sudo nano /etc/php/8.2/apache2/php.ini
sudo nano /etc/php/8.2/cli/php.ini
```
Set the following value:
```
date.timezone = Europe/Berlin
```
Update `ilias.ini.php`:
```
sudo vim /mnt/data/StudOn/ilias/ilias.ini.php
```
Set the following value:
```
timezone = "Europe/Berlin"
```
Restart Apache
```
sudo systemctl restart apache2
```

## LTI Setup
### Set Consumer Default Values
Open
```
cd /var/www/ilias
sudo nano Services/LTI/classes/InternalProvider/class.ilLTIProviderObjectSettingGUI.php
```
Change
```
$tf->setValue($active_consumer->platformId);
$tf->setValue($active_consumer->clientId);
$tf->setValue($active_consumer->deploymentId);
$tf->setValue($active_consumer->jku);
$tf->setValue($active_consumer->accessTokenUrl);
$tf->setValue($active_consumer->authenticationUrl);
```
Update the configuration as shown below. Replace `<PROVIDER_ADDRESS>`, `<CLIENT_ID>`, `<DEPLOYMENT_ID>`, `<JWU>`, `<ACCESS_TOKEN_URL>` and `<AUTH_URL>` with the values provided by the LTI provider:

```
$tf->setValue($active_consumer->platformId ?: <PROVIDER_ADDRESS>);
$tf->setValue($active_consumer->clientId ?: <CLIENT_ID>);
$tf->setValue($active_consumer->deploymentId ?: <DEPLOYMENT_ID>);
$tf->setValue($active_consumer->jku ?: <JWU>);
$tf->setValue($active_consumer->accessTokenUrl ?: <ACCESS_TOKEN_URL>);
$tf->setValue($active_consumer->authenticationUrl ?: <AUTH_URL>);

```
Restart Apache:
```
sudo systemctl restart apache2
```
