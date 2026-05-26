<?php

class Language implements ArrayAccess
{
    private array $tlr = array();

    public function __construct()
    {
        $constants = require 'lang_constants.php';
        $this->tlr = is_array($constants) ? $constants : array();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset !== null && $this->offsetExists($offset)) {
            die('Changing already existing values is prohibited');
        }

        if ($offset === null) {
            $this->tlr[] = $value;
        } else {
            $this->tlr[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->tlr[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->tlr[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->tlr[$offset] ?? 'NO_LANG_' . strtoupper((string) $offset);
    }
}

$tracker_lang = new Language();

?>