<?php
use PHPUnit\Framework\TestCase;
use Error\RuleBrokenError;

/**
 * @covers  PHPlater
 */
class PHPlaterTest extends TestCase {

    public $phplater;
    public $tpl_file;
    public $test_class;

    static function setUpBeforeClass(): void {
        include_once 'src/PHPlater.php';
        include_once 'src/functions.php';
    }

    function setUp(): void {
        $this->phplater = new PHPlater();
        $this->tpl_file = 'tests/template.tpl';
        $this->test_class = new class {

            public $prop = 'ok';

            public function this() {
                return $this;
            }

            public function arr() {
                return ['arr' => ['ok']];
            }
        };
    }

    function tearDown(): void {
        if (file_exists($this->tpl_file)) {
            unlink($this->tpl_file);
        }
        unset($this->phplater);
        unset($this->test_class);
        unset($this->tpl_file);
    }

    /**
     * @covers  PHPlater::__construct
     * @covers  PHPlater::getTag
     * @covers  PHPlater::tags
     * @covers  PHPlater::tagsVariables
     * @covers  PHPlater::tagsList
     * @covers  PHPlater::tagsConditionals
     */
    public function testConstruct() {
        $this->assertEquals('::', $this->phplater->getTag(PHPlater::TAG_ELSE));
        $this->assertEquals(preg_quote('(('), $this->phplater->getTag(PHPlater::TAG_CONDITIONAL_BEFORE));
        $this->assertEquals(preg_quote('[['), $this->phplater->getTag(PHPlater::TAG_LIST_BEFORE));
        $this->assertEquals(preg_quote('{{'), $this->phplater->getTag(PHPlater::TAG_BEFORE));
    }

    /**
     * @covers  PHPlater::render
     * @covers  PHPlater::setPlate
     * @covers  PHPlater::result
     * @covers  PHPlater::renderCallback
     * @covers  PHPlaterVariable::pattern
     */
    public function testSimpleRender() {
        $this->phplater->setPlate('string', 'ok');
        $this->phplater->setPlate('int', 10);
        $this->phplater->setPlate('json', '{"value":"ok"}');
        $this->phplater->setPlate('array', ['ok']);
        $this->phplater->setPlate('array_assoc', ['value' => 'ok']);
        $this->phplater->setPlate('object', (object) ['value' => 'ok']);

        $this->assertEquals('test ok', $this->phplater->render('test {{string}}'));
        $this->assertEquals('test 10', $this->phplater->render('test {{int}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{json.value}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{array.0}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{array_assoc.value}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{object.value}}'));
    }

    /**
     * @covers  PHPlater::render
     * @covers  PHPlater::setPlate
     */
    public function testVariableWhitespace() {
        $this->phplater->setPlate('string', 'is');
        $this->phplater->setPlate('number', '5.');
        $this->phplater->setPlate('test', 'test');
        $this->phplater->setPlate('good', 'ok');
        $this->assertEquals('1. test is ok', $this->phplater->render('1. test {{string}} ok'));
        $this->assertEquals('2. test is ok', $this->phplater->render('2. test {{ string}} ok'));
        $this->assertEquals('3. test is ok', $this->phplater->render('3. test {{string }} ok'));
        $this->assertEquals('4. test is ok', $this->phplater->render('4. test {{ string }} ok'));
        $this->assertEquals('5. test is ok', $this->phplater->render('{{ number }} {{test }} {{ string}} {{good}}'));
        $this->phplater->setPlate('number', '6.');
        $this->assertEquals('6. test is ok', $this->phplater->render('{{ number  }} {{test  }} {{  string}} {{    good       }}'));
    }

    /**
     * @covers  PHPlater::render
     * @covers  PHPlater::setPlate
     * @covers  PHPlater::setPlates
     */
    public function testArrayAsPlates() {
        $this->phplater->setPlates(['assoc' => 'test', 2 => 'ok']);
        $this->phplater->setPlate(5, '!');
        $this->assertEquals('test ok!', $this->phplater->render('{{assoc}} {{2}}{{5}}'));
    }

