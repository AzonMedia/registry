<?php

namespace Azonmedia\Registry;

use Azonmedia\Registry\Interfaces\RegistryBackendInterface;
use DirectoryIterator;
use Exception;

/**
 * Class RegistryBackendIni
 * Provides a registry backend based on php ini files by using http://docs.php.net/manual/en/function.parse-ini-file.php
 * @package Azonmedia\Registry
 */
class RegistryBackendIni extends RegistryBackendArray implements RegistryBackendInterface
{
    /**
     * Global config files can be included in git
     * Local config files (*.local.ini) contain environment specific data and should be in the gitignore
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
                if ($file->getExtension() != 'ini') {
                    continue;
                }

                if (substr($file->getFilename(), -10) === '.local.ini' || $file->getFilename() == 'local.ini') {
                    $current_config = parse_ini_file($file->getRealPath(), true, INI_SCANNER_TYPED);
                    $this->local_config = array_replace_recursive($this->local_config, $current_config);
                } else {
                    $current_config = parse_ini_file($file->getRealPath(), true, INI_SCANNER_TYPED);
                    $this->global_config = array_replace_recursive($this->global_config, $current_config);
                }
            } elseif ($file->isDir()) {
                $this->generate_config($file->getRealPath());
            }
        }
    }
}