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

    /**
     * Creates PHPLater object and initializes it
     *
     * @access public
     * @param  string $template Optional to put in template as argument to constructor
     */
    public function __construct(?string $template = null, string $root = '') {
        if (version_compare(phpversion(), '8.0.0', '<')) {
            throw new RuleBrokenError('PHPlater requires PHP version to be > 8.0');
        }
        $this->core($this);
        $this->extension('.tpl');
        $this->root($root);
        $this->plates([]);
        self::tagsConditionals('((', '))');
        self::tagsVariables('{{', '}}');
        self::tagsList('[[', ']]');
        self::tags([
            self::TAG_ARGUMENT => ':',
            self::TAG_ARGUMENT_LIST => ',',
            self::TAG_CHAIN => '.',
            self::TAG_FILTER => '|',
            self::TAG_IF => '??',
            self::TAG_ELSE => '::',
            self::TAG_LIST_KEY => '#',
            self::TAG_DELIMITER => '~'
        ]);
        $this->content($template);
    }

    /**
     * Get and set the root folder of templates
     *
     * @access public
     * @param  string $location Location to root folder of templates
     * @return string Returns location to templates
     */
    public function root(?string $location = null): string|PHPLater {
        if ($location && substr($location, -1) == '/') {
            throw new RuleBrokenError('Root must not end with slash. The template file should begin with it.');
        }
        return $this->getSet('location', $location);
    }

    /**
     * Get and set the template extension, if set, the extension is not needed to be used
     * Default: .tpl
     *
     * @access public
     * @param  string $extension of the template file
     * @return string Returns extension of the template file
     */
    public function extension(?string $extension = null): string|PHPLater {
        return $this->getSet('extension', $extension);
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
        if($data === null || trim($data) === ''){
            return null;
        }

        $have_slash = str_contains($data, '/');
        $have_tag = str_contains($data, self::tag(self::TAG_BEFORE));
        $have_conditional = str_contains($data, self::tag(self::TAG_CONDITIONAL_BEFORE));
        $have_list = str_contains($data, self::tag(self::TAG_LIST_BEFORE));
        $have_space = str_contains($data, ' ');
        if (!$have_slash && ($have_space || $have_list || $have_conditional || $have_tag)) {
            return $data;
        }

        $is_tpl_file = substr($data, -strlen($this->extension())) === $this->extension() && str_contains($data, $this->extension());

        $location = $this->root() . $data;
        $file_contents = null;
        if ($is_tpl_file) {
            $file_contents = is_file($location) ? file_get_contents($location) : '';
        }

        if (!$is_tpl_file && !$file_contents && $have_slash && !$have_space) {
            $location = $location . $this->extension();
            $file_contents = is_file($location) ? file_get_contents($location) : '';
        } else if (!$is_tpl_file) {
            return $data;
        }

        return $file_contents !== null ? $file_contents : $data;
    }

    /**
     * If the template is to be iterated over a collection of plates, then this method has to be called with true
     *
     * @access public
     * @param  $many true or false(default) according to whether or not there are many plates to iterate over
     * @return mixed Either bool value, or the current object
     */
    public function many(?bool $many = null): bool|object {
        return $this->getSet('many', $many);
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
        return $this->getSet('plates', self::ifJsonToArray($plates));
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
            $tag_before = stripslashes(self::tag(self::TAG_BEFORE));
            $tag_after = stripslashes(self::tag(self::TAG_AFTER));
            return $this->data['plates'][$name] ?? $tag_before . $name . $tag_after;
        }
        $this->data['plates'][$name] = self::ifJsonToArray($plate);
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
        $this->result($this->content());
        if (str_contains($this->result(), stripslashes(self::tag(self::TAG_LIST_BEFORE)))) {
            $this->result(self::renderCallback($this->get(self::CLASS_LIST), $this->result()));
        }
        if (str_contains($this->result(), stripslashes(self::tag(self::TAG_CONDITIONAL_BEFORE)))) {
            $this->result(self::renderCallback($this->get(self::CLASS_CONDITIONAL), $this->result()));
        }
        $tag_before = stripslashes(self::tag(self::TAG_BEFORE));
        $tag_after = stripslashes(self::tag(self::TAG_AFTER));
        $content = $this->many() ? $tag_before . '0' . $tag_after : $this->result();
        if(str_contains($content, $tag_before)) {
            $this->result(self::renderCallback($this->get(self::CLASS_VARIABLE), $content));
        }
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
    private static function renderCallback(object $class, string $content): string {
        return preg_replace_callback($class::pattern(), [$class, 'find'], $content);
    }
}
