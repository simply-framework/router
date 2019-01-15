# Web Application Request Router #

[![Travis](https://img.shields.io/travis/simply-framework/router.svg?style=flat-square)](https://travis-ci.org/simply-framework/router)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/simply-framework/router.svg?style=flat-square)](https://scrutinizer-ci.com/g/simply-framework/router/)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/simply-framework/router.svg?style=flat-square)](https://scrutinizer-ci.com/g/simply-framework/router/)
[![Packagist](https://img.shields.io/packagist/v/simply/router.svg?style=flat-square)](https://packagist.org/packages/simply/router)

This package provides generic router that may be used with PHP frameworks. The goal of this router is to optimize
towards fast startup taking advantage of array op-cache optimizations in PHP 7. 
 
This router uses segment based matching which optimizes usage of static segments in the paths to minimize the need to
generate large and slow regular expressions. In some uses cases, this may provide faster routing than simply using a few
large regular expression. However, the aim of this package is not to compete in efficiency against more popular routers.

NOTE: This package is part of a framework that is still highly experimental in nature. Stable api or proper
documentation are not to be expected until the framework has been tested in practice.

API documentation is available at: https://docs.riimu.net/simply/router/

## Credits
 
This library is Copyright (c) 2018-2019 Riikka Kalliomäki.

See LICENSE for license and copying information.
