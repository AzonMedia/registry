<?php

namespace Azonmedia\Registry;

use Azonmedia\Registry\Interfaces\RegistryBackendInterface;

/**
 * Class RegistryBackendEnv
 *
 * All env variables are expected to be UPPERCASE as well all key lookups.
 * Internally everything is converted to uppercase.
 *
 * The env variables are loaded in __construct()
 * This means that no lookup for env vars will be done after that - if a change in the envvars is done during runtime this will not be reflected
 *
 * @package Azonmedia\Registry
 */
class RegistryBackendEnv
implements RegistryBackendInterface
{

    /**
     * @var string
     */
    protected $env_var_prefix = '';

    /**
     * Contains all environment variables.
     * This is populated by the constructor so that it is avoided to obtain each individual variable with getenv($var) when it is requested.
     * @var array
     */
    protected $env_vars = [];

    /**
     * RegistryBackendEnv constructor.
     * It is required to provide $env_var_prefix even if it is an empty string (meaning no prefix)
     * @param string $env_var_prefix
     */
    public function __construct(string $env_var_prefix)
    {
        $this->env_var_prefix = $env_var_prefix;
        $this->env_vars = array_map('strtoupper', getenv() );
    }

    public function get_config_value(string $class_name, string $key, /* mixed */ $default_value = NULL) /* mixed */
    {
        $ret = $default_value;
        $env_var_name = $this->env_var_prefix.self::convert_class_name($class_name).'_'.self::convert_key($key);
        if (array_key_exists($env_var_name, $this->env_vars)) {
            $ret = $this->env_vars[$env_var_name];
        }
        return $ret;
    }

    public function class_is_in_registry(string $class_name) : bool
    {
        $ret = FALSE;
        //checks are there any environment variables that contain this class name
        $env_var_pattern = self::convert_class_name($class_name);
        foreach ($this->env_vars as $env_var_name=>$env_var_value) {
            if (stripos($env_var_name, $env_var_pattern) !== FALSE) {
                $ret = TRUE;
                break;
            }
        }
        return $ret;
    }

    public function get_class_config_values(string $class_name) : array
    {
        $ret = [];
        $env_var_pattern = self::convert_class_name($class_name);
        foreach ($this->env_vars as $env_var_name=>$env_var_value) {
            if (stripos($env_var, $env_var_pattern) !== FALSE) {
                $ret[] = $env_var_value;
            }
        }
        return $ret;
    }

    protected static function convert_class_name(string $class_name) : string
    {
        $ret = strtoupper(str_replace('\\','_',$class_name));
        return $ret;
    }

    protected static function convert_key(string $key) : string
    {
        $ret = strtoupper($key);
        return $ret;
    }

}