    /**
     * @covers  PHPlater::render
     * @covers  PHPlater::setPlates
     * @covers  PHPlater::setContent
     */
    public function testAdvancedRender() {
        file_put_contents($this->tpl_file, '{{str}}, {{json.value}}, {{json.arr.0.value}}, {{obj.prop}}, {{obj.this.prop}}, {{obj.arr.arr.0}}, {{arr.arr.arr.value}}, {{arr.obj.prop}}');
        $plates = [
            'str' => 'ok',
            'json' => '{"value": "ok", "arr": [{"value": "ok"}]}',
            'obj' => $this->test_class,
            'arr' => ['arr' => ['arr' => ['value' => 'ok']], 'obj' => $this->test_class]
        ];
        $this->phplater->setPlates($plates);
        $this->phplater->setContent($this->tpl_file);
        $this->assertEquals($this->phplater->ifJsonToArray($plates), $this->phplater->getPlates());
        $this->assertEquals('ok, ok, ok, ok, ok, ok, ok, ok', $this->phplater->render());
    }

    /**
     * @covers  PHPlater::setTag
     * @covers  PHPlater::setPlate
     * @covers  PHPlater::render
     * @covers  PHPlater::setContent
     * @covers  PHPlater::extract
     */
    public function testChainSeperator() {
        $this->phplater->setPlate('arr', ['arr' => ['arr' => ['value' => 'ok']]]);
        $this->phplater->setContent('{{ arr->arr->arr->value }}');
        $this->phplater->setTag(PHPlater::TAG_CHAIN, '->');
        $this->assertEquals('ok', $this->phplater->render());
    }

    /**
     * @covers  PHPlater::ifJsonToArray
     */
    public function testIfJsonToArray() {
        $this->assertEquals(['value' => 'ok'], $this->phplater->ifJsonToArray('{"value": "ok"}'));
        $this->assertEquals('value ok', $this->phplater->ifJsonToArray('value ok'));
        $this->assertEquals(1, $this->phplater->ifJsonToArray(1));
        $this->assertEquals(['value' => 'ok'], $this->phplater->ifJsonToArray(['value' => 'ok']));
        $this->assertEquals($this->test_class, $this->phplater->ifJsonToArray($this->test_class));
    }

    /**
     * @covers  PHPlater::contentify
     */
    public function testContentify() {
        file_put_contents($this->tpl_file, 'value ok');
        $this->assertEquals('value ok', $this->phplater->setContent($this->tpl_file));
        $this->assertEquals('value ok', $this->phplater->setContent('value ok'));
    }

    /**
     * @covers  PHPlater::setContent
     */
    public function testContent() {
        file_put_contents($this->tpl_file, 'value ok');
        $this->phplater->setContent($this->tpl_file);
        $this->assertEquals('value ok', $this->phplater->getContent());
    }

    /**
     * @covers  PHPlater::render
     */
    public function testEmptyRender() {
        file_put_contents($this->tpl_file, '');
        $this->assertEquals('', $this->phplater->render($this->tpl_file));
    }

    /**
     * @covers  PHPlater::setPlate
     */
    public function testPlate() {
        $this->phplater->setPlate('test', 'ok');
        $this->assertEquals('ok', $this->phplater->getPlate('test'));
    }

    /**
     * @covers  PHPlater::tagsVariables
     * @covers  PHPlater::getTag
     */
    public function testTags() {
        $this->phplater->setPlate('string', 'ok');
        $this->phplater->setTagsVariables('<!--', '-->');
        $this->assertEquals(preg_quote('<!--'), $this->phplater->getTag(PHPlater::TAG_BEFORE));
        $this->assertEquals(preg_quote('-->'), $this->phplater->getTag(PHPlater::TAG_AFTER));
        $this->assertEquals('test ok', $this->phplater->render('test <!-- string -->'));
    }

