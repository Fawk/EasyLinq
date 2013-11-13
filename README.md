EasyLinq
========

Used as following:
-----------------

Include EasyLinq.php to whatever page you want the accessibilty on.

And start writing out the magic.

Examples:
---------

```php


require_once("EasyLinq.php");

class Person 
{
    public $firstname;
    public $lastname;
    public $salary;
    
    public function __construct($fn, $ln, $s) 
    {
        $this->firstname = $fn;
        $this->lastname = $ln;
        $this->salary = $s;
    }
}

$list = array(new Person('John', 'Doe', 10000), 
              new Person('Sarah', 'Smith', 12000), 
              new Person('Clarence', 'Anderson', 16000));
        
$result = in($list)->where('$salary > 10000')->select('$firstname');

```

Which will yield this:

```php

    Array ( 
              [0] => Sarah 
              [1] => Clarence 
          )
```

Various other functions you can use:
------------------------------------

```php

    /* Selecting on multiple statments */
    $result = in($list)->where('$salary > 12000')->_or('$salary < 14000')->_and('$lastname contains Smi')->select();
    
    /* Result */
    Array 
            ( 
                [1] => Person Object 
                    ( 
                        [firstname] => Sarah 
                        [lastname] => Smith 
                        [salary] => 12000 
                    ) 
                [2] => Person Object 
                    ( 
                        [firstname] => Clarence 
                        [lastname] => Anderson 
                        [salary] => 16000 
                    ) 
            )

```

Various string expressions

* contains
* startswith
* endswith
* < , > , <= , >= , == (Using these on string values will compare against the length of the string)
