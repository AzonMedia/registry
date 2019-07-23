# RegistryBackendArray

- Configuration is separated in .php files. Each of which contains an array with config data.
- Local and global configuration files
  - local files are all that end with *local.php. They override the global configuration. They may contain sensitive and environment specific values. And should be put in a .gitignore. 
  - global files are all the other php files in the config directory. They contain all the efault configuration values for classes.
- The configuration folder is parsed recursively. So there is no need to put everything in the root of the config folder
 
 ## Uses
 
 - Basic configuration
    - Can be used from from a factory if the config is accessible
    ```    
    'session' => [
           'key1' => 'value1',
           'key2' => 'value2',
       ],
    ```
  
 - Class config values
    - Values that can be injected in classes protect properties through getters or some other method
    ```
     MysqlConnection::class => [
         'host' => '127.0.0.1',
         'port' => 3306,
         'user' => 'user',
         'password' => 'password',
         'database' => 'db',
     ],
    ```    
 - DI 
    - Values can be injected in class constructors
    ```
    SampleClass::class => [
        'class' => SampleClass::class,
        'args' => [
            'sampleString' => 'string',
            'sampleInt' => 17,
        ]
    ],
    ```
    
    - Values can be inject in class factories
    ```
    Connection::class => [
            'class' => ConnectionFactory::class,
            'args' => [
                'sampleString' => 'string',
                'sampleInt' => 17
            ],
        ],
    ```
