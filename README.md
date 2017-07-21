Mailcheck
=========

Php port of [kicksend/mailcheck](https://github.com/Kicksend/mailcheck)

Free API available here : [mailcheck/suggest](http://headoo.com/api/mailcheck/suggest/)

Example : [test@hotnail.cmo](http://headoo.com/api/mailcheck/suggest/test@hotnail.cmo)

Installation : 

    $  git clone https://github.com/Headoo/mailcheck.git
    $  composer update

Test : 

    $ ./vendor/phpunit/phpunit/phpunit tests
     
Upload code coverage
 
    $ php vendor/bin/phpunit tests --coverage-clover=coverage.xml
    $ bash <(curl -s https://codecov.io/bash)
