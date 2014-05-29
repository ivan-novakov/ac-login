# AC Login

External Shibboleth authentication for Adobe Connect

## Requirements

* PHP 5.3
* [Shibboleth SP](http://www.shibboleth.net/) instance
* [Composer](https://getcomposer.org/)

## Installation

Clone the repository:
```
$ git clone https://github.com/ivan-novakov/ac-login.git
```

Install the dependencies with [Composer](https://getcomposer.org/):
```
$ cd ac-login/
$ composer install
```  

Create a local copy of the configuration files and edit it to suit your environment:
```
$ cp config/aclogin.ini.dist config/aclogin.ini
$ cp config/acl/aclogin.acl.php.dist config/acl/aclogin.acl.php
```

In Apache, configure Shibboleth for the `public/` directory:
```
Alias /aclogin AC_LOGIN_DIR/public
<Location /aclogin>
    AuthType shibboleth
    ShibRequestSetting requireSession 1
    require valid-user
</Location>
```

For more information you can look at the original installation instructions, which may be a bit outdated:

* https://homeproj.cesnet.cz/projects/aclogin/wiki/Install_en
 

## License

* [LGPL](http://www.gnu.org/licenses/lgpl.txt)

## Copyright

* (c) 2009-2014 [CESNET, z. s. p. o.](http://www.ces.net/)

## Author

* [Ivan Novakov](http://novakov.cz/)
