# PHPlater
A simple PHP template engine that lets PHP do all the logic and then append it to the HTML in the template file. It is set to solve the problem of mixing too much logic into the template file itself and by that loosing some control over where the logic happens. You also get a bit more control over the data that is passed down to the template, and the code is easier to manage and to track.

---
- [Installation](#installation)
- [Simple Usage](#simple-usage)
- [Advanced Usage](#advanced-usage)
- [Contributing](#contributing)
- [Documentation](#documentation)
- [License](#licence)
---

## Installation

PHP 8.0 or higher is needed for this class to work.

Use the package manager [composer](https://getcomposer.org/) to install PHPlater.

```bash
composer install phplater/phplater
```

## Simple Usage

**Given this PHP code**
```php
$phplater = new PHPlater();
$phplater->plate('hello', 'world!');
echo $phplater->render('Hello {{hello}}');
```
**This will be the output:**
```
Hello world!
```
## Advanced Usage

Some more examples are available under [/examples](https://github.com/ThaKladd/PHPlater/tree/master/examples) and [/tests](https://github.com/ThaKladd/PHPlater/tree/master/tests)

**Given this template.tpl file**
```html
<div>
    One: {{ one }}<br />
    Two: {{ two.getTwo }}<br />
    Three: {{ assoc.three }}<br />
    Four: {{ assoc.4 }}<br />
    Five: {{ assoc.5.0 }}<br />
    Six: {{ assoc.six.0 }}<br />
    Seven: {{ seven_obj.returnArray.seven }}
</div>
```

**And this PHP code**
```php
class Test {
    function getTwo() {
        return 'Kaksi';
    }
    public function returnArray() {
        return ['seven' => 'Seitsemän'];
    }
}

$phplater = new PHPlater();
$phplater->plates([
    'one' => 'Yksi',
    'two' => new Test(),
    'assoc' => [
        'three' => 'Kolme',
        4 => 'Neljä',
        5 => ['Viisi'],
        'six' => ['Kuusi']
    ],
    'seven_obj' => new Test()
]);

echo $phplater->render('template.tpl');
```

**This will be the output:**
```html
<div>
    One: Yksi<br />
    Two: Kaksi<br />
    Three: Kolme<br />
    Four: Neljä<br />
    Five: Viisi<br />
    Six: Kuusi<br />
    Seven: Seitsemän 
</div>
```

### Many

In order to ease the looping a array of similar values, it can be sent inn and iterated over on the same template

**Given this code**
```php
$phplater = new PHPlater();
$phplater->many(true)->plates([
    ['value' => ['this']],
    ['value' => ['is']],
    ['value' => ['ok']]
]);
echo '<ul>'.$phplater->render('<li>{{ value.0 }}</li>').'</ul>';
```

**This will be the output:**
```html
<ul><li>this</li><li>is</li><li>ok</li></ul>
```

There is also a syntax for doing a foreach inside the template using tags and a placeholder without the many method

**Given this code**
```php
$phplater = new PHPlater();
$phplater->plates([
    'list' => [
        ['value' => ['this']],
        ['value' => ['is']],
        ['value' => ['ok']]
    ]
]);
echo $phplater->render('<ul>[[<li>{{ list..value.0 }}</li>]]</ul>');
```

**This will be the output:**
```html
<ul><li>this</li><li>is</li><li>ok</li></ul>
```


### Filters
**Given this code**
```php
$phplater = new PHPlater();
$phplater->filter('uppercase', 'mb_strtoupper');
$phplater->filter('add_ok', function (string $data, string $ok = '') {
    return $data . ' is '.$ok;
});

$phplater->plate('string', 'test');

echo $phplater->render('<b>This {{string|add_ok:ok|uppercase}}</b>');
```

**This will be the output:**
```html
<b>This TEST IS OK</b>
```

## Test

Run the following
```
vendor\bin\phpunit tests/PHPlaterTest.php
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## Issues to fix and features to add
- Make it possible to change argument , separator (low priority)
- Error handling

## Documentation

[PHPDocumentator](https://phpdoc.org/) v.3.0 is used for generating documentation from PHPDoc in code.
Download [phpDocumentor.phar](https://phpdoc.org/phpDocumentor.phar) and run the following in the project folder to update it.

```bash
php c:/path/to/phpDocumentor.phar -d src -t docs
```

## License
[MIT](https://choosealicense.com/licenses/mit/)