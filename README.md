EasyLinq
========

Used as following:
-----------------

Include EasyLinq.php to whatever page you want the accessibilty on.

And start writing out the magic.

Examples:
---------

´´´php

    <?php 
    
    require_once("EasyLinq.php");
    
    class Person {
    
        public $firstname;
        public $lastname;
        public $salary;
        
        public function __construct($fn, $ln, $s) {
        
            $this->firstname = $fn;
            $this->lastname = $ln;
            $this->salary = $s;
    
        }
    }
    
    $list = new array(new Person('John', 'Doe', 10000), 
            new Person('Sarah', 'Smith', 12000), 
            new Person('Clarence', 'Anderson', 16000));
            
    $result = in($list)->where('$salary > 10000')->select('$firstname');

´´´

