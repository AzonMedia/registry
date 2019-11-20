<?php


namespace Azonmedia\Registry;


use Azonmedia\Registry\Interfaces\RegistryBackendInterface;
use DirectoryIterator;
use Exception;
use Symfony\Component\Yaml\Yaml;

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
class RegistryBackendYml extends RegistryBackendArray implements RegistryBackendInterface
{
    /**
     * Global config files can be included in git
     * Local config files (*.local.yaml) contain environment specific data and should be in the gitignore
     * Local files override global config files
     *
     * @param string $config_path
     * @throws Exception
     */
    public function generate_config(string $config_path)
    {
        if (!is_dir($config_path)) {
            throw new Exception(sprintf('Config path %s does not exist', $config_path));
        }

        foreach (new DirectoryIterator($config_path) as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->isFile()) {
                if (!in_array($file->getExtension(), ['yaml', 'yml'])) {
                    continue;
                }

                if (substr($file->getFilename(), -10) === '.local.yml' || substr($file->getFilename(), -11) === '.local.yaml' || in_array($file->getFilename(), ['local.yml', 'local.yaml'])) {
                    $current_config = Yaml::parseFile($file->getRealPath());
                    $this->local_config = array_replace_recursive($this->local_config, $current_config);
                } else {
                    $current_config = Yaml::parseFile($file->getRealPath());
                    $this->global_config = array_replace_recursive($this->global_config, $current_config);
                }
            } elseif ($file->isDir()) {
                $this->generate_config($file->getRealPath());
            }
        }
    }
}