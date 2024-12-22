<?php

namespace Paul\CompanyCars;

final class OptionsData
{
    // Singleton instance of the class
    public static ?OptionsData $instance = null;

    // User property lead code
    public ?string $identifierUserPropertyLeadCode;

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton instance of the class.
     * If it doesn't exist, create a new one.
     *
     * @return OptionsData
     */
    public static function getInstance(): OptionsData
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Magic setter to set a property value.
     * Allows setting a property only once.
     *
     * @param string $name Property name.
     * @param mixed $value Value to assign.
     * @throws \Exception If the property is already set or doesn't exist.
     */
    public function __set(string $name, $value): void
    {
        if (property_exists($this, $name)) {
            if ($this->$name === null) {
                $this->$name = $value;
            } else {
                throw new \Exception("$name is already set and cannot be modified.");
            }
        } else {
            throw new \Exception("Invalid property name: $name");
        }
    }

    /**
     * Magic getter to retrieve a property value.
     *
     * @param string $name Property name.
     * @return mixed The property value.
     * @throws \Exception If the property doesn't exist.
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new \Exception("Invalid property name: $name");
    }

    /**
     * Magic isset to check if a property is set and not empty.
     *
     * @param string $name Property name.
     * @return bool True if the property is set and not empty, false otherwise.
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name) && $this->$name !== null && $this->$name !== false && $this->$name !== '';
    }

}
