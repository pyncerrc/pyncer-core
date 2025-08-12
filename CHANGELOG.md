# Change Log

## 1.3.0 - Unreleased

### Added

- Added Initializer class to allow pyncer packages to initialize constants.

## 1.2.4 - 2025-08-08

### Fixed

- Fixed deprecated implicitly marked nullable parameter.
- Fixed errors brought up by updated PHPStan.

## 1.2.3 - 2024-09-28

### Fixed

- Fixed not taking into consideration paths with periods when dealing with extensions.

## 1.2.2 - 2024-05-21

### Fixed

- Added missing type hint to get\_recursive $array parameter.

### Changed

- Deprecated first\_key and last\_key array functions since array\_key\_first and array\_key\_last now exist.

## 1.2.1 - 2024-03-27

### Fixed

- Fixed clean\_uri function adding a trailing '/' when not needed.

## 1.2.0 - 2024-03-22

### Added

- Added removeExtension parameter to filename IO function.

### Changed

- Deprecated HTTP URL functions and replaced them with equivalent URI functions.

## 1.1.9 - 2024-02-25

### Added

- Added map\_defines utility function.

## 1.1.8 - 2024-02-14

### Added

- Added code function.

### Changed

- Normalized naming order of some Pyncer\IO constants.

## 1.1.7 - 2023-09-17

### Added

- Added scalarify and stringify functions.

## 1.1.6 - 2023-09-16

### Added

- Added basify function.

## 1.1.5 - 2023-09-16

### Added

- Added uid generation function.

## 1.1.4 - 2023-09-14

### Added

- The date\_time function now supports passing true to mean use current time.

### Changed

- The date\_time function will now throw an InvalidArgumentException if the specified date is an invalid type.
- The he and hd functions now use the ENCODING constant instead of 'UTF-8'.

## 1.1.3 - 2023-09-03

### Fixed

- Fixed bad return type possibility in split\_case utility function.

## 1.1.2 - 2023-07-05

### Fixed

- Fixed issue with Pyncer\Array\set\_recursive function.

## 1.1.1 - 2023-05-24

### Changed

- Normalized 'Chars' to 'Characters' to match the rest of Pyncer.

## 1.1.0 - 2023-05-11

### Added

- Added to\_kebab\_case utility function.

### Changed

- split\_case function now handles splitting kebab case

## 1.0.1 - 2023-01-09

### Fixed

- Multiple issues picked up by PHPStan.

### Changed

- Some function parameter names have changed.

### Added

- Unit tests.
- PHPStan static analysis.

## 1.0.0 - 2022-11-29

Initial release.
