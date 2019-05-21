<?php


namespace Azonmedia\Registry;


use Azonmedia\Registry\Interfaces\RegistryBackendInterface;

/**
 * Class RegistryBackendNull
 * All methods return FALSE or [] as if there is no configuration at all
 * @package Azonmedia\Registry
 */
class RegistryBackendNull
implements RegistryBackendInterface
{

}