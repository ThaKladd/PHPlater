<?php

/**
 * The PHPlater class, a simple template engine.
 *
 * This class can either be used as is or extended by another object.
 * The musts is to add a template, and to map the variables there to the plates in this object, and then run render to return the result.
 *
 * @author  John Larsen
 * @license MIT
 */
use Error\RuleBrokenError;

class PHPlater extends PHPlaterBase {
    const CLASS_VARIABLE = 'PHPlaterVariable';
    const CLASS_LIST = 'PHPlaterList';
    const CLASS_CONDITIONAL = 'PHPlaterConditional';
    const CLASS_FILTER = 'PHPlaterFilter';
    const CLASS_TAG = 'PHPlaterTag';
    const CLASS_KEY = 'PHPlaterKey';

    private $instances = [];

    /**
     * Creates PHPLater object and initializes it
     *
     * @access public
     * @param  string $template Optional to put in template as argument to constructor
     */
    public function __construct(?string $template = null) {
        $this->core($this);
        $phplater_tag = $this->get(self::CLASS_TAG);
        $phplater_tag->tagsConditionals('((', '))');
        $phplater_tag->tagsVariables('{{', '}}');
        $phplater_tag->tagsList('[[', ']]');
        $phplater_tag->tags([
            PHPlaterTag::TAG_ARGUMENT => ':',
            PHPlaterTag::TAG_ARGUMENT_LIST => ',',
            PHPlaterTag::TAG_CHAIN => '.',
            PHPlaterTag::TAG_FILTER => '|',
            PHPlaterTag::TAG_IF => '??',
            PHPlaterTag::TAG_ELSE => '::',
            PHPlaterTag::TAG_LIST_KEY => '#',
            PHPlaterTag::TAG_DELIMITER => '~'
        ]);
        $this->content($template);
    }

    /**
     * Get the instance of the object on demand
     *
     * @access public
     * @param  string $const get the current instance of the corresponding class
     */
    public function get(string $const){
        if(!isset($this->instances[$const])){
            $this->instances[$const] = new $const($this);
        }
        return $this->instances[$const];
    }

    /**
     * Set the template to act upon
     *
     * @access public
     * @param  mixed $data Url to file, a text string to set template, or null to return template
     * @return mixed Current template as string, or the current object if data is set
     */
    public function content(?string $data = null): string|PHPlater {
        return $this->getSet('content', $this->contentify($data));
    }

    /**
     * Will manage the content so that it is a string when stored into data
     *
     * @access public
     * @param  mixed $data Url to file or a text string, if null returns null
     * @return mixed Returns content as a string or null if no data
     */
    public function contentify(?string $data): string|null {
        if($data === null){
            return null;
        }
        $is_tpl = substr($data, -strlen($this->extension())) == $this->extension();
        if(strlen($data) > 200 && !$is_tpl){
            return $data;
        }
        $location = $this->root() . $data;
        if($is_tpl && is_file($location)){
            return file_get_contents($location);
        }
        if(!$is_tpl && is_file($location . $this->extension())){
            return file_get_contents($location . $this->extension());
        }
        return $data;
    }

    /**
     * Stores the result of the variable to value change in template for each run
     *
     * @access public
     * @param  mixed $data String if the aim is to store the result, null if it is to get the stored result
     * @return mixed Returns result as a string or this if result is set
     */
    public function result(?string $data = null): string|PHPlater {
        return $this->getSet('result', $data);
    }

    /**
     * Calls the many method on the correct class
     *
     * @access public
     * @param  $many true or false(default) according to whether or not there are many plates to iterate over
     * @return mixed Either bool value, or the current PHPlater object
     */
    public function many(?bool $many = null): bool|PHPlater {
        $this->get(self::CLASS_VARIABLE)->many($many);
        return $this;
    }

    /**
     * Adds and gets the filter function, as well as calls it
     * Note that if $value is a string of a callable function it will be considered a set of the function
     * Otherwise the filter function is called with $value as argument
     *
     * @access public
     * @param  mixed $filter The name of the filter, either when set or when called
     * @param  string $value The callable function, or the argument to call function with
     * @return mixed The result of the called function, the function itself, or the current object
     */
    public function filter(string $filter, callable|string|null|array $value = null): int|string|callable|object {
        return $this->get(self::CLASS_FILTER)->filter($filter, $value);
    }

    /**
     * Set or get all plates at once
     *
     * The plates array is a key value store from which it is accessed from within the template
     *
     * @access public
     * @param  mixed $plates Either array with plates, json as string, or null to get all plates
     * @return mixed The array of all the plates or the current object or current PHPlater if set
     */
    public function plates(null|string|array $plates = null): array|PHPlater {
        return $this->getSet('plates', $this->ifJsonToArray($plates));
    }

    /**
     * Set or get the a plate (template variable)
     *
     * A plate is the value stored at the name position and is accessed from within the template
     *
     * @access public
     * @param  string $name The key position for where the plate is stored
     * @param  mixed $plate Object, array or string to store in the key position, or null to get the data in the key position
     * @return mixed Plate asked for if it is a get operation, or the current object if data is set
     */
    public function plate(string $name, object|array|string|int|float|bool|null $plate = null): mixed {
        if ($plate === null) {
            $tag_before = stripslashes($this->tag(PHPlaterTag::TAG_BEFORE));
            $tag_after = stripslashes($this->tag(PHPlaterTag::TAG_AFTER));
            return $this->data['plates'][$name] ?? $tag_before . $name . $tag_after;
        }
        $this->data['plates'][$name] = $this->ifJsonToArray($plate);
        return $this;
    }

    /**
     * Run to render the template
     *
     * Replaces the template variables in the template, distinguished by tags, with the values from the plates
     *
     * @access public
     * @param  mixed $template Optional. The template to act upon if it is not set earlier.
     * @param  int $iterations To allow for variables that return variables, you can choose the amount of iterations
     * @return string The finished result after all plates are applied to the template
     */
    public function render(?string $template = null, int $iterations = 1): string {
        $this->content($template);
        $this->result($this->renderCallback($this->get(self::CLASS_LIST), $this->content()));
        $this->result($this->renderCallback($this->get(self::CLASS_CONDITIONAL), $this->result()));
        
        $phplater_variable = $this->get(self::CLASS_VARIABLE);
        $tag_before = stripslashes($this->tag(PHPlaterTag::TAG_BEFORE));
        $tag_after = stripslashes($this->tag(PHPlaterTag::TAG_AFTER));
        $content = $phplater_variable->many() ? $tag_before . '0' . $tag_after : $this->result();

        $this->result($this->renderCallback($phplater_variable, $content));
        if ($iterations-- && strstr($this->result(), $tag_before) && strstr($this->result(), $tag_after)) {
            return $this->render($this->result(), $iterations);
        }
        return $this->result();
    }

    /**
     * Renders the content with a callback to a method
     *
     * @param object $class The class to call the find method on
     * @param string $content The content to render
     * @return string The resulting content
     */
    private function renderCallback(object $class, string $content): string {
        //[$class, 'find']
        //function($matches) use ($class) { return $class->find($matches); }
        //echo $class->pattern().'-----'.PHP_EOL.PHP_EOL;
        return preg_replace_callback($class->pattern(), [$class, 'find'], $content);
    }
}