    /**
     * @covers  PHPlater::setTag
     */
    public function testDelimiter() {
        $this->phplater->setPlate('string', 'ok');
        $this->phplater->setTag(PHPlater::TAG_DELIMITER, '=');
        $this->assertEquals('test ok', $this->phplater->render('test {{string}}'));
    }

    /**
     * @covers  PHPlaterFilter::setFilter
     * @covers  PHPlaterFilter::getFiltersAndParts
     * @covers  PHPlater::extract
     * @covers  PHPlaterFilter::callFilters
     */
    public function testFilter() {
        $this->phplater->setFilter('uppercase', 'mb_strtoupper');
        $this->phplater->setFilter('lowercase', 'mb_strtolower');
        $this->phplater->setFilter('add_ok', function (string $data) {
            return $data . ' ok';
        });
        $this->phplater->setFilter('implode', function (array $data) {
            return implode('', $data);
        });

        $this->phplater->setPlate('string_one', 'ok');
        $this->assertEquals('test OK', $this->phplater->render('test {{string_one|uppercase}}'));

        $this->phplater->setPlate('string_two', 'OK');
        $this->assertEquals('test ok', $this->phplater->render('test {{string_two|lowercase}}'));

        $this->phplater->setPlate('string_three', 'test');
        $this->assertEquals('TEST OK', $this->phplater->render('{{string_three|add_ok|uppercase}}'));

        $this->phplater->setPlate('o', ['o', 'k']);
        $this->phplater->setPlate('okey', [['o', 'k']]);
        $this->phplater->setPlate('oks', '{{okey.0|implode}}');

        $this->assertEquals('test OK', $this->phplater->render('test {{o|implode|uppercase}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{okey.0|implode}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{oks}}'));
    }

    /**
     * @covers  PHPlaterFilter::setFilter
     * @covers  PHPlater::getTag
     * @covers  PHPlaterFilter::getFunctionAndArguments
     * @covers  PHPlater::extract
     * @covers  PHPlaterFilter::callFilters
     */
    public function testFilterArguments() {
        $this->phplater->setPlate('testing', 'test');
        $this->phplater->setFilter('add_args', function (string $test, string $is = 'no', string $ok = 'no') {
            return $test . ' ' . $is . ' ' . $ok;
        });
        $this->assertEquals('test is ok', $this->phplater->render('{{testing|add_args:is,ok}}'));
    }

    /**
     * @covers  PHPlater::setTag
     */
    public function testArgumentSeperator() {
        $this->phplater->setPlate('testing', 'test');
        $this->phplater->setTag(PHPlater::TAG_ARGUMENT, '>');
        $this->phplater->setFilter('add_args', function (string $test, string $is = 'no', string $ok = 'no') {
            return $test . ' ' . $is . ' ' . $ok;
        });
        $this->assertEquals('test is ok', $this->phplater->render('{{testing|add_args>is,ok}}'));
    }

    /**
     * @covers  PHPlater::setTag
     */
    public function testArgumentListSeperator() {
        $this->phplater->setPlate('testing', 'test');
        $this->phplater->setTag(PHPlater::TAG_ARGUMENT_LIST, '_');
        $this->phplater->setFilter('add_args', function (string $test, string $is = 'no', string $ok = 'no') {
            return $test . ' ' . $is . ' ' . $ok;
        });
        $this->assertEquals('test is ok', $this->phplater->render('{{testing|add_args:is_ok}}'));
    }

    /**
     * @covers  PHPlater::setTag
     */
    public function testFilterSeperator() {
        $this->phplater->setFilter('uppercase', 'mb_strtoupper');
        $this->phplater->setFilter('add_ok', function (string $data) {
            return $data . ' is ok';
        });
        $this->phplater->setPlate('string', 'test');
        $this->phplater->setTag(PHPlater::TAG_FILTER, '¤');
        $this->assertEquals('This TEST IS OK', $this->phplater->render('This {{string¤add_ok¤uppercase}}'));
    }

