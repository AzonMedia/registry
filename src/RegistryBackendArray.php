<?php


namespace Azonmedia\Registry;


use Azonmedia\Registry\Interfaces\RegistryBackendInterface;
use DirectoryIterator;
use Exception;

class RegistryBackendArray implements RegistryBackendInterface
{

    /**
     * Contains merged config
     *
     * @var array
     */
    protected $config = [];

    /**
     * Contains global configuration
     *
     * @var array
     */
    protected $global_config = [];

    /**
     * Contains local configuration
     *
     * @var array
     */
    protected $local_config = [];

    /**
     * RegistryBackendArray constructor.
     * @param string $config_path
     * @throws Exception
     */
    public function __construct(string $config_path)
    {
        $this->generate_config($config_path);
        $this->config = array_merge_recursive($this->global_config, $this->local_config);
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

    /**
     * Global config files can be included in git
     * Local config files (*.local.php) contain environment specific data and should be in the gitignore
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
            if ($file->isFile()) {
                if (substr($file->getFilename(), -10) === '.local.php' || $file->getFilename() == 'local.php') {
                    $current_config = include $file->getRealPath();
                    $this->local_config = array_merge_recursive($this->local_config, $current_config);
                } elseif ($file->getExtension() === 'php') {
                    $current_config = include $file->getRealPath();
                    $this->local_config = array_merge_recursive($this->global_config, $current_config);
                }
            } elseif ($file->isDir()) {
                $this->generate_config($file->getRealPath());
            }
        }
    }
}