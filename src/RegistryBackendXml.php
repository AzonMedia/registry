<?php
declare(strict_types=1);


namespace Azonmedia\Registry;


use Azonmedia\Registry\Interfaces\RegistryBackendInterface;
use DirectoryIterator;
use Exception;

/**
 * Class RegistryBackendXml
 * Provides a configuration kept in XML following the rules in Guzaba1
 * The constructor expects as an argument path to the XML config file
 * @package Azonmedia\Registry
 */
class RegistryBackendXml extends RegistryBackendArray implements RegistryBackendInterface
{
    public function __construct(string $config_path)
    {
        parent::__construct($config_path);

        // cast config values to int and float if needed
        array_walk_recursive($conf, function (&$item, $key) {
            if (is_numeric($item)) {
                if (strpos($item, '.')) {
                    $item = (float)$item;
                } else {
                    $item = (int)$item;
                }
            }
        });
        unset($item);
    }

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
                if ($file->getExtension() != 'xml') {
                    continue;
                }

                if (substr($file->getFilename(), -10) === '.local.xml' || $file->getFilename() == 'local.xml') {
                    $current_config = $this->parseXmlFile($file->getRealPath());
                    $this->local_config = array_replace_recursive($this->local_config, $current_config);
                } else {
                    $current_config = $this->parseXmlFile($file->getRealPath());
                    $this->global_config = array_replace_recursive($this->global_config, $current_config);
                }
            } elseif ($file->isDir()) {
                $this->generate_config($file->getRealPath());
            }
        }
    }

    /**
     * @param $file_path
     * @return array
     */
    protected function parseXmlFile(string $file_path): array
    {
        $node = simplexml_load_file($file_path);
        $parsed_result = $this->process_config_array($node, $file_path);
        return $parsed_result;
    }

    protected function process_config_array(\simpleXMLElement $node, string $config_path): array
    {
        $arr = array();
        foreach ($node->children() as $key => $value) {
            $var_name = $this->process_array_key_name($key, $value);
            if (count($value->children()) || $value['type'] == 'array') {
                if (isset($arr[$var_name])) {
                    //do not overwrite but instead rename
                    $arr[$var_name . '_0'] = $arr[$var_name];
                    unset($arr[$var_name]);
                    $arr[$var_name . '_1'] = $this->process_config_array($value, $config_path);//recursion
                } elseif (isset($arr[$var_name . '_0'])) {
                    //it is an array and we have already found elements from it
                    //find what is the last element
                    $counter = 0;
                    do {
                        $counter++;
                        if (!isset($arr[$var_name . '_' . $counter])) {
                            break;//the current value of the counter is not taken so it must be added here
                        }
                    } while (true);
                    $arr[$var_name . '_' . $counter] = $this->process_config_array($value, $config_path);
                    unset($counter);

                } else {
                    //either not an array or this is the first value
                    $arr[$var_name] = $this->process_config_array($value, $config_path);
                }
            } else {
                $arr[$var_name] = $this->process_config_var($value, $config_path);
            }
        }
        return $arr;
    }

    /**
     * Processes the array key name based on the XML rules for comparison
     * @param string $key
     * @param \simpleXMLElement $node
     * @return string
     *
     * @author vesko@azonmedia.com
     * @created 06.08.2018
     * @since 0.7.2
     */
    protected function process_array_key_name(string $key, \simpleXMLElement $node): string
    {
        $var_name = $key;
        if (strpos($key, 'U_') === 0) {
            $var_name = '_' . substr($key, 2);
        }

        if (strpos($key, 'I_') === 0) {
            $var_name = substr($key, 2);//it is allowed array keys to start with a number or to be numbers
        }

        if ($key == 'A_key') {
            if (empty($node['name'])) {
                throw new \RuntimeException(sprintf('An element that is designated to be a key of an array with the A_key tag is missing the "name" attribute.'));
            }
            $var_name = (string)$node['name'];
        }
        return $var_name;
    }

    /**
     * Generates the value of a variable/property from a configuration file
     * @param \simpleXMLElement $node
     * @param string $config_path The path to the configuration file that is currently being processed. This argument currently is being passed for the sole purpose to provide more detailed error messages.
     * @return mixed
     */
    protected function process_config_var(\simpleXMLElement $node, string $config_path) /* mixed */
    {
        $node_value_as_string = (string)$node;

        if (count($node->children())) {
            //the is an array
            $ret = $this->process_config_array($node, $config_path);
        } elseif ($type = $node['type']) {

            switch ($type) {
                case 'constant': //the variable is defined using an existing constant
                case 'const':
                    if ($node_value_as_string) {
                        if (defined($node_value_as_string)) {
                            $ret = constant((string)$node);
                        } else {
                            //exceptions thrown during autoload can not be caught by a catch block
                            $message = 'The the value of the variable/key "%s" is set to be retrieved from the constant "%s" which does not exist.';
                            throw new \RuntimeException(sprintf($message, $node->getName(), $node_value_as_string));
                        }
                    } else {
                        $ret = null;
                    }
                    break;
                case 'integer':
                case 'int':
                    $ret = (integer)$node_value_as_string;
                    break;
                case 'double':
                case 'float':
                    $ret = (float)$node_value_as_string;
                    break;
                case 'boolean':
                case 'bool':
                    if (strtolower($node_value_as_string) === 'true' || $node_value_as_string === '1') {
                        $ret = true;
                    } elseif (strtolower($node_value_as_string) === 'false' || $node_value_as_string === '0') {
                        $ret = false;
                    } else {
                        //exceptions thrown during autoload can not be caught by a catch block
                        $message = 'The value of the variable/key "%s" is defined as boolean, but its value is not "true", "false", "1" or "0" but is "%s".';
                        throw new \RuntimeException(sprintf($message, $node->getName(), $node_value_as_string));
                    }
                    break;
                case 'string':
                    $ret = $node_value_as_string;
                    break;
                case 'null':
                case 'NULL':
                    $ret = null;
                    break;
                case 'array':
                    $ret = array();
                    break;
                case 'resourse':
                    throw new \RuntimeException(sprintf('The "%s" variable/key is defined as resource. This is not allowed.', $node->getName()));
                case 'object':
                    throw new \RuntimeException(sprintf('The "%s" variable/key is defined as object. This is not allowed.', $node->getName()));
                default:
                    $ret = null;
                    throw new \RuntimeException(sprintf('The "%s" variable/key is defined to be of an unknown type "%s".', $node->getName(), $type));
            }
        } else {
            if (strtolower($node_value_as_string) == 'true') {
                $ret = true;
            } elseif (strtolower($node_value_as_string) == 'false') {
                $ret = false;
            } else {
                $ret = $node_value_as_string;//by default is a string
            }
        }
        return $ret;
    }

}