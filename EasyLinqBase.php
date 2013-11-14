<?php

  class EasyLinqBase {
    
    private $l;
    private $reallist;
    private $evalcount;
    private $count;
    private $cl;
    private $order_name;
    private $order_dir;
    private $order_type;
    private $skip;
    private $take;
    private $anonexpr;
    private $first;
    private $last;
    
    public function __construct($list)
    {
        if(!is_array($list) || count($list) == 0)
        {
            throw new Exception(get_class($this) . " error: No valid list of objects applied!");
        }

        $this->l = $list;
        $this->cl = get_class($list[0]);

        foreach($list as $obj)
        {
            if(get_class($obj) != $this->cl)
            {
                throw new Exception(get_class($this) . " error: All objects in the array must be of the same type!");
            }
        }
        
        return $this;
    }

    public function select($name = "")
    {
        $return = array();
        $objs = array();
        $t = "";
        if(substr($name, 0, 3) == "new")
        {
            if(preg_match_all('/\{(.*?)\}/',$name,$match)) {
                $this->anonexpr = trim($match[1][0]);
            }
            else
            {
                throw new Exception(get_class($this) . " error: Invalid object expression in select!");
            }
            $name = "";
        }
        else
        {
            $t = substr($name, 0, 1);
            $name = substr($name, 1, strlen($name));
        }
        for($i = 1; $i < count($this->reallist) + 1; $i++)
        {
            $count = count($this->reallist[$i]);
            $objs[$i]["evals"] = $count;
            $objs[$i]["objects"] = array();

            foreach($this->reallist[$i] as $key => $inner)
            {
                foreach($inner as $objkey => $obj)
                {
                    $objs[$i]["objects"][] = $obj;
                }
            }
        }
        $o = array();
        
        foreach($objs as $key => $arr)
        {
            $count = $arr["evals"];
            if(count($arr["objects"]) > 1)
            {
                foreach($arr["objects"] as $objkey => $obj)
                {
                    $counts[] = 0;
                    if($count > 1)
                    {
                        if(in_array($obj, $o))
                        {
                            $index = array_search($obj, $o);
                            $counts[$index]++;
                            if($counts[$index] == $count - 1)
                            {
                                $return = $this->_objex($name, $obj, $return, $t);
                            }
                        }
                        else
                        {
                            $o[] = $obj;
                        }
                    }
                    else
                    {
                        $return = $this->_objex($name, $obj, $return, $t);
                    }
                }
            }
            else
            {
                if(isset($obj))
                    $return = $this->_objex($name, $obj, $return, $t);

            }
        }

        if(isset($this->order_dir) && isset($this->order_name))
        {
            if($this->order_dir == "desc")
            {
                usort($return, array($this, 'compare'));
            }
            else
            {
                usort($return, array($this, 'compare'));
            }
        }
        
        if(isset($this->skip))
        {
            if($this->skip >= count($return))
            {
                return array();
            }
            else
            {
                for($i = 0; $i < $this->skip; $i++)
                {
                    unset($return[$i]);
                }
            }
        }
        
        if(isset($this->take))
        {
            $return = array_values($return);
            if(count($return) != 0)
            {
                if($this->take >= count($return))
                {
                    return $return;
                }
                else
                {
                    $list = array();
                    for($i = 0; $i < $this->take; $i++)
                    {
                        $list[] = $return[$i];
                        return $list;
                    }
                }
            }
        }
        
        if(isset($this->anonexpr))
        {
            return $this->anoneval($this->anonexpr, $return);
        }
        
        if(isset($this->first))
        {
            return $return[0];
        }
        
        if(isset($this->last))
        {
            return $return[count($return) - 1];
        }
        
        return $return;
    }
    
    private function anoneval($expr, $list)
    {
        $return = array(); 
        $keys = array();
        $values = array();
        $concats = array();
        if(strpos($expr, ',') !== false)
        {
            $ex = explode(',', $expr);
        }
        else
        {
            $ex = array();
            $ex[] = $expr;
        }
        foreach($ex as $k => $v)
        {
            $v = trim($v);
            $e = explode(' ', $v);
            if(is_numeric($e[0]))
            {
                throw new Exception(get_class($this) . " error: Numeric key is not allowed in select!");
            }
            if($e[1] != "=")
            {
                throw new Exception(get_class($this) . " error: Invalid anonymous expression, use = when pointing properties.");
            }
            $keys[] = $e[0];
            
            for($i = 0; $i < count($e); $i++)
            {
                if($e[$i] == "+" && strpos($e[$i -1], '$'))
                {
                    if(strpos($e[$i + 1], '$'))
                    {
                        $this->_exists(substr($e[$i + 1], 1, strlen($e[$i + 1) - 1)], $list[0], "$");
                        $concats[$k][] = $e[$i + 1];
                    }
                    else
                    {
                        $concats[$k][] = $e[$i + 1];
                    }
                }
                else
                {
                    $concats[$k][] = $e[$i + 1];
                }
            }
            
            $var = substr($e[2], 1, strlen($e[2]) - 1);
            $this->_exists($var, $list[0], "$");
            $values[] = $var;
        }
        for($i = 0; $i < count($list); $i++)
        {
            $obj = new stdClass;
            for($j = 0; $j < count($keys); $j++)
            {
                $extra = "";
                if(isset($concats[$j]) && count($concats[$j]) != 0)
                {
                    foreach($concats[$j] as $ck => $cv)
                    {
                        $extra .= $cv;
                    }
                }
                $obj->$keys[$j] = $list[$i]->$values[$j] . $extra;
            }
            $return[] = $obj;
        }
        
        return $return;
    }
    
    private function compare($a, $b)
    {
        $d = "";
        if($this->order_type == "Variable")
        {
            $d = $this->order_name;
        }
        else
        {
            $a = call_user_func(array($a, $name));
            $b = call_user_func(array($b, $name));
        }
        if($this->order_dir == "asc")
        {
            if(is_object($a))
            {
               return $a->$d == $b->$d ? 0 : ($a->$d > $b->$d) ? 1 : -1; 
            }
            else
            {
                return $a == $b ? 0 : ($a > $b) ? 1 : -1;
            }
        }
        else
        {
            if(is_object($a))
            {
               return $a->$d == $b->$d ? 0 : ($a->$d < $b->$d) ? 1 : -1; 
            }
            else
            {
                return $a == $b ? 0 : ($a < $b) ? 1 : -1;
            }
        }
    }
    
    public function first()
    {
        if(isset($this->last))
            throw new Exception(get_class($this) . " error: you cannot have both first and last functions!");
        
        $this->first = true;
        return $this;
    }
    
    public function last()
    {
        if(isset($this->first))
            throw new Exception(get_class($this) . " error: you cannot have both first and last functions!");
        
        $this->last = true;
        return $this;
    }

    public function skip($count)
    {
        if(!is_numeric($count))
            throw new Exception(get_class($this) . " error: skip parameter must be a number!");
        
        $this->skip = $count;
                                
        return $this;
    }

    public function take($count)
    {
        if(!is_numeric($count))
            throw new Exception(get_class($this) . " error: take parameter must be a number!");
        
        $this->take = $count;
                                
        return $this;
    }

    public function where($expr)
    {
        $this->evalcount = 0;
        $this->count++;
        $this->evaluate($expr);
        return $this;
    }
    
    public function _and($expr)
    {
        $this->evaluate($expr);
        return $this;
    }
    
    public function _or($expr)
    {
        $this->evalcount = 0;
        $this->count++;
        $this->evaluate($expr);
        return $this;
    }
    
    public function orderBy($expr)
    {
        $e = explode(' ', $expr);
        if(count($e) != 2)
        {
            throw new Exception(get_class($this) . " error: Invalid orderBy expression!");
        }
        $name = substr($e[0], 1, strlen($e[0]));
        $t = substr($e[0], 0, 1);
        
        $direction = $e[1];    
        if($direction != "")
        {
            if($direction == "desc") {}
            elseif($direction == "asc") {}
            else
            {
                throw new Exception(get_class($this) . " error: <b>$direction</b> is not a valid direction!");
            }
        }
        
        if($t == "$")
        {
            $this->_exists($name, $this->l[0], $t);
            $this->order_type = "Variable";
        }
        elseif($t == "~")
        {
            $this->_exists($name, $this->l[0], $t);
            $this->order_type = "Function";
        }
        $this->order_dir = $direction;
        $this->order_name = $name;
        return $this;
    }
    
    private function evaluate($expr)
    {
        $this->evalcount++;
        $e = explode(' ', $expr);
        if(count($e) != 3)
        {
            throw new Exception(get_class($this) . " error: Invalid expression <b>$expr</b> applied in <b>" . debug_backtrace()[1]['function'] . "()</b> function!");
        }
        $name = substr($e[0], 1, strlen($e[0]));
        $comparer = $e[1];
        $value = $e[2];
        $is = "String";
        $type = "Variable";
        $t = substr($e[0], 0, 1);
        if($t == '$')
        {
            $type = "Variable";
            $this->_exists($name, $this->l[0], $t);
            
        }
        elseif($t == "~")
        {
            $type = "Function";
            $this->_exists($name, $this->l[0], $t);
        }
        else
        {
            throw new Exception(get_class($this) . " error: <b>$t</b> is not a valid name type.");
        }
        
        if(is_numeric($value))
        {
            $is = "Number";
            $value = $value + 0;
        }
        
        $return = array();
        foreach($this->l as $obj)
        {
            if($type == "Function")
            {
                $n = call_user_func(array($obj, $name));
            }
            elseif($type == "Variable")
            {
                $n = $obj->$name;
            }
            switch($is)
            {
                case "Number":
                    if($this->vc($n, $value, $comparer))
                    {
                        $return[] = $obj;
                    }
                    break;
                
                case "String":
                    
                    if($comparer == "contains")
                    {
                        if(strpos($n, $value) !== false)
                        {
                            $return[] = $obj;
                        }
                    }
                    elseif($comparer == "beginswith")
                    {
                        if(substr($n, 0, strlen($value)) == $value)
                        {
                            $return[] = $obj;
                        }
                    }
                    elseif($comparer == "endswith")
                    {
                        if(substr($n, strlen($n) - strlen($value), strlen($value)) == $value)
                        {
                            $return[] = $obj;
                        }
                    }
                    else {
                        throw new Exception(get_class($this) . " error: $is comparer: <b>$comparer</b> not recognized!");
                    }
                    break;
            }
        } 
        $this->reallist[$this->count][] = $return;
    }
    
    private function vc($a, $b, $t)
    {
        if(!is_numeric($a))
        {
            $a = strlen($a);
        }
        
        switch($t)
        {
            case "==":
                return $a == $b;
                break;
            
            case ">=":
                return $a >= $b;
                break;
            
            case "<=":
                return $a <= $b;
                break;
            
            case "<":
                return $a < $b;
                break;
            
            case ">":
                return $a > $b;
                break;
            
            default:
                throw new Exception(get_class($this) . " error: Number comparer: <b>$t</b> not recognized!");
                break;
        }
    }
    
    private function _exists($name, $obj, $t)
    {
        if($t == "$")
        {
            if(property_exists($obj, $name))
            {
                return $obj->$name;
            }
            else
            {
                throw new Exception(get_class($this) . " error: Object ". get_class($obj) ." does not have a member variable named <b>$name</b>!");
            }
        }
        elseif($t == "~")
        {
            if(method_exists($obj, $name))
            {
                return call_user_func(array($obj, $name));
            }
            else
            {
                throw new Exception(get_class($this) . " error: Object ". get_class($obj) ." does not have a function named <b>$name</b>!");
            }
        }
        else
        {
            throw new Exception(get_class($this) . " error: Invalid select expression!");
        }
    }
    
    private function _objex($name, $obj, $return, $t)
    {
        if($name != "")
        {
            if(!in_array($this->_exists($name, $obj, $t), $return))
            {
                $return[] = $this->_exists($name, $obj, $t);
            }
        }
        else
        {
            if(!in_array($obj, $return))
            {
                $return[] = $obj;
            }
        } 
        return $return;
    }
  }
