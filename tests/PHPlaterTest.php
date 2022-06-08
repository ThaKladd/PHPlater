<?php
use PHPUnit\Framework\TestCase;

class PHPlaterTest extends TestCase {

    public $phplater;
    public $tpl_file;
    public $test_class;

    static function setUpBeforeClass(): void {
        include_once 'src/PHPlater.php';
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
     * @covers  PHPlater->render
     * @uses    PHPlater->plate
     * @uses    PHPlater->content
     * @uses    PHPlater->contentify
     * @uses    PHPlater->ifJsonToArray
     * @uses    PHPlater->getSet
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     */
    public function testSimpleRender() {
        $this->phplater->plate('string', 'ok');
        $this->phplater->plate('int', 10);
        $this->phplater->plate('json', '{"value":"ok"}');
        $this->phplater->plate('array', ['ok']);
        $this->phplater->plate('array_assoc', ['value' => 'ok']);
        $this->phplater->plate('object', (object) ['value' => 'ok']);

        $this->assertEquals('test ok', $this->phplater->render('test {{string}}'));
        $this->assertEquals('test 10', $this->phplater->render('test {{int}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{json.value}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{array.0}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{array_assoc.value}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{object.value}}'));
    }

    /**
     * @covers  PHPlater->render
     * @covers  PHPlater->plates
     * @uses    PHPlater->content
     * @uses    PHPlater->getSet
     * @uses    PHPlater->contentify
     * @uses    PHPlater->ifJsonToArray
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     */
    public function testAdvancedRender() {
        file_put_contents($this->tpl_file, '{{str}}, {{json.value}}, {{json.arr.0.value}}, {{obj.prop}}, {{obj.this.prop}}, {{obj.arr.arr.0}}, {{arr.arr.arr.value}}, {{arr.obj.prop}}');
        $plates = [
            'str' => 'ok',
            'json' => '{"value": "ok", "arr": [{"value": "ok"}]}',
            'obj' => $this->test_class,
            'arr' => ['arr' => ['arr' => ['value' => 'ok']], 'obj' => $this->test_class]
        ];
        $this->phplater->plates($plates);
        $this->phplater->content($this->tpl_file);
        $this->assertEquals($this->phplater->ifJsonToArray($plates), $this->phplater->plates());
        $this->assertEquals('ok, ok, ok, ok, ok, ok, ok, ok', $this->phplater->render());
    }

    /**
     * @covers  PHPlater->ifJsonToArray
     */
    public function testIfJsonToArray() {
        $this->assertEquals(['value' => 'ok'], $this->phplater->ifJsonToArray('{"value": "ok"}'));
        $this->assertEquals('value ok', $this->phplater->ifJsonToArray('value ok'));
        $this->assertEquals(1, $this->phplater->ifJsonToArray(1));
        $this->assertEquals(['value' => 'ok'], $this->phplater->ifJsonToArray(['value' => 'ok']));
        $this->assertEquals($this->test_class, $this->phplater->ifJsonToArray($this->test_class));
    }

    /**
     * @covers  PHPlater->contentify
     * @uses    PHPlater->getSet
     */
    public function testContentify() {
        file_put_contents($this->tpl_file, 'value ok');
        $this->assertEquals('value ok', $this->phplater->contentify($this->tpl_file));
        $this->assertEquals('value ok', $this->phplater->contentify('value ok'));
    }

    /**
     * @covers  PHPlater->content
     * @uses    PHPlater->contentify
     * @uses    PHPlater->getSet
     */
    public function testContent() {
        file_put_contents($this->tpl_file, 'value ok');
        $this->phplater->content($this->tpl_file);
        $this->assertEquals('value ok', $this->phplater->content());
    }

    /**
     * @covers  PHPlater->render
     * @uses    PHPlater->getSet
     * @uses    PHPlater->content
     * @uses    PHPlater->contentify
     */
    public function testEmptyRender() {
        file_put_contents($this->tpl_file, '');
        $this->assertEquals('', $this->phplater->render($this->tpl_file));
    }

    /**
     * @covers  PHPlater->plate
     * @uses    PHPlater->getSet
     */
    public function testPlate() {
        $this->phplater->plate('test', 'ok');
        $this->assertEquals('ok', $this->phplater->plate('test'));
    }

    /**
     * @covers  PHPlater->tags
     * @covers  PHPlater->tagBefore
     * @covers  PHPlater->tagAfter
     * @uses    PHPlater->getSet
     * @uses    PHPlater->render
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     */
    public function testTags() {
        $this->phplater->plate('string', 'ok');
        $this->phplater->tags('<!--', '-->');
        $this->assertEquals(preg_quote('<!--'), $this->phplater->tagBefore());
        $this->assertEquals(preg_quote('-->'), $this->phplater->tagAfter());
        $this->assertEquals('test ok', $this->phplater->render('test <!-- string -->'));
    }

    /**
     * @covers  PHPlater->pregDelimiter
     * @uses    PHPlater->render
     * @uses    PHPlater->getSet
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     */
    public function testDelimiter() {
        $this->phplater->plate('string', 'ok');
        $this->phplater->pregDelimiter('=');
        $this->assertEquals('test ok', $this->phplater->render('test {{string}}'));
    }

    /**
     * @covers  PHPlater->filter
     * @uses    PHPlater->filterSeperator
     * @uses    PHPlater->render
     * @uses    PHPlater->getSet
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     * @uses    PHPlater->getFiltersAndParts
     * @uses    PHPlater->callFilters
     */
    public function testFilter() {
        $this->phplater->filter('uppercase', 'mb_strtoupper');
        $this->phplater->filter('lowercase', 'mb_strtolower');
        $this->phplater->filter('add_ok', function (string $data) {
            return $data . ' ok';
        });
        $this->phplater->filter('implode', function (array $data) {
            return implode('', $data);
        });

        $this->phplater->plate('string_one', 'ok');
        $this->assertEquals('test OK', $this->phplater->render('test {{string_one|uppercase}}'));

        $this->phplater->plate('string_two', 'OK');
        $this->assertEquals('test ok', $this->phplater->render('test {{string_two|lowercase}}'));

        $this->phplater->plate('string_three', 'test');
        $this->assertEquals('TEST OK', $this->phplater->render('{{string_three|add_ok|uppercase}}'));

        $this->phplater->plate('o', ['o', 'k']);
        $this->phplater->plate('okey', [['o', 'k']]);
        $this->phplater->plate('oks', '{{okey.0|implode}}');

        $this->assertEquals('test OK', $this->phplater->render('test {{o|implode|uppercase}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{okey.0|implode}}'));
        $this->assertEquals('test ok', $this->phplater->render('test {{oks}}'));
    }

    /**
     * @covers  PHPlater->filter
     * @uses    PHPlater->filterSeperator
     * @uses    PHPlater->getFunctionAndArguments
     * @uses    PHPlater->render
     * @uses    PHPlater->getSet
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     * @uses    PHPlater->getFiltersAndParts
     * @uses    PHPlater->callFilters
     */
    public function testFilterArguments() {
        $this->phplater->plate('testing', 'test');
        $this->phplater->filter('add_args', function (string $test, string $is = 'no', string $ok = 'no') {
            return $test . ' ' . $is . ' ' . $ok;
        });
        $this->assertEquals('test is ok', $this->phplater->render('{{testing|add_args:is,ok}}'));
    }

    /**
     * @covers  PHPlater->testArgumentSeperator
     * @uses    PHPlater->filter
     * @uses    PHPlater->filterSeperator
     * @uses    PHPlater->getFunctionAndArguments
     * @uses    PHPlater->render
     * @uses    PHPlater->getSet
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     * @uses    PHPlater->getFiltersAndParts
     * @uses    PHPlater->callFilters
     */
    public function testArgumentSeperator() {
        $this->phplater->plate('testing', 'test');
        $this->phplater->argumentSeperator('>');
        $this->phplater->filter('add_args', function (string $test, string $is = 'no', string $ok = 'no') {
            return $test . ' ' . $is . ' ' . $ok;
        });
        $this->assertEquals('test is ok', $this->phplater->render('{{testing|add_args>is,ok}}'));
    }

    /**
     * @covers  PHPlater->filterSeperator
     * @uses    PHPlater->filter
     * @uses    PHPlater->render
     * @uses    PHPlater->getSet
     * @uses    PHPlater->find
     * @uses    PHPlater->extract
     * @uses    PHPlater->getFiltersAndParts
     * @uses    PHPlater->callFilters
     */
    public function testFilterSeperator() {
        $this->phplater->filter('uppercase', 'mb_strtoupper');
        $this->phplater->filter('add_ok', function (string $data) {
            return $data . ' is ok';
        });
        $this->phplater->plate('string', 'test');
        $this->phplater->filterSeperator('¤');
        $this->assertEquals('This TEST IS OK', $this->phplater->render('This {{string¤add_ok¤uppercase}}'));
    }

}
