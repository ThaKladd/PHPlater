<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../vendor/autoload.php';
include 'classes/TestPlate.php';
include 'classes/Test.php';

$phplater = new PHPlater();
$phplater->plate('test_plate', new TestPlate());
$phplater->plate('test', new Test());
$phplater->plate('plain', 'This text is injected directly into PHPlater');
$phplater->plate('nested', 'Hello from the nest');

$phplater_from_text = new PHPlater();
$phplater_from_text->content('<b>{{ text }}</b>');
$phplater_from_text->plate('text', 'This text is put into teplate string before adding to file.<br />Also, nesting the template: <b>{{ nested }}</b>');
$phplater->plate('no_file', $phplater_from_text->Render());

$phpplater_json = new PHPlater();
$phpplater_json->plates('{"eight": "Kahdeksan"}');

$phplater_array = new PHPlater();
$phplater_array->plates([
    'one' => 'Yksi',
    'two' => new Test(),
    'assoc' => [
        'three' => 'Kolme',
        4 => 'Neljä',
        5 => ['Viisi'],
        'six' => ['Kuusi']
    ],
    'seven_obj' => new Test(),
    'from_json' => $phpplater_json
]);

$phplater->plate('from_array', $phplater_array->render('
    <div>
        One: {{ one }}<br />
        Two: {{ two.getTwo }}<br />
        Three: {{ assoc.three }}<br />
        Four: {{ assoc.4 }}<br />
        Five: {{ assoc.5.0 }}<br />
        Six: {{ assoc.six.0 }}<br />
        Seven: {{ seven_obj.returnArray.seven }}<br />
        Eight: {{ from_json.eight }}
   </div>
'));

$phplater->plate('from_json',);

echo $phplater->render('./templates/test_page.tpl');
