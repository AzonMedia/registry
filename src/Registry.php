<?php

namespace Azonmedia\Registry;


use Azonmedia\Registry\Interfaces\RegistryBackendInterface;
use Azonmedia\Registry\Interfaces\RegistryInterface;

/**
 * Class Registry
 * Supports multiple backends to be registered.
 * Requires at least one (provided to the constructor) to operate.
 * The variables are looked up in the registy backends by order the backends were added.
 * The one provided to the constructor is the primary one.
 * @package Azonmedia\Registry
 */
class Registry
implements RegistryInterface
{

    /**
     * Array of RegistryBackendInterface
     * @var array
     */
    protected $registry_backends = [];

    /**
     * Registry constructor.
     * Registry needs at least one backend to operate so it needs to be provided in the constructor.
     * This is the primary backend.
     * Fallback backends can be registered with @see self::add_backend()
     * Once a backend is added it can not be removed.
     * @param RegistryBackendInterface $registryBackend
     */
    public function __construct(RegistryBackendInterface $RegistryBackend)
    {
        $this->add_backend($RegistryBackend);
    }

    /**
     * Returns FALSE if this backend is already added
     * @return bool
     */
    public function add_backend(RegistryBackendInterface $RegistryBackend) : bool
    {
        $ret = FALSE;
        foreach ($this->registry_backends as $RegisteredRegistryBackend) {
            if (get_class($RegisteredRegistryBackend) === get_class($RegistryBackend)) {
                return $ret;
            }
        }
        $this->registry_backends[] = $RegistryBackend;
        return $ret;
    }

    /**
     * Because it has $default_value argument which will be returned if no value is found this method can be used for checking is there such value
     * @param string $class_name
     * @param string $key
     * @param mixed $default_value This value will be returned if the registry doesnt find the needed config value.
     */
    public function get_config_value(string $class_name, string $key, /* mixed */ $default_value = NULL) /* mixed */
    {
        $ret = $default_value;
        foreach ($this->registry_backends as $RegisteredRegistryBackend) {
            $backend_ret = $RegisteredRegistryBackend->get_config_value($class_name, $key, $default_value);
            if ($backend_ret !== $default_value) {
                //the backend returned a value
                $ret = $backend_ret;
                break;//do not check the registries with lower priority
            }
        }
        return $ret;
    }

    public function class_is_in_registry(string $class_name) : bool
    {
        $ret = FALSE;
        foreach ($this->registry_backends as $RegisteredRegistryBackend) {
            $ret = $RegisteredRegistryBackend->class_is_in_registry($class_name);
            if ($ret) {
                break;
            }
        }
        return $ret;
    }

    public function get_class_config_values(string $class_name) : array
    {
        $ret = [];
        foreach ($this->registry_backends as $RegisteredRegistryBackend) {
            $in_reg = $RegisteredRegistryBackend->class_is_in_registry($class_name);
            if ($in_reg) {
                $ret = $RegisteredRegistryBackend->get_class_config_values($class_name);
                break;
            }
        }
        return $ret;
    }

    
}