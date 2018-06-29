# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Added internal function `split_segments()`
- Added internal function `string_split()`
- Added configuration for sami based API documentation

### Changed
- `HttpMethod::isValidMethod()` renamed to `HttpMethod::isValid()`
- `HttpMethod::getHttpMethods()` renamed to `HttpMethod::getAll()`
- Dynamic segments are now indicated by `/` rather than `#`.
- The signature and name of `RouteDefinitionProvider::getStaticRoutes()` was changed to
  `RouteDefinitionProvider::getRoutesByStaticPath($path)`
- The signature and name of `RouteDefinitionProvider::getSegmentCounts()` was changed to
  `RouteDefinitionProvider::getRoutesBySegmentCount($count)`
- The signature and name of `RouteDefinitionProvider::getSegmentValues()` was changed to
  `RouteDefinitionProvider::getRoutesBySegmentValue($segment, $value)`
- Address code quality issues

## 0.1.0 - 2018-06-23
### Added
- Initial development release

[Unreleased]: https://github.com/simply-framework/router/compare/v0.1.0...HEAD
