## Docker

### Documentation

- https://docs.dockware.io/
- https://developer.shopware.com/docs/guides/installation/dockware

### Default URL

- Apache DocRoot: /var/www/html/public
- ADMINER URL: http://localhost/adminer.php
- MAILCATCHER URL: http://localhost/mailcatcher
- PIMPMYLOG URL: http://localhost/logs
- SHOP URL: http://localhost
- ADMIN URL: http://localhost/admin

### Commands

`docker-compose up -d`

## Build plugin

- docker default path

`cd /var/www/html`

- build administration front-end

`./bin/build-administration.sh`

- build a shop front-end

`./bin/build-storefront.sh`
