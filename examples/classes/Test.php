<?php

class Test {

    function number() {
        return 'Random: ' . rand(0, 10);
    }

    function text() {
        return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
    }

    function getTwo() {
        return 'Kaksi';
    }

    public function returnArray() {
        return ['seven' => 'Seitsemän'];
    }

}
