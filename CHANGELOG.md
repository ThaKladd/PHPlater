
# Changelog

## v1.1.0

### Cache

 - Added optional cache
 - Cache speeds ~40% faster when looping same template
 - Speeds without cache improved ~10%
 - Internal refactoring for speed, and more static
 - Some breaking changes due to static methods and chaining

## v1.0.0

### Revamped

- Removed deprecated methods
- Back to getters and setters
- More robust and correct type hinting
- Template file extension and root folder can be defined
- Templates can be used without extension
- Refactored the code into multiple classes
- Reworked tests for the new syntaxes
- Added more error handling, but more so, made typesafe
- Added quick access function
- Speeds up to about 400% faster

## v0.8.0

### Error handling

- Some errors now throw catchable exeptions
- Fixed a few major issues
- Refactored lot of code
- Updated phpUnit tests to use phar
- Changed preg delimiter to ~

## v0.7.0

### Key tag

- Added key tag for getting key from lists
- Changed how tags are accessed and refactored the code

## v0.6.0

### Added conditionals

- Conditionals are now supported
- Support for common operands for comparison
- Method to change argument list , seperator

## v0.5.0

### Added new syntax for iteration

- New syntax for iterating lists in plates
- Changes in escaping of seperators and tags
- Optimizing some inner workings of the class

## v0.4.0

### Added application of template to an array of plates

- Added the many method to indicate that the template should be applied to a list of values
- Access to value in array as first value on template

## v0.3.1

### Added chain separator

- Added changable chain separator
- Better handling and escaping of separators, so that they can be more than one char long

## v0.3.0

### Added argumnets to filters

- Inspired by [Latte](https://github.com/nette/latte), filters can have arguments

## v0.2.0

### Added filters

- Added [Twig](https://github.com/twigphp/Twig) style filters in the spirit of PHPlater
- Multiple filters can be applied
- The filter separator can be defined by user
- If template variable is array, json or object, filters can be applied to it as well

## v0.1.0

### Initial release candidate

- Basic templating functionality
- Nesting of templates
- User defined delimiters and tags
- Template can be file or string
- Varables added can be arrays, json and objects, and data can be retrieved by chainin