    /**
     * @covers  PHPlater::setMany
     */
    public function testMany() {
        $this->phplater->setMany(true);
        $this->phplater->setPlates([
            ['value' => ['this']],
            ['value' => ['test']],
            ['value' => ['is']],
            ['value' => ['ok']]
        ]);
        $this->assertEquals('<li>this</li><li>test</li><li>is</li><li>ok</li>', $this->phplater->render('<li>{{ value.0 }}</li>'));
    }

    /**
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::find
     * @covers  PHPlaterList::getList
     */
    public function testListFirst() {
        $this->phplater->setPlates([
            ['value' => ['this']],
            ['value' => ['ok']]
        ]);
        $this->assertEquals('<ul><li>this</li><li>ok</li></ul>', $this->phplater->render('<ul>[[<li>{{ ..value.0 }}</li>]]</ul>'));
    }

    /**
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::find
     * @covers  PHPlaterList::getList
     */
    public function testListOnly() {
        $this->phplater->setPlates(['this', 'ok']);
        $this->assertEquals('<ul><li>this</li><li>ok</li></ul>', $this->phplater->render('<ul>[[<li>{{ .. }}</li>]]</ul>'));
    }

    /**
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::find
     * @covers  PHPlaterList::getList
     */
    public function testListLast() {
        $this->phplater->setPlates([
            'list' => ['this', 'ok']
        ]);
        $this->assertEquals('<ul><li>this</li><li>ok</li></ul>', $this->phplater->render('<ul>[[<li>{{ list.. }}</li>]]</ul>'));
    }

