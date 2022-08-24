<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$start_time = microtime(true);
require '../vendor/autoload.php';
require '../src/PHPLater.php';
include 'classes/TestPlate.php';
include 'classes/Test.php';

$return = [];
$runs = 1000;
for ($y = 0; $y < $runs; $y++) {
    try {
        $phplater = new PHPlater();
    } catch (Exception $ex) {
        echo '<pre>' . print_r($ex, true) . '</pre>';
    }

    $phplater->setPlate('test_plate', new TestPlate());
    $phplater->setPlate('test', new Test());
    $phplater->setPlate('plain', 'This text is injected directly into PHPlater');
    $phplater->setPlate('nested', 'Hello from the nest');

    $phplater_from_text = new PHPlater();
    $phplater_from_text->setContent('<b>{{ text }}</b> {{ text_two }}');
    $phplater_from_text->setPlate('text', 'This text is put into teplate string before adding to file.<br />Also, nesting the template: <b>{{ nested }}</b>');
    $phplater_from_text->setPlate('text_two', ', This however is <i>not nested</i>');
    $phplater->setPlate('no_file', $phplater_from_text->render());
    $phpplater_json = new PHPlater();
    $phpplater_json->setPlates('{"eight": "Kahdeksan"}');
    $phplater_array = new PHPlater();
    $phplater_array->setPlates([
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
    $phplater_array->setFilter('nineFunc', function (string $nine): ?string {
        return $nine == 'nine' ? 'Yhdeksän' : 'Undefined';
    });
    $phplater_array->setFilter('strtoupper', 'mb_strtoupper');
    $phplater_array->setFilter('lowercase', 'mb_strtolower');
    $phplater_array->setFilter('implode', function (array $data) {
        return implode('', $data);
    });
    $phplater_array->setPlate('tens', [['K', 'y', 'm', 'm', 'e', 'n', 'e', 'n']]);
    $phplater_array->setPlate('nine', 'nine');
    $phplater->setPlate('from_array', $phplater_array->render('
        <div>
            One: {{ one }}<br />
            Two: {{ two.getTwo }}<br />
            Three: {{ assoc.three }}<br />
            Four: {{ assoc.4 }}<br />
            Five: {{ assoc.5.0 }}<br />
            Six: {{ assoc.six.0 }}<br />
            Seven: {{ seven_obj.returnArray.seven }}<br />
            Eight: {{ from_json.eight }}<br />
            Nine: {{ nine|nineFunc|strtoupper }}<br />
            Ten: {{ tens.0|implode|lowercase }}
       </div>
    '));

    $return['first'] = $phplater->render('./templates/test_page.tpl');

    $phplater = new PHPlater();
    $phplater->setMany(true)->setPlates([
        ['assoc' => ['Yksitoista']],
        ['assoc' => ['Kaksitoista']],
        ['assoc' => ['Kolmetoista']]
    ]);

    $return['second'] = '<ul>' . $phplater->render('<li>{{ assoc.0 }}</li>') . '</ul>';

    $phplater = new PHPlater();
    $phplater->setPlates([
        'list' => [
            ['assoc' => [
                    'english' => 'Fourteen',
                    'finnish' => 'Neljätoista'
                ]
            ],
            ['assoc' => [
                    'english' => 'Fifteen',
                    'finnish' => 'Viisitoista'
                ]
            ],
            ['assoc' => [
                    'english' => 'Sixteen',
                    'finnish' => 'Kuusitoista'
                ]
            ]
        ],
        'values' => [
            'Seitsemäntoista', 'Kahdeksantoista', 'Yhdeksäntoista'
        ],
        'deep' => [
            'deeper' => [
                'deepening' => [
                    'deepest' => [['Kaksikymmentä'], ['Kaksikymmentäyksi'], ['Kaksikymmentäkaksi']]
                ]
            ]
        ]
    ]);
    $return['third'] = $phplater->render('
        <ul>[[<li>{{ list..assoc.english }}: {{ list..assoc.finnish }}</li>]]</ul>
        <ul>[[<li>{{ values.. }}</li>]]</ul>
        <ul>[[<li>{{ deep.deeper.deepening.deepest..0 }}</li>]]</ul>
    ');

    $phplater = new PHPlater();
    $phplater->setPlates([
        ['assoc' => [
                'english' => 'Twentythree',
                'finnish' => 'Kaksikymmentäkolme'
            ]
        ],
        ['assoc' => [
                'english' => 'Twentyfour',
                'finnish' => 'Kaksikymmentäneljä'
            ]
        ],
        ['assoc' => [
                'english' => 'Twentyfive',
                'finnish' => 'Kaksikymmentäviisi'
            ]
        ]
    ]);
    $return['fourth'] = $phplater->render('
        <ul>[[<li>{{ ..assoc.english }}: {{ ..assoc.finnish }}</li>]]</ul>
    ');

    $phplater = new PHPlater();
    $phplater->setPlates([
        'assoc' => [
            'value' => [
                [
                    'uk' => '',
                    'fi' => 'Kaksikymmentäkuusi'
                ], [
                    'uk' => 'Twentyseven',
                    'fi' => ''
                ]
            ]
        ]
    ]);
    $return['fifth'] = $phplater->render('
        Value 0 is (( {{assoc.value.0.fi}} ?? {{assoc.value.0.fi}} :: {{assoc.value.0.uk}} )) <br>
        Value 1 is (( {{assoc.value.1.fi}} ?? {{assoc.value.1.fi}} :: {{assoc.value.1.uk}} )) <br>
        Value 0 is (( {{assoc.value.0.fi}} ?? :: {{assoc.value.0.uk}} )) <br>
        Value 1 is (( {{assoc.value.1.fi}} ?? :: {{assoc.value.1.uk}} )) <br>
        Value 0 is (( {{assoc.value.0.fi}} ?? {{assoc.value.0.fi}} )) <br>
        Value 1 is (( {{assoc.value.1.fi}} ?? {{assoc.value.1.fi}} )) <br>
        Value 0 is (( {{assoc.value.0.fi}} ?? <b>{{assoc.value.0.fi}}</b> :: {{assoc.value.0.uk}} )) <br>
        Value 1 is (( {{assoc.value.1.fi}} ?? {{assoc.value.1.fi}} :: Kaksikymmentäseitsemän )) <br>
        Value is (( 1 == 1 ?? true :: false )) <br>
        Value is (( 1 == 2 ?? true :: false )) <br>
        Value is (( {{assoc.value.1.fi}} == Kaksikymmentäseitsemän ?? true :: false )) <br>
    ');
}
echo implode('', $return);
/**
 * Imagining a future implementation of an if else
 * (( {{ test }} | {{ true }} | {{ false }} ))
 * (( {{ test }} ?? {{ true }} :: {{ false }} ))
 */
echo 'Page generated in ' . round(microtime(true) - $start_time, 3) . ' seconds with ' . $runs . ' runs.';
