<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
$start_time = microtime(true);
require '../vendor/autoload.php';
require '../src/PHPLater.php';
include 'classes/TestPlate.php';
include 'classes/Test.php';
include 'classes/Data.php';

$return = [];
$runs = 1;
$last_memory = $mid_memory = $first_memory = 0;
for ($y = 0; $y < $runs; $y++) {
    try {
        $phplater = new PHPlater();
    } catch (Exception $ex) {
        echo '<pre>' . print_r($ex, true) . '</pre>';
    }

    $phplater->setCache(true);
    $phplater->setPlate('test_plate', new TestPlate());
    $phplater->setPlate('test', new Test());
    $phplater->setPlate('plain', 'This text is injected directly into PHPlater');
    $phplater->setPlate('nested', 'Hello from the nest');

    $phplater_from_text = new PHPlater();
    //$phplater_from_text->setCache(true);
    $phplater_from_text->setContent('<b>{{ text }}</b> {{ text_two }}');
    $phplater_from_text->setPlate('text', 'This text is put into teplate string before adding to file.<br />Also, nesting the template: <b>{{ nested }}</b>');
    $phplater_from_text->setPlate('text_two', ', This however is <i>not nested</i>');
    $phplater->setPlate('no_file', $phplater_from_text->render());
    $phpplater_json = new PHPlater();
    //$phpplater_json->setCache(true);
    $phpplater_json->setPlates('{"eight": "Kahdeksan"}');
    $phplater_array = new PHPlater();
    //$phplater_array->setCache(true);
    $phplater_array->setPlates([
        'one' => 'Yksi',
        'two' => new Test(),
        'assoc' => [
            'three' => 'Kolme',
            4 => 'Neljä',
            5 => ['Viisi'],
            'six' => ['Kuusi'],
            'test' => '{ "testi": "Testattu" }'
        ],
        'seven_obj' => new Test(),
        'from_json' => $phpplater_json
    ]);
    $phplater_array->setFilter('nineFunc', function (string $nine): ?string {
        return $nine == 'nine' ? 'Yhdeksän' : 'Undefined';
    });

    $phplater_array->setFilter('strtoupper', 'mb_strtoupper');
    $phplater_array->setFilter('lowercase', 'mb_strtolower');
    $phplater_array->setFilter('implode', function (array|string $data) {
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
            Ten: {{ tens.0|implode|lowercase }}<br />
            Test: {{ assoc.test.testi }}
       </div>
    '));

    $return['first'] = $phplater->render('./templates/test_page.tpl', 1);

    $phplater = new PHPlater();
    //$phplater->setCache(true);
    $phplater->setMany(true)->setPlates([
        ['assoc' => ['Yksitoista']],
        ['assoc' => ['Kaksitoista']],
        ['assoc' => ['Kolmetoista']]
    ]);

    $return['second'] = '<ul>' . $phplater->render('<li>{{ assoc.0 }}</li>') . '</ul>';

    $phplater = new PHPlater();
    //$phplater->setCache(true);
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
    //$phplater->setCache(true);
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
    //$phplater->setCache(true);
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
        Value 1 is (( {{assoc.value.1.fi}} ?? {{assoc.value.1.fi}} should not appear )) <br>
        Value 0 is (( {{assoc.value.0.fi}} ?? <b>{{assoc.value.0.fi}}</b> :: {{assoc.value.0.uk}} )) <br>
        Value 1 is (( {{assoc.value.1.fi}} ?? {{assoc.value.1.fi}} :: Kaksikymmentäseitsemän )) <br>
        Value is (( 1 == 1 ?? true :: false )) <br>
        Value is (( 1 == 2 ?? true :: false )) <br>
        Value is (( {{assoc.value.1.fi}} == Kaksikymmentäseitsemän ?? true :: false )) <br>
    ');

    $number = 3;
    $scalarValues = [];
    for ($i = 0; $i < $number; $i++) {
        $scalarValues[] = 'val ' . $i;
    }

    $arrayValues = [];
    for ($i = 0; $i < $number; $i++) {
        $arrayValues[] = [
            'id' => $i,
            'name' => 'name',
        ];
    }

    $objectValues = [];
    for ($i = 0; $i < $number; $i++) {
        $object = new Data('name');
        $object->id = $i;

        $objectValues[] = $object;
    }

    $combinedValues = [];
    for ($i = 0; $i < $number; $i++) {
        $name = 'name';

        $object = new Data($name);
        $object->id = $i;

        $combinedValues[] = [
            'id' => $i,
            'name' => $name,
            'object' => $object,
        ];
    }

    $variables = [
        'scalarValues' => $scalarValues,
        'arrayValues' => $arrayValues,
        'objectValues' => $objectValues,
        'combinedValues' => $combinedValues,
    ];
    $phplater = new PHPlater();
    //$phplater->setCache(true);
    $phplater->setPlates($variables);
    $return['sixth'] = $phplater->render('
        <ul>
            [[ <li>{{ scalarValues.. }}</li> ]]
        </ul>

        <table>
        [[
            <tr>
                <td>{{ arrayValues..id }}</td>
                <td>{{ arrayValues..name }}</td>
            </tr>
        ]]
        </table>

        <table>
        [[
            <tr>
                <td>{{ objectValues..id }}</td>
                <td>{{ objectValues..getName }}</td>
            </tr>
        ]]
        </table>

        <table>
        [[
            <tr>
                <td>{{ combinedValues..id }}</td>
                <td>{{ combinedValues..name }}</td>
                <td>{{ combinedValues..object.id }}</td>
                <td>{{ combinedValues..object.getName }}</td>
            </tr>
        ]]
        </table>
    ');
    $phplater = new PHPlater();
    $phplater->setPlate('test', 'value');
    $phplater->setPlate('test_value', 'Should not show');
    $return['seventh'] = $phplater->render('<br>With no render: \'\' ./templates/test_no_value.tpl \'\'');
    $return['eight'] = $phplater->render('With render: \'\' ./templates/test_value.tpl|render \'\'');

    $phplater = new PHPlater();
    $return['ninth'] = $phplater->render('
        Set varaible to: {{test => varaible set before}}varaible set before<br>Get variable: {{test}}<br><br>
        Set varaible to: {{test_array => [1,2,3,"this here"]}}[1,2,3,"this here"]<br>Get variable from array index 3: {{test_array.3}}<br><br>
    ');

    $phplater = new PHPlater();
    $phplater->setPlates([
        'list' => [
            ['value' => ['this']],
            ['value' => ['ok']]
        ]
    ]);
    $phplater->setTagsList('-foreach-', '-end-');
    $return['tenth'] = $phplater->render('<ul>-foreach-<li>{{ list..value.0 }}</li>-end-</ul>');

    $phplater = new PHPlater();
    $phplater->setPlates([
        'list' => [
            ['value' => ['this']],
            ['value' => ['ok']]
        ]
    ]);

    //PHPlaterBase::debug($phplater);
    $return['eleventh'] = $phplater->render('1. this is (( {{ list.0.value.0 }} ?? {{ list.1.value.0 }} :: not ok ))') . '<br>'
            . $phplater->render('2. this is (( {{ list.0.value.2 }} ?? not ok :: {{ list.1.value.0 }} ))') . '<br>'
            . $phplater->render('3. this is (( {{ list.0.value.0 }} ?? ok ))') . '<br>'
            . $phplater->render('4. this is (( {{ list.0.value.1 }} ?? :: ok ))') . '<br>'
            . $phplater->render('5. this is (( {{ list.0.value.0 }} ?? <b>{{ list.1.value.0 }}</b> :: not ok ))') . '<br>';

    $phplater = new PHPlater();
    //$phplater->setCache(true);
    $phplater->setPlates([
        'list' => [
            ['value' => ['this' => 'ok']]
        ]
    ]);
    $template = '<ul>[[<li>{{ # }} {{ list.0.value.. }}</li>]]</ul><br><br>';
    $return['twelvth'] = $phplater->render($template);

    $phplater = new PHPlater();
    //$phplater->setCache(true);
    $phplater->setPlate('test_value', 'Test Value');
    $template = '
    Init block: << one_block >> //print "One Value"<br>
    Test block: << test_block|render => {{test_value}} >> //print "Test Value"<br>
    and unblock it here: << test_block >> //print "Test Value"<br>
    double down here: << second_test|render => <b>With Love</b> >> //print "With Love"<br>
    and unblock here: << second_test >> //print "With Love"<br>
    add value to Init block here: << one_block => One Value >> //print "" (but sets one_block with value "One Value")<br><br>';

    $return['thirteenth'] = $phplater->render($template);

    $phplater = new PHPlater();
    $phplater->setPlate('world', 'Hello');
    $phplater->setPlate('hello', 'World');

    $return['fourteenth'] = $phplater->render('<<hello>>! << hello|render => Hello {{ hello }} >>! <<hello>>!<br><br><br>');

    if ($y == ceil($runs / 2) - 1) {
        $mid_memory = memory_get_usage() / 1024;
    }
    if ($y == 0) {
        $first_memory = memory_get_usage() / 1024;
    }
    if ($y == $runs - 1) {
        $last_memory = memory_get_usage() / 1024;
    }
}
echo implode('', $return);
/**
 * TODO:
 * Imagining a future implementation of an if else
 * (( {{ test }} | {{ true }} | {{ false }} ))
 * (( {{ test }} ?? {{ true }} :: {{ false }} ))
 */
echo 'Page generated in ' . round(microtime(true) - $start_time, 3) . ' seconds with ' . $runs . ' runs.<br><br>';
echo 'Memory usage first: ' . round($first_memory, 3) . ' KB, and mid: ' . round($mid_memory / 1024, 3) . ' KB.<br><br>';
echo 'Memory usage last: ' . round($last_memory, 3) . ' KB, and end: ' . round(memory_get_usage() / 1024, 3) . ' KB.<br><br>';
//PHPlater::debug(PHPlaterBase::$content_cache);
//echo 'Timer: ' . round(PHPlater::$timer, 3) . 'seconds for str_contains.';
