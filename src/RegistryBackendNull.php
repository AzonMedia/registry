<?php
declare(strict_types=1);


namespace Azonmedia\Registry;


use Azonmedia\Registry\Interfaces\RegistryBackendInterface;

/**
 * Class RegistryBackendNull
 * All methods return FALSE or [] as if there is no configuration at all
 * @package Azonmedia\Registry
 */
class RegistryBackendNull implements RegistryBackendInterface
{

    /**
     * Returns a single config value.
     * Does not throw an exception if the config value does not exist but returns $default_value
     * @param string $class_name
     * @param string $key
     * @param null $default_value
     * @return mixed|null
     */
    public function get_config_value(string $class_name, string $key, $default_value = NULL)
    {
        return null;
    }

    /**
     * Checks does the provided $class_name has entries in the registry
     * @param string $class_name
     * @return bool
     */
    public function class_is_in_registry(string $class_name): bool
    {
       return false;
    }

    /**
     * Returns all configuration entries for the provided $class_name from the registry.
     * @param string $class_name
     * @return array Associative array
     */
    public function get_class_config_values(string $class_name): array
    {
        return [];
    }
}