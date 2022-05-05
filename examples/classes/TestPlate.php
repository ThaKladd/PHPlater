<?php
class TestPlate extends PHPlater {

    private $title = 'Test';

    public function __construct() {
        $this->Plate('number_list', $this->numberList());
        $this->Plate('select', '<select>' . $this->option('Test', 1) . $this->option('Test 2', 2) . '</select>');
    }

    public function option($name, $value) {
        return '<option value="' . $value . '">' . $name . '</option>';
    }

    public function header() {
        return '<h2>' . $this->title . '</h2>';
    }

    public function numberList() {
        return '
        <ul>
            <li>One
            <li>Two
            <li>Three
        </ul>';
    }

}
