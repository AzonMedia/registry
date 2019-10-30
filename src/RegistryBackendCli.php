<?php

namespace Azonmedia\Registry;

use Azonmedia\Registry\Interfaces\RegistryBackendInterface;

/**
 * Class RegistryBackendCli
 *
 * Provides a registry backend array based on console input options
 *
 * @package Azonmedia\Registry
 */
class RegistryBackendCli
implements RegistryBackendInterface
{

    /**
     * Contains merged config
     *
     * @var array
     */
    protected $config = [];

    /**
     * RegistryBackendCli constructor.
     * Provides a registry backend based on console input options
     * @param array $cli_options_mapping
     */
    public function __construct(array $cli_options_mapping)
    {
        $this->config = array_merge($this->config, $cli_options_mapping);
    }

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
        $value = $default_value;
        if (isset($this->config[$class_name][$key])) {
            $value = $this->config[$class_name][$key];
        }

        return $value;
    }

    /**
     * Checks does the provided $class_name has entries in the registry
     * @param string $class_name
     * @return bool
     */
    public function class_is_in_registry(string $class_name): bool
    {
        return isset($this->config[$class_name]);
    }

    /**
     * Returns all configuration entries for the provided $class_name from the registry.
     * @param string $class_name
     * @return array Associative array
     */
    public function get_class_config_values(string $class_name): array
    {
        $values = [];
        if (isset($this->config[$class_name])) {
            $values = $this->config[$class_name];
        }

        return $values;
    }

}