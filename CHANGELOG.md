# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.5] - 2019-01-15
### Changed
- Improved some tests based on mutation testing

### Fixed
- The router should no longer produce "Undefined offset 0" error when matching route "/" without a match

## [0.2.4] - 2018-10-17
### Added
- The `RouteDefinitionProvider::getCacheFile()` now accepts an optional parameter for encoding callback

### Changed
- Some tests and conditions have been improved with mutation testing

## [0.2.3] - 2018-07-16
### Changed
- Ensure the allowed methods are always listed in canonical order

## [0.2.2] - 2018-07-10
### Added
- Added `Route::getParameters()` to fetch all the parameters set by the route

## [0.2.1] - 2018-07-02
### Fixed
- Added missing functions.php from autoloaded files on normal autoloader

## [0.2.0] - 2018-06-29
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
- Addressed numerous code quality and static analysis issues

## 0.1.0 - 2018-06-23
### Added
- Initial development release

[Unreleased]: https://github.com/simply-framework/router/compare/v0.2.5...HEAD
[0.2.5]: https://github.com/simply-framework/router/compare/v0.2.4...v0.2.5
[0.2.4]: https://github.com/simply-framework/router/compare/v0.2.3...v0.2.4
[0.2.3]: https://github.com/simply-framework/router/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/simply-framework/router/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/simply-framework/router/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/simply-framework/router/compare/v0.1.0...v0.2.0
