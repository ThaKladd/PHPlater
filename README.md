# PHPlater

A simple PHP template engine that intially aimed for PHP to do most of the logic and then append it to the HTML in the template file like a glorified search and replace, but that turned into a more full fledged template engine. The aim is still to make things simple and fast while doing it runtime. Some of the syntax is unique, and the engine itself is very lightweight.

## Why?

As a backend developer, I want to do most of the logic in PHP, and when to seperate HTML out from the code, I usually need a simple variable to value replacement tool. In its simplest form, its just a str_replace, but soon you find that you need something a little bit more powerfull, although still simple. So, I decieded to make a template enginge as so many has done before me. PHPlater is aimed to solve some of the issues I found with other template engines - maybe only to introduce new things for others to have issue with. So, in a nutshell, PHPlater gives you variables with filters, lists and conditionals, with the simplicity of the syntax in mind as well as ease of use without any other dependencies. Every coder is different, and this was made to solve my way of doing it. Maybe there are someone else that finds it attractive too?

---

- [Installation](#installation)
- [Simple Usage](#simple-usage)
- [Advanced Usage](#advanced-usage)
- [Tags](#tags)
- [Test](#test)
- [Contributing](#contributing)
- [Documentation](#documentation)
- [License](#licence)

---

## Installation

PHP 8.0 or higher is needed for this class to work.

Use the package manager [composer](https://getcomposer.org/) to install PHPlater.

```bash
composer require phplater/phplater
```

## Simple Usage

### Given this PHP code

```php
$phplater = new PHPlater();
$phplater->setPlate('hello', 'world!');
echo $phplater->render('Hello {{hello}}');
```

### This will be the output

```bash
Hello world!
```

## Advanced Usage

Some more examples are available under [/examples](https://github.com/ThaKladd/PHPlater/tree/master/examples) and [/tests](https://github.com/ThaKladd/PHPlater/tree/master/tests)

### Given this template.tpl file

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

### And this PHP code

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
$phplater->setPlates([
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

### This will then be the output

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

### Given this code

```php
$phplater = new PHPlater();
$phplater->setMany(true)->setPlates([
    ['value' => ['this']],
    ['value' => ['is']],
    ['value' => ['ok']]
]);
echo '<ul>'.$phplater->render('<li>{{ value.0 }}</li>').'</ul>';
```

### The output will be as follows

```html
<ul><li>this</li><li>is</li><li>ok</li></ul>
```

There is also a syntax for doing a foreach inside the template using tags and a placeholder without the many method

### Given this

```php
$phplater = new PHPlater();
$phplater->setPlates([
    'list' => [
        ['value' => ['this']],
        ['value' => ['is']],
        ['value' => ['ok']]
    ]
]);
echo $phplater->render('<ul>[[<li>{{ list..value.0 }}</li>]]</ul>');
```

### The output wil be like this

```html
<ul><li>this</li><li>is</li><li>ok</li></ul>
```

### Filters

Filters gets inspiration from [Twig](https://github.com/twigphp/Twig) and and come after | tag with arguments to the method inspired by [Latte](https://github.com/nette/latte)

### If code is like this

```php
$phplater = new PHPlater();
$phplater->setFilter('uppercase', 'mb_strtoupper');
$phplater->setFilter('add_ok', function (string $data, string $ok = '') {
    return $data . ' is '.$ok;
});

$phplater->setPlate('string', 'test');

echo $phplater->render('<b>This {{string|add_ok:ok|uppercase}}</b>');
```

### The html results in

```html
<b>This TEST IS OK</b>
```

### Conditionals

The conditional evaluates one or two variables, and return either a true value or a false value. These must have a space before and after the operator and the syntax is as follows.

```php
$phplater = new PHPlater();
$phplater->setPlates([
    'arr' => ['check', 'check', 'true', 'false']
]);
echo $phplater->render('(( {{ arr.0 }} == {{ arr.1 }} ?? <b>{{ arr.2 }}</b> :: {{ arr.3 }} ))');
```

### And the output will be

```html
<b>true</b>
```

These are the supported comparison operations

Operator|Description
---|---
==|Equal
!=|Not equal
!==|Strict not equal
===|Strict equal
\>=|Greater than or equal
\<=|Less than or equal
\>|Greater than
\<|Less than
\<\>|Not equal
\<=\>|Spaceship operator
%|Modulo, reminder (0 is falsy)
&&|And
and|And
\|\||Or
or|Or
xor|Either, but not both

## Tags

There are a minimal amount of tags to remeber in PHPlater, and almost all of them are changeable

Tag|Description|Example
---|---|---
{{ and }}|Start and end tag for template variable|`<li>{{var}}</li>`
.|Chain separator by which to traverse plates|`<li>{{root.var}}</li>`
\||Filter tag to variable, method followes|`<li>{{var`<code>\|</code>`method}}</li>`
:|Seperator if filter method need arguments|`<li>{{var`<code>\|</code>`method:arg1}}</li>`
,|Seperate the arguments given to method|`<li>{{var`<code>\|</code>`method:arg1,arg2}}</li>`
[[ and ]]|Start and end tag for each element in a list|`<ul>[[<li>{{var}}</li>]]</ul>`
\#|To get the key from list|`<ul>[[<li>{{ # }}</li>]]</ul>`
\.\.|Placement of list in the variable chain|`<ul>[[<li>{{list..var}}</li>]]</ul>`
(( and ))|Start and end tag for conditional expression|`(( {{var}} ?? true :: false ))`
??|Tag after condition, followed by true result|`(( {{var}} ?? true ))`
::|Tag after true result, followed by false result|`(( {{var}} ?? :: false ))`
~|Default preg delimiter|

## Test

### PHPUnit

PHPUnit 9.5.21 is used. Download [phpunit.phar](https://phar.phpunit.de/) and, in the root folder of the project, run tests with

```bash
php c:/path/to/phpunit.phar
```

For code coverage, add " --coverage-text" to the command, and use [xDebug](https://xdebug.org/download) or similar of your choice.

Other tools like [psalm](https://github.com/vimeo/psalm) and [PHPStan](https://github.com/phpstan/phpstan/) are used as well for testing to find errors.

### Psalm

```bash
c:/path/to/vendor/bin/psalm --show-info=true --no-cache
```

### PHPStan

```bash
c:/path/to/vendor/bin/phpstan analyse src --level=9
```

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## Issues to fix and features to add

At the moment, very little of known bugs. Improvements and features are multiple. Here is a crude list. 

### Planning following

#### Fixes

~~Fix the issues with getPHPlaterObject and patternCache - both can be improved~~
~~Put constants into enum classes~~
~~See into reducing memory footprint~~

#### Placeholder block

>>block-as-non-existing-plate => values and {{ variables }}<< 
<<block>> or {{non-existing-plate}}

<<block => Test data >> -> sets test data into block -> Set block plate to data and reduce down to {{block}}
if only <<block>> create empty plate as a filler spot and translate into {{block}}
if then later << block => Test data >> overwrites the block in plate, and then renders the block without data

>> block => >>other block<< assign data << 


#### Includes

~~''include file or~~ url ~~content'' -> ''css/style.css'' ''js/script.js'', ''text.txt'', ''template.tpl''~~
~~'''includes and renders'''? -> see twig source. Maybe use filter ''includes.tpl|render''~~

#### Filters that comes with engine

{{ plate|escape }} -> short {{ plate|e }} -> {{ plate|escape:html }}, {{ plate|escape:js }}, {{ plate|escape:css }}, {{ plate|escape:url }} (url_encode), {{ plate|escape:attr }}
{{ plate|raw }} -> unescape? ignore escape?, {{ plate|raw:url }} -> urldecode
{{ plate|raw:striptags }} or {{ plate|escape:striptags }} or {{ plate|striptags }}??
{{ plate|abs }}, {{ plate|floor }}, {{ plate|ceil }}, {{ plate|round }}, {{ plate|decimals:3 }}, {{ plate|thousand:. }}, {{ plate|multiply:4 }}, {{ plate|add:4 }}, {{ plate|divide:4 }}, {{ plate|pow:4 }}, {{ plate|to_comma }}, {{ plate|to_dot }}
{{ plate|number_format:2, '.', ','}}
{{ plate|first }} -> if plate is array or json, or first char if string
{{ plate|last }} -> if plate is array or json, or last char if string
{{ plate|min }} -> array
{{ plate|max }} -> array
{{ plate|random }} -> array or string too? see twig
{{ plate|dump }} or {{ plate|debug }} -> run debug function in Base
{{ plate|log:file,content }} -> maybe as function too?
{{ plate|capitalize }} or also {{ plate|title }} -> or {{ plate|capitalize:words }} and maybe also syphony {{ plate|humanize }}
{{ plate|lowecase }} = {{ plate|lower }} 
{{ plate|uppercase }} = {{ plate|upper }} 
{{ plate|lenght }} -> both array, and string 
{{ plate|json_encode }} -> short {{ plate|json }} -> encodes json
{{ plate|serialize }} -> if object
{{ plate|format }} ->  test %s |format:this }} -> test this 
{{ plate|replace }}  ->  test %s or %y |replace:%s,this,%y,that }} -> test this or that 
{{ plate|cap }} or {{ plate|truncate }} -> abcdefgh|cap:3,... -> abc...
{{ plate|snake }} -> snakecase, {{ plate|camel }} - camelcase -> to unicode too, or use unicode as standard, and then other encodeing as non standard somehow
{{ plate|implode:', ', ' and '}} -> [1,2,3] to 1, 2 and 3 
{{ plate|explode:', '}} or {{ plate|split:', '}} -> maybe as twig does split with limit argument?
{{ plate|date:d.m Y }} 
{{ plate|reverse }} -> number, string, array
{{ plate|nl2br }} and {{ plate|br2nl }}
{{ plate|default:undefined }}
{{ plate|slug:- }} 
{{ plate|unicode:truncate, 5, '...' }} -> short {{ plate|u:truncate }} //unicode
{{ plate|spaceless }} or  {{ plate|trim:' ' }} or {{ plate|remove:' ' }} -> only html between tags? See trim in twig
{{ plate|sort:abc }} -> sort array abc, cba
{{ plate|divby:3 }} 
{{ plate|empty }} 
{{ plate|even }} 
{{ plate|render }} -> Renders on core
{{ plate|odd }} 
{{ plate|null }} 
{{ plate|object }} 
{{ plate|iterable }} -> {{ plate|array }}
{{ plate|absolute }} -> if url -> to make it absolute or maybe {{ plate|realtive }} too? 
{{ plate|start:'a' }} or {{ plate|start_with:'a' }} -> same with end
{{ plate|match:'regex' }}
{{ plate|in:{{plates.list}} }} and {{ plate|not_in:{{plates.list}} }}
{{ plate|is:{{plates.other}} }}
{{ plates|for:''block.tpl'' }} or {{ plates|for:>>block<< }}

Maybe add function syntax to use anywere inside tags or places -> that can also use filters?
Filters are for transforming values, and functions should be for generating content -> but can they be combined?
text text text {{ function(plate) }} text  => text text text {{ escape(plate) }} text 

#### Set Varibales and get them

~~{{ |set_plate:key,value }} or {{ |var:key,value }} or {{ key => value }}~~
~~{{ |get_plate:key }} or {{ |var:key }} or {{ key }}~~
~~Value should be able to be an array -> like {{ key => [1,2,3,4] }} or  {{ key => [1 => value, 2 => value2] }}~~

#### Lists first and last

[[ {{ #|first }} {{ #|last }} ]]

#### Use filters, with no value

{{ []|range:100,1000 }} or {{ |range:100,1000 }} 
{{ ""|lipsum:100 }} or {{ |lipsum:100 }}
Ture and falsy filters can be used in iffs

#### If text in " then can use filter on without tags

"Hello"|tolowercase or ""Hello""|tolowercase - seems unneccessary, unless from other source or result of if
-> removes surrounding "" after

#### Better extendable

Make extendable as twig is with -> $phplater->addGlobal('text', new Text()) -> maybe call it addOwn? addDish?
It can already be added through a plate of course, but if it should be added differently?? Does it even make sense?

Twig have own TwigFilter class that stores filter ref, and addFilter accepts only that -> but it is needed? Is it only there for the options?

#### Plate Objects

For version 2 or 3 -> When plates are added, build a obkject tree with each plate as a object-type such as PHPlateString etc 
- to make sure they follow rules and are easier to operate upon. This may take som toll on performance, but also have other gains

## Documentation

[PHPDocumentator](https://phpdoc.org/) v.3.3.1 is used for generating documentation from PHPDoc in code.
Download [phpDocumentor.phar](https://phpdoc.org/phpDocumentor.phar) and run the following in the project folder to update it.

```bash
php c:/path/to/phpDocumentor.phar -d src -t docs
```

## License

[MIT](https://choosealicense.com/licenses/mit/)