<?php
declare(strict_types=1);

namespace Azonmedia\Registry\Interfaces;

interface RegistryInterface
{
    public function get_config_value(string $class_name, string $key, /* mixed */ $default_value = NULL) /* mixed */ ;

    public function class_is_in_registry(string $class_name) : bool ;

    public function get_class_config_values(string $class_name) : array ;

    public function get_backends() : array ;

    public function add_backend(RegistryBackendInterface $RegistryBackend) : bool ;

}