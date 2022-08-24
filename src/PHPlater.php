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
     * Creates PHPlater object and initializes it
     *
     * @access public
     * @param  string $template Optional to put in template as argument to constructor
     */
    public function __construct(string $template = '', string $root = '') {
        if (version_compare(phpversion(), '8.0.0', '<')) {
            throw new RuleBrokenError('PHPlater requires PHP version to be > 8.0');
        }
        $this->setCore($this);
        $this->setExtension('.tpl');
        $this->setRoot($root);
        self::setTagsConditionals('((', '))');
        self::setTagsVariables('{{', '}}');
        self::setTagsList('[[', ']]');
        self::setTags([
            self::TAG_ARGUMENT => ':',
            self::TAG_ARGUMENT_LIST => ',',
            self::TAG_CHAIN => '.',
            self::TAG_FILTER => '|',
            self::TAG_IF => '??',
            self::TAG_ELSE => '::',
            self::TAG_LIST_KEY => '#',
            self::TAG_DELIMITER => '~'
        ]);
        $this->setContent($template);
    }

    /**
     * Get root folder of templates
     *
     * @access public
     * @return string Returns root location to templates
     */
    public function getRoot(): string {
        return $this->root;
    }

    /**
     * Set the root folder of templates
     *
     * @access public
     * @param  string $location Location to root folder of templates
     * @return PHPlater Returns current object
     */
    public function setRoot(string $location = ''): PHPlater {
        if ($location && substr($location, -1) == '/') {
            throw new RuleBrokenError('Root must not end with slash. The template file should begin with it.');
        }
        $this->root = $location;
        return $this;
    }

    /**
     * Get the template extension
     * Default: .tpl
     *
     * @access public
     * @return string Returns extension of the template file
     */
    public function getExtension(): string {
        return $this->extension;
    }

    /**
     * Set the template extension, when set it does not need to be part of template file name
     * Default: .tpl
     *
     * @access public
     * @param  string $extension of the template file
     * @return PHPlater Returns current object
     */
    public function setExtension(string $extension = ''): PHPlater {
        $this->extension = $extension;
        return $this;
    }

    /**
     * Get the template as string
     *
     * @access public
     * @return string Current template as string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * Set the template to act upon
     *
     * @access public
     * @param  string $data Url to file, a text string to set template, or null to return template
     * @return PHPlater Current template as string, or the current object if data is set
     */
    public function setContent(string $data = ''): PHPlater {
        $this->content = $this->contentify($data);
        return $this;
    }

    /**
     * Will manage the content so that it is a string when stored into data
     *
     * @access public
     * @param  string $data Url to file or a text string
     * @return string Returns content as a string or null if no data
     */
    public function contentify(string $data): string {
        if (trim($data) === '') {
            return $this->getContent();
        }

        $have_slash = str_contains($data, '/');
        $have_tag = str_contains($data, self::getTag(self::TAG_BEFORE));
        $have_conditional = str_contains($data, self::getTag(self::TAG_CONDITIONAL_BEFORE));
        $have_list = str_contains($data, self::getTag(self::TAG_LIST_BEFORE));
        $have_space = str_contains($data, ' ');
        if (!$have_slash && ($have_space || $have_list || $have_conditional || $have_tag)) {
            return $data;
        }

        $ext = $this->getExtension();
        $is_tpl_file = substr($data, -strlen($ext)) === $ext && str_contains($data, $ext);

        $location = $this->getRoot() . $data;
        $file_contents = null;
        if ($is_tpl_file) {
            $file_contents = is_file($location) ? file_get_contents($location) : '';
        }

        if (!$file_contents && $have_slash && !$have_space) {
            $location = $location . $this->getExtension();
            $file_contents = is_file($location) ? file_get_contents($location) : '';
        }

        return $file_contents !== null ? $file_contents : $data;
    }

    /**
     * Set true if template is to be iterated over a collection of plates
     *
     * @access public
     * @param bool $many true or false(default) according to whether or not there are many plates to iterate over
     * @return PHPlater The current object
     */
    public function setMany(bool $many = false): PHPlater {
        $this->many = $many;
        return $this;
    }

    /**
     * Returns if the template is to be iterated over a collection of plates or not
     *
     * @access public
     * @return bool Value if it is many or not
     */
    public function getMany(): bool {
        return $this->many;
    }

    /**
     * Stores the result of the variable to value change in template for each run
     *
     * @access private
     * @param  string $data String to store as the result
     * @return PHPlater Returns current object
     */
    private function setResult(string $data): PHPlater {
        $this->result = $data;
        return $this;
    }

    /**
     * Stores the result of the variable to value change in template for each run
     *
     * @access public
     * @return string Returns result as a string or this if result is set
     */
    public function getResult(): string {
        return $this->result;
    }

    /**
     * Adds the callable filter function to class
     *
     * @access public
     * @param string $filter The name of the filter
     * @param callable $function The callable function
     * @return void
     */
    public function setFilter(string $filter, callable $function): void {
        $this->getPHPlaterObject(self::CLASS_FILTER)->setFilter($filter, $function);
    }

    /**
     * Set or get all plates at once
     *
     * The plates array is a key value store from which it is accessed from within the template
     *
     * @access public
     * @param  string|array<string|int, mixed> $plates Either array with plates or json as string
     * @return PHPlater The array of all the plates or the current object or current PHPlater if set
     */
    public function setPlates(string|array $plates = []): PHPlater {
        $this->plates = self::ifJsonToArray($plates);
        return $this;
    }

    /**
     * Get all plates at once
     *
     * @access public
     * @return array<string|int, mixed> The array of all the plates or the current object
     */
    public function getPlates(): array {
        return $this->plates;
    }

    /**
     * Get the a plate (template variable)
     *
     * A plate is the value stored at the name position and is accessed from within the template
     *
     * @access public
     * @param  string $name The key position for where the plate is stored
     * @return mixed Plate asked for if it is a get operation
     */
    public function getPlate(string $name): mixed {
        $tag_before = stripslashes(self::getTag(self::TAG_BEFORE));
        $tag_after = stripslashes(self::getTag(self::TAG_AFTER));
        return $this->plates[$name] ?? $tag_before . $name . $tag_after;
    }

    /**
     * Set a plate (template variable)
     *
     * A plate is the value stored at the name position and is accessed from within the template
     *
     * @access public
     * @param  string $name The key position for where the plate is stored
     * @param  mixed $plate Object, array or string to store in the key position
     * @return PHPlater The current object
     */
    public function setPlate(string $name, mixed $plate = null): PHPlater {
        $this->plates[$name] = self::ifJsonToArray($plate);
        return $this;
    }

    /**
     * Run to render the template
     *
     * Replaces the template variables in the template, distinguished by tags, with the values from the plates
     *
     * @access public
     * @param  string $template Optional. The template to act upon if it is not set earlier.
     * @param  int $iterations To allow for variables that return variables, you can choose the amount of iterations
     * @return string The finished result after all plates are applied to the template
     */
    public function render(string $template = '', int $iterations = 1): string {
        $this->setResult($this->setContent($template)->getContent());
        if (str_contains($this->getResult(), stripslashes(self::getTag(self::TAG_LIST_BEFORE)))) {
            $this->setResult(self::renderCallback($this->getPHPlaterObject(self::CLASS_LIST), $this->getResult()));
        }
        if (str_contains($this->getResult(), stripslashes(self::getTag(self::TAG_CONDITIONAL_BEFORE)))) {
            $this->setResult(self::renderCallback($this->getPHPlaterObject(self::CLASS_CONDITIONAL), $this->getResult()));
        }
        $tag_before = stripslashes(self::getTag(self::TAG_BEFORE));
        $tag_after = stripslashes(self::getTag(self::TAG_AFTER));
        $content = $this->getMany() ? $tag_before . '0' . $tag_after : $this->getResult();
        if(str_contains($content, $tag_before)) {
            $this->setResult(self::renderCallback($this->getPHPlaterObject(self::CLASS_VARIABLE), $content));
        }
        if ($iterations-- && strstr($this->getResult(), $tag_before) && strstr($this->getResult(), $tag_after)) {
            return $this->render($this->getResult(), $iterations);
        }
        return $this->getResult();
    }

    /**
     * Renders the content with a callback to a method
     *
     * @param object $class The class to call the find method on
     * @param string $content The content to render
     * @return string The resulting content
     */
    private static function renderCallback(object $class, string $content): string {
        return (string) preg_replace_callback($class::pattern(), [$class, 'find'], $content);
    }
}
