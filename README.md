# Web Application Request Router #

[![Travis](https://img.shields.io/travis/simply-framework/router.svg?style=flat-square)](https://travis-ci.org/simply-framework/router)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/simply-framework/router.svg?style=flat-square)](https://scrutinizer-ci.com/g/simply-framework/router/)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/simply-framework/router.svg?style=flat-square)](https://scrutinizer-ci.com/g/simply-framework/router/)
[![Packagist](https://img.shields.io/packagist/v/simply/router.svg?style=flat-square)](https://packagist.org/packages/simply/router)

This package provides generic router that may be used with PHP frameworks. The goals is of this router is to optimize
towards fast startup taking advantage of array op-cache optimizations in PHP 7. 
 
This router uses segment based matching which optimizes usage of static segments in the paths to minimize the need to
generate large and slow regular expressions similar to FastRoute or Symfony Router.

NOTE: This package is part of personal experimental framework. No stable API or proper documentation is to be expected
until the framework has proven itself in practical use cases.

## Credits
 
This library is Copyright (c) 2018 Riikka Kalliomäki.

See LICENSE for license and copying information.
