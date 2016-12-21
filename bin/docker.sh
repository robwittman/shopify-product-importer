echo $PWD;
docker run -it -p 80:80 -v $PWD:/var/www/ -v $PWD/php.ini:/usr/local/etc/php/php.ini php:5.6-apache /bin/bash -c 'a2enmod rewrite; apache2-foreground'