    /**
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::pattern
     * @covers  PHPlater::findList
     * @covers  PHPlaterList::getList
     */
    public function testList() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => ['this']],
                ['value' => ['ok']]
            ]
        ]);
        $this->assertEquals('<ul><li>this</li><li>ok</li></ul>', $this->phplater->render('<ul>[[<li>{{ list..value.0 }}</li>]]</ul>'));
    }

    /**
     * @covers  PHPlater::tagsList
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::find
     * @covers  PHPlaterList::getList
     */
    public function testListTags() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => ['this']],
                ['value' => ['ok']]
            ]
        ]);
        $this->phplater->setTagsList('-foreach-', '-end-');
        $this->assertEquals('<ul><li>this</li><li>ok</li></ul>', $this->phplater->render('<ul>-foreach-<li>{{ list..value.0 }}</li>-end-</ul>'));
    }

    /**
     * @covers  PHPlaterList::pattern
     * @covers  PHPlater::pattern
     * @covers  PHPlaterList::find
     * @covers  PHPlaterList::getList
     * @covers  PHPlaterKey::pattern
     * @covers  PHPlaterKey::replaceKeys
     */
    public function testListAssocWithKey() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => ['this' => 'ok']]
            ]
        ]);
        $this->assertEquals('<ul><li>this ok</li></ul>', $this->phplater->render('<ul>[[<li>{{ # }} {{ list.0.value.. }}</li>]]</ul>'));
    }

    /**
     * @covers  PHPlaterList::pattern
     * @covers  PHPlaterList::find
     * @covers  PHPlaterList::getList
     * @covers  PHPlaterKey::pattern
     * @covers  PHPlaterKey::replaceKeys
     * @covers  PHPlater::setTag
     */
    public function testListAssocWithKeyChange() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => ['this' => 'ok']]
            ]
        ]);
        $this->phplater->setTag(PHPlater::TAG_LIST_KEY, '+');
        $this->assertEquals('<ul><li>this ok</li></ul>', $this->phplater->render('<ul>[[<li>{{ + }} {{ list.0.value.. }}</li>]]</ul>'));
    }

    /**
     * @covers  PHPlaterConditional::doOpreration
     * @covers  PHPlaterConditional::findConditional
     * @covers  PHPlaterConditional::evaluateOperation
     * @covers  PHPlaterConditional::patternForConditional
     * @covers  PHPlaterConditional::pattern
     */
    public function testConditionals() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => ['this']],
                ['value' => ['ok']]
            ]
        ]);
        $this->assertEquals('1. this is ok', $this->phplater->render('1. this is (( {{ list.0.value.0 }} ?? {{ list.1.value.0 }} :: not ok ))'));
        $this->assertEquals('2. this is ok', $this->phplater->render('2. this is (( {{ list.0.value.2 }} ?? not ok :: {{ list.1.value.0 }} ))'));
        $this->assertEquals('3. this is ok', $this->phplater->render('3. this is (( {{ list.0.value.0 }} ?? ok ))'));
        $this->assertEquals('4. this is ok', $this->phplater->render('4. this is (( {{ list.0.value.1 }} ?? :: ok ))'));
        $this->assertEquals('5. this is <b>ok</b>', $this->phplater->render('5. this is (( {{ list.0.value.0 }} ?? <b>{{ list.1.value.0 }}</b> :: not ok ))'));
    }

    /**
     * @covers  PHPlaterConditional::doOpreration
     * @covers  PHPlaterConditional::findConditional
     * @covers  PHPlaterConditional::tagsConditionals
     * @covers  PHPlaterConditional::patternForConditional
     * @covers  PHPlaterConditional::pattern
     */
    public function testConditionalTags() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => ['this']],
                ['value' => ['ok']]
            ]
        ]);
        $this->phplater->setTagsConditionals('<?', '?>');
        $this->assertEquals('this is ok', $this->phplater->render('this is <? {{ list.0.value.0 }} ?? {{ list.1.value.0 }} :: not ok ?>'));
        $this->phplater->setTag(PHPlater::TAG_IF, 'TRUE:');
        $this->assertEquals('this is ok', $this->phplater->render('this is <? {{ list.0.value.0 }} TRUE: {{ list.1.value.0 }} :: not ok ?>'));
        $this->phplater->setTag(PHPlater::TAG_ELSE, 'FALSE:');
        $this->assertEquals('this is ok', $this->phplater->render('this is <? {{ list.0.value.0 }} TRUE: {{ list.1.value.0 }} FALSE: not ok ?>'));
    }

    /**
     * @covers  PHPlaterConditional::doOpreration
     * @covers  PHPlaterConditional::findConditional
     * @covers  PHPlaterConditional::evaluateOperation
     * @covers  PHPlaterConditional::patternForConditional
     * @covers  PHPlaterConditional::pattern
     */
    public function testConditionalWhitespaces() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => ['this']],
                ['value' => ['ok']]
            ]
        ]);

        $this->assertEquals('1. this is ok', $this->phplater->render('1. this is (({{list.0.value.0}} ?? {{ list.1.value.0   }} :: not ok))'));
        $this->assertEquals('2. this is ok', $this->phplater->render('2. this is (( {{ list.0.value.2 }} ?? not ok :: {{  list.1.value.0 }}))'));
        $this->assertEquals('3. this is ok', $this->phplater->render('3. this is (( {{list.0.value.0 }} ?? ok   ))'));
        $this->assertEquals('4. this is ok', $this->phplater->render('4. this is ((    {{ list.0.value.1}} ?? ::   ok ))'));
        $this->assertEquals('5. this is <b> ok </b>', $this->phplater->render('5. this is (( {{ list.0.value.0 }}  ??  <b> {{ list.1.value.0 }} </b> :: not ok ))'));
        $this->assertEquals('6. this is ok', $this->phplater->render('6. this is (({{list.0.value.0}}??{{list.1.value.0}}::not ok))'));
        $this->assertEquals('7. this is ok', $this->phplater->render('7. this is (({{list.0.value.0}} ??{{ list.1.value.0   }}::not ok))'));
    }

    /**
     * @covers  PHPlaterConditional::doOpreration
     * @covers  PHPlaterConditional::findConditional
     * @covers  PHPlaterConditional::evaluateOperation
     * @covers  PHPlaterConditional::patternForConditional
     * @covers  PHPlaterConditional::pattern
     */
    public function testConditionalOperators() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => [2]],
                ['value' => ['ok']]
            ]
        ]);

        $this->assertEquals('1. this is ok', $this->phplater->render('1. this is (( {{ list.0.value.0 }} == 2 ?? ok :: not ok ))'));
        $this->assertEquals('2. this is ok', $this->phplater->render('2. this is (( {{ list.0.value.0 }} === 2 ?? ok :: not ok ))'));
        $this->assertEquals('3. this is ok', $this->phplater->render('3. this is (( {{ list.0.value.0 }} != 1 ?? ok :: not ok ))'));
        $this->assertEquals('4. this is ok', $this->phplater->render('4. this is (( {{ list.0.value.0 }} !== 1 ?? ok :: not ok ))'));
        $this->assertEquals('5. this is ok', $this->phplater->render('5. this is (( {{ list.0.value.0 }} >= 1 ?? ok :: not ok ))'));
        $this->assertEquals('6. this is ok', $this->phplater->render('6. this is (( {{ list.0.value.0 }} <= 3 ?? ok :: not ok ))'));
        $this->assertEquals('7. this is ok', $this->phplater->render('7. this is (( {{ list.0.value.0 }} > 1 ?? ok :: not ok ))'));
        $this->assertEquals('8. this is ok', $this->phplater->render('8. this is (( {{ list.0.value.0 }} < 3 ?? ok :: not ok ))'));
        $this->assertEquals('9. this is ok', $this->phplater->render('9. this is (( {{ list.0.value.0 }} <> 1 ?? ok :: not ok ))'));
        $this->assertEquals('10. this is ok', $this->phplater->render('10. this is (( {{ list.0.value.0 }} <=> 1 ?? ok :: not ok ))'));
        $this->assertEquals('11. this is ok', $this->phplater->render('11. this is (( {{ list.0.value.0 }} % 3 ?? ok :: not ok ))'));
        $this->assertEquals('12. this is ok', $this->phplater->render('12. this is (( {{ list.0.value.0 }} && {{ list.0.value.1 }} ?? not ok :: ok ))'));
        $this->assertEquals('13. this is ok', $this->phplater->render('13. this is (( {{ list.0.value.0 }} and {{ list.0.value.1 }} ?? not ok :: ok ))'));
        $this->assertEquals('14. this is ok', $this->phplater->render('14. this is (( {{ list.0.value.0 }} || {{ list.0.value.2 }} ?? ok :: not ok ))'));
        $this->assertEquals('15. this is ok', $this->phplater->render('15. this is (( {{ list.0.value.0 }} or {{ list.0.value.2 }} ?? ok :: not ok ))'));
        $this->assertEquals('16. this is ok', $this->phplater->render('16. this is (( {{ list.0.value.2 }} xor {{ list.0.value.0 }} ?? ok :: not ok ))'));
    }

    /**
     * @covers  PHPlaterConditional::doOpreration
     * @covers  PHPlaterConditional::findConditional
     * @covers  PHPlaterConditional::evaluateOperation
     * @covers  PHPlaterConditional::patternForConditional
     * @covers  PHPlaterConditional::pattern
     */
    public function testConditionalOperatorWhitespace() {
        $this->phplater->setPlates([
            'list' => [
                ['value' => [2]],
                ['value' => ['ok']]
            ]
        ]);

        $this->assertEquals('1. this is ok', $this->phplater->render('1. this is (( {{ list.0.value.0 }}==2 ?? ok :: not ok ))'));
        $this->assertEquals('2. this is ok', $this->phplater->render('2. this is (( {{ list.0.value.0 }}===2 ?? ok :: not ok ))'));
        $this->assertEquals('3. this is ok', $this->phplater->render('3. this is (( {{ list.0.value.0 }}!=1 ?? ok :: not ok ))'));
        $this->assertEquals('4. this is ok', $this->phplater->render('4. this is (( {{ list.0.value.0 }}!==1 ?? ok :: not ok ))'));
        $this->assertEquals('5. this is ok', $this->phplater->render('5. this is (( {{ list.0.value.0 }}>=1 ?? ok :: not ok ))'));
        $this->assertEquals('6. this is ok', $this->phplater->render('6. this is (( {{ list.0.value.0 }}<=3 ?? ok :: not ok ))'));
        $this->assertEquals('7. this is ok', $this->phplater->render('7. this is (( {{ list.0.value.0 }}>1 ?? ok :: not ok ))'));
        $this->assertEquals('8. this is ok', $this->phplater->render('8. this is (( {{ list.0.value.0 }}<3 ?? ok :: not ok ))'));
        $this->assertEquals('9. this is ok', $this->phplater->render('9. this is (( {{ list.0.value.0 }}<>1 ?? ok :: not ok ))'));
        $this->assertEquals('10. this is ok', $this->phplater->render('10. this is (( {{ list.0.value.0 }}<=>1 ?? ok :: not ok ))'));
        $this->assertEquals('11. this is ok', $this->phplater->render('11. this is (( {{ list.0.value.0 }}%3 ?? ok :: not ok ))'));
        $this->assertEquals('12. this is ok', $this->phplater->render('12. this is (( {{ list.0.value.0 }}&&{{ list.0.value.1 }} ?? not ok :: ok ))'));
        $this->assertEquals('13. this is ok', $this->phplater->render('13. this is (( {{ list.0.value.0 }}and{{ list.0.value.1 }} ?? not ok :: ok ))'));
        $this->assertEquals('14. this is ok', $this->phplater->render('14. this is (( {{ list.0.value.0 }}||{{ list.0.value.2 }} ?? ok :: not ok ))'));
        $this->assertEquals('15. this is ok', $this->phplater->render('15. this is (( {{ list.0.value.0 }}or{{ list.0.value.2 }} ?? ok :: not ok ))'));
        $this->assertEquals('16. this is ok', $this->phplater->render('16. this is (( {{ list.0.value.2 }}xor{{ list.0.value.0 }} ?? ok :: not ok ))'));
    }

    /**
     * @covers  PHPlater::render
     * @covers  PHPlater::find
     * @covers  PHPlater::setPlate
     * @covers  PHPlater::content
     */
    public function testSameTemplateMultipleTimesWithChangingPlates() {
        $list = [
            ['this', 'is', 'ok'],
            ['this', 'is', 'ok too']
        ];
        $this->phplater->setContent('{{item.0}} {{item.1}} {{item.2}}');
        $result = [];
        foreach ($list as $item) {
            $result[] = $this->phplater->setPlate('item', $item)->render();
        }
        $this->assertEquals('this is ok and this is ok too', implode(' and ', $result));
    }

    /**
     * @covers  Error\RuleBrokenError
     */
    public function testOperationException() {
        $this->expectException(RuleBrokenError::class);
        $this->phplater->render('(( value % value ?? true :: false ))');
    }

    /**
     * @covers  render
     */
    public function testNoExtension() {
        file_put_contents($this->tpl_file, 'value ok');
        $this->assertEquals('value ok', $this->phplater->render(str_replace('.tpl', '', $this->tpl_file)));
    }

    /**
     * @covers  render
     */
    public function testRootNoExtension() {
        file_put_contents($this->tpl_file, 'value ok');
        $this->phplater->setRoot('tests');
        $this->assertEquals('value ok', $this->phplater->render('/template'));
    }

    /**
     * @covers  render
     */
    public function testQuickAccess() {
        file_put_contents($this->tpl_file, 'value {{ ok }}');
        $this->assertEquals('value ok', phplater($this->tpl_file, ['ok' => 'ok'])->render());
    }

}
