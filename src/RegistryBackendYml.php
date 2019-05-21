<?php


namespace Azonmedia\Registry;


use Azonmedia\Registry\Interfaces\RegistryBackendInterface;

/**
 * Class RegistryBackendYml
 * Supports registry stored in Yml.
 * @example
 * some_key:
 *   value: XX
 *   type: int
 * Or just
 * some_key: XX
 * With implied value of string
 * @package Azonmedia\Registry
 */
class RegistryBackendYml
implements RegistryBackendInterface
{

}