# PHPUnit Extensions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lloople/phpunit-extensions.svg?style=flat-square)](https://packagist.org/packages/lloople/phpunit-extensions)
[![Build Status](https://img.shields.io/travis/lloople/phpunit-extensions/master.svg?style=flat-square)](https://travis-ci.org/lloople/phpunit-extensions)
[![Quality Score](https://img.shields.io/scrutinizer/g/lloople/phpunit-extensions.svg?style=flat-square)](https://scrutinizer-ci.com/g/lloople/phpunit-extensions)
[![Total Downloads](https://img.shields.io/packagist/dt/lloople/phpunit-extensions.svg?style=flat-square)](https://packagist.org/packages/lloople/phpunit-extensions)

This package provides you a few useful extensions for your testsuite in an effort to improve your code.

## Installation

You can install the package via composer:

```bash
composer require lloople/phpunit-extensions --dev
```

Add the Extension to your `phpunit.xml` file:

```xml
<extensions>
    <extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\Console" />
</extensions>
```

## Extensions

## Console

Output the slowest tests on the console.

```xml
<extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\Console"/>
```

```
Showing the top 5 slowest tests:
  543 ms: Tests\Feature\ProfileTest::can_upload_new_profile_image
   26 ms: Tests\Feature\ProfileTest::can_visit_profile_page
   25 ms: Tests\Feature\ProfileTest::throws_validation_error_if_password_not_match
```

Default options are:

- rows: `5`

## Csv

Write the tests in a CSV file ready for import.

```xml
<extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\Csv"/>
```

Default options are:

- rows: `null` (all the tests)
- file: `phpunit_results.csv`

## Json

Write the tests in a JSON file ready for import.

```xml
<extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\Json"/>
```

Default options are:

- rows: `null` (all the tests)
- file: `phpunit_results.json`

### MySQL

Store the test name and the time into a MySQL database. It will override existing records

```xml
<extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\MySQL"/>
```

Default credentials are (as array):

- rows: `null` (all the tests)
- database: `phpunit_results`
- table: `default`
- username: `root`
- password: ``
- host: `127.0.0.1`

### SQLite

Store the test name and the time into a SQLite database. It will override existing records

```xml
<extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\SQLite"/>
```

Default credentials are (as array):

- rows: `null` (all the tests)
- database: `phpunit_results.db`
- table: `default`

## Arguments

To override the default configuration per extension, you need to use `<arguments>`in your `phpunit.xml` file

```xml
<extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\Json">
  <arguments>
    <integer>10</integer>
    <string>phpunit_results_as_json.json</string>
  </arguments>
</extension>
```

In the case of the MySQL and SQLite, which needs a database connection, configuration goes as array

<extension class="Lloople\PHPUnitExtensions\Runners\SlowestTests\MySQL">
  <arguments>
    <null/> <!-- This allows you to log all the tests -->
    <array>
      <element key="database">
        <string>my_phpunit_results</string>
      </element>
      <element key="table">
        <string>project1_test_results</string>
      </element>
      <element key="username">
        <string>homestead</string>
      </element>
      <element key="password">
        <string>secret</string>
      </element>
      <element key="host">
        <string>192.168.12.14</string>
      </element>
    </array>
  </arguments>
</extension>
```

You don't need to override those credentials that already fit to your 
usecase, since the class will merge your configuration with the default one

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email d.lloople@icloud.com instead of using the issue tracker.

## Credits

- [David Llop](https://github.com/lloople)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.