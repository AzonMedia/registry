<?php

namespace Azonmedia\Registry;

use Azonmedia\Registry\Interfaces\RegistryBackendInterface;
use Azonmedia\Registry\Interfaces\RegistryInterface;
use Symfony\Component\VarExporter\VarExporter;

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
     * @var string
     */
    protected string $generated_runtime_config_file = '';

    /**
     * @var string
     */
    protected string $generated_runtime_config_dir = '';

    /**
     * Registry constructor.
     * Registry needs at least one backend to operate so it needs to be provided in the constructor.
     * This is the primary backend.
     * Fallback backends can be registered with @see self::add_backend()
     * Once a backend is added it can not be removed.
     * @param RegistryBackendInterface $registryBackend
     */
    public function __construct(RegistryBackendInterface $RegistryBackend, string $generated_runtime_config_file = '', string $generated_runtime_config_dir = '')
    {
        $this->add_backend($RegistryBackend);
        $this->generated_runtime_config_file = $generated_runtime_config_file;
        $this->generated_runtime_config_dir = $generated_runtime_config_dir;

        $this->remove_dir($generated_runtime_config_dir);

        if (file_exists($generated_runtime_config_file)) {
            unlink($generated_runtime_config_file);
        }
    }

    private function remove_dir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object) && !is_link($dir."/".$object)) {
                        $this->remove_dir($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }

            rmdir($dir);
        }
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
     * Returns an array of RegistryBackendInterface of the currently registered ones.
     * @return array
     */
    public function get_backends() : array
    {
        return $this->registry_backends;
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
        $file_content = '';

        //the order of the registries is the reverse - the newly added ones override the previously added ones
        //if the first added registry needs to have highest priority (reverse the order) then the code should be
        //foreach (array_reverse($this->registry_backends) as $RegisteredRegistryBackend) {
        foreach ($this->registry_backends as $RegisteredRegistryBackend) {
            $in_reg = $RegisteredRegistryBackend->class_is_in_registry($class_name);
            if ($in_reg) {
                $config_values = $RegisteredRegistryBackend->get_class_config_values($class_name);

                $merged_values = array_replace_recursive($ret, $config_values);

                if ($ret != $merged_values) {
                    $ret = $merged_values;

                    $file_content .= 'Rewriter Registered Backend: ' . get_class($RegisteredRegistryBackend) . PHP_EOL;
                    $file_content .= 'Rewrited properties: ';
                    $file_content .= print_r($config_values, TRUE);

                    $this->add_to_runtime_files($class_name, $config_values, "changes from " . get_class($RegisteredRegistryBackend));
                }
            }
        }

        $this->add_to_runtime_config_file($class_name, $file_content);

        return $ret;
    }

    /**
     * dump all classes CONFIG_RUNTIME data and their changes in one file
     */
    public function add_to_runtime_config_file($class_name, $content) : void
    {
        if ($this->generated_runtime_config_file != '') {

            if (\Swoole\Coroutine::getCid() > 0) {
                \Swoole\Coroutine\System::writeFile($this->generated_runtime_config_file, $content, 1);
            } else {
                file_put_contents($this->generated_runtime_config_file, $content, FILE_APPEND);
            }
        }
    }

    /**
     * dump all classes FINAL CONFIG_RUNTIME data in a directory, structured according to their namespaces
     */
    public function add_to_runtime_files($class_name, $content, $comment = '') : void
    {

        if ($this->generated_runtime_config_dir != '') {

            $exploded_class_name = explode("\\", $class_name);

            $file_name = $this->generated_runtime_config_dir . '/' . implode("/", $exploded_class_name) . '.php';
            unset($exploded_class_name[count($exploded_class_name) - 1]);

            $dir = $this->generated_runtime_config_dir . '/' . implode("/", $exploded_class_name);

            $config_runtime_str = VarExporter::export($content);
            $file_content = '';

            if (!file_exists($file_name)) {

               $file_content = <<<FILE
<?php
declare(strict_types=1);
FILE;
            }

            $file_content .= <<<FILE


// $comment
\$CONFIG_RUNTIME = $config_runtime_str;
FILE;

            if (strpos($comment, 'FINAL') !== false) {

            $file_content .= <<<FILE


return \$CONFIG_RUNTIME;
FILE;
            }

            if (!is_dir($dir)) {
                mkdir($dir, 0755, TRUE);
            }

            if (\Swoole\Coroutine::getCid() > 0) {
                \Swoole\Coroutine\System::writeFile($file_name, $file_content, 1);
            } else {
                file_put_contents($file_name, $file_content, FILE_APPEND);
            }
        }
    }
}
