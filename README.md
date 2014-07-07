phputils
========

PHP utility library now includes basic **http**, **cache**, **security**, **db** , **mustache** modules. It provides some basic php classes and functions. You can also use `hybridauth` library to implements social sign on, `ftpupload` library to upload files to ftp server, `oembed` library to allow an embeded representation of an URL.

## Modules

>* The **http** module provides `curl` and `socket` functions.
>* The **cache** module provides `memcache`, `file`, `redis` functions.
>* The **security** module provides `des` , `rsa` functions.
>* The **db** module provides basic `mysql` functions.
>* The **mustache** module provides basic functions about `mustache`.

## Tests

Now there are some test cases for these modules.

**DesTest**

```

    include_once("../autoloader.php");
    $des = new PU_Des('key');
    $text = 'encrypt';
    echo 'Input data: '.$text. '<br>';
    $data = $des->encrypt($text);
    echo 'After encrypt: '.$data . '<br>';
    $origin = $des->decrypt($data);
    echo 'After encrypt: '.$origin . '<br>'; 

```

**MemcacheTest**

```

    include_once("../autoloader.php");
    include_once("../config.php");
    //assert(class_exists("Memcached") == false );
    //assert(function_exists('memcache_connect') == false );
    
    $memc = new PU_Memcached();
    //  
    $memc->set('key1', 'This is first value', 60);
    $val = $memc->get('key1');
    echo "After set, key1 value: " . $val ."<br />";
    // 
    $memc->replace('key1', 'This is replace value', 60);
    $val = $memc->get('key1');
    echo "After replace, key1 value: " . $val . "<br />";
    //
    $memc->del('key1');
    $val = $memc->get('key1');
    echo "After delete, key1 value: " . $val . "<br />";
    //
    $memc->flush();
    $val1 = $memc->get('key1');
    echo "After flush, key1 value: ";
    print_r($val1);
    echo "<br />";
     
    $memc->close();

```

## WIKI